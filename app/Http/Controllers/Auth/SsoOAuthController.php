<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SessionConcurrencyManager;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoOAuthController extends Controller
{
    public function __construct(protected SessionConcurrencyManager $sessionConcurrencyManager) {}

    public function redirect(Request $request): RedirectResponse
    {
        $baseUrl = $this->baseUrl();
        $clientId = (string) config('services.sso.client_id');
        $redirectUri = $this->redirectUri();

        if ($baseUrl === '' || $clientId === '') {
            return redirect()->route('login')->withErrors([
                'username' => 'Konfigurasi SSO belum lengkap. Hubungi administrator.',
            ]);
        }

        $state = Str::random(40);
        $request->session()->put('sso_oauth_state', $state);

        $queryPayload = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => (string) config('services.sso.scope', ''),
            'state' => $state,
        ];

        $prompt = trim((string) config('services.sso.prompt', ''));
        if ($prompt !== '') {
            $queryPayload['prompt'] = $prompt;
        }

        $query = http_build_query($queryPayload);

        return redirect()->away(rtrim($baseUrl, '/').'/oauth/authorize?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('sso_oauth_state');
        $receivedState = $request->string('state')->toString();

        if (! is_string($expectedState) || $expectedState === '' || $expectedState !== $receivedState) {

            return redirect()->route('login')->withErrors([
                'username' => 'State OAuth tidak valid. Silakan coba login lagi.',
            ]);
        }

        if ($request->filled('error')) {
            $errorDescription = $request->string('error_description')->toString();

            return redirect()->route('login')->withErrors([
                'username' => $errorDescription !== '' ? $errorDescription : 'Login SSO dibatalkan atau gagal.',
            ]);
        }

        $code = $request->string('code')->toString();

        if ($code === '') {
            return redirect()->route('login')->withErrors([
                'username' => 'Kode otorisasi dari SSO tidak ditemukan.',
            ]);
        }

        /** @var Response $tokenResponse */
        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post(rtrim($this->baseUrl(), '/').'/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => (string) config('services.sso.client_id'),
                'client_secret' => (string) config('services.sso.client_secret'),
                'redirect_uri' => $this->redirectUri(),
                'code' => $code,
            ]);

        if ($tokenResponse->failed()) {
            return redirect()->route('login')->withErrors([
                'username' => 'Gagal menukar kode OAuth ke access token.',
            ]);
        }

        $accessToken = (string) $tokenResponse->json('access_token');

        if ($accessToken === '') {
            return redirect()->route('login')->withErrors([
                'username' => 'Access token dari SSO tidak valid.',
            ]);
        }

        /** @var Response $profileResponse */
        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get(rtrim($this->baseUrl(), '/').$this->userEndpoint());

        if ($profileResponse->failed()) {
            return redirect()->route('login')->withErrors([
                'username' => 'Gagal mengambil profil pengguna dari SSO.',
            ]);
        }

        $profile = $profileResponse->json();

        if (! is_array($profile)) {
            return redirect()->route('login')->withErrors([
                'username' => 'Format profil pengguna dari SSO tidak valid.',
            ]);
        }

        $ssoUserId = isset($profile['id']) && (is_int($profile['id']) || is_string($profile['id']))
            ? (int) $profile['id']
            : null;

        $email = isset($profile['email']) && is_string($profile['email'])
            ? trim($profile['email'])
            : '';

        $username = isset($profile['username']) && is_string($profile['username'])
            ? trim($profile['username'])
            : '';

        if ($email === '' && $username === '') {
            return redirect()->route('login')->withErrors([
                'username' => 'Profil SSO tidak memiliki email/username yang bisa dipakai login.',
            ]);
        }

        $localUser = $this->resolveLocalUser($ssoUserId, $email, $username);

        if (! $localUser) {
            $localUser = $this->provisionLocalUser($ssoUserId, $profile, $email, $username);
        }

        if (! $localUser->is_active) {
            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda nonaktif. Hubungi admin.',
            ]);
        }

        $name = isset($profile['name']) && is_string($profile['name'])
            ? trim($profile['name'])
            : $localUser->name;

        $emailVerifiedAt = $this->resolveEmailVerifiedAt($profile['email_verified_at'] ?? null, $email);

        // Sync data dari SSO ke local user
        $localUser->forceFill([
            'sso_user_id' => $ssoUserId,
            'name' => $name,
            'username' => $username !== '' ? $username : $localUser->username,
            'email' => $email !== '' ? $email : $localUser->email,
            'email_verified_at' => $emailVerifiedAt ?? $localUser->email_verified_at,
        ])->save();

        $this->ensureDefaultRole($localUser);

        Auth::login($localUser, true);
        $request->session()->regenerate();
        $this->sessionConcurrencyManager->activateLatestSession($request, $localUser->id);

        return redirect()->intended(route('dashboard'));
    }

    private function baseUrl(): string
    {
        return (string) config('services.sso.base_url');
    }

    private function redirectUri(): string
    {
        $configured = (string) config('services.sso.redirect_uri');

        return $configured !== '' ? $configured : route('sso.callback');
    }

    private function userEndpoint(): string
    {
        $endpoint = (string) config('services.sso.user_endpoint', '/api/user');

        return str_starts_with($endpoint, '/') ? $endpoint : '/'.$endpoint;
    }

    private function resolveLocalUser(?int $ssoUserId, string $email, string $username): ?User
    {
        if ($ssoUserId !== null) {
            $user = User::query()->where('sso_user_id', $ssoUserId)->first();

            if ($user) {
                return $user;
            }
        }

        if ($email !== '') {
            $user = User::query()->where('email', $email)->first();

            if ($user) {
                return $user;
            }
        }

        if ($username !== '') {
            return User::query()->where('username', $username)->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function provisionLocalUser(?int $ssoUserId, array $profile, string $email, string $username): User
    {
        $name = isset($profile['name']) && is_string($profile['name']) && trim($profile['name']) !== ''
            ? trim($profile['name'])
            : ($username !== '' ? $username : $email);

        $resolvedUsername = $this->resolveProvisionedUsername($username, $email, $ssoUserId);
        $resolvedEmail = $this->resolveProvisionedEmail($email, $resolvedUsername, $ssoUserId);

        $user = User::query()->create([
            'sso_user_id' => $ssoUserId,
            'name' => $name,
            'username' => $resolvedUsername,
            'email' => $resolvedEmail,
            'email_verified_at' => $this->resolveEmailVerifiedAt($profile['email_verified_at'] ?? null, $resolvedEmail),
            'password' => Hash::make(Str::random(40)),
            'is_active' => true,
        ]);

        $this->ensureDefaultRole($user);

        return $user;
    }

    private function ensureDefaultRole(User $user): void
    {
        if ($user->roles()->exists()) {
            return;
        }

        $user->assignRole('guest');
    }

    private function resolveEmailVerifiedAt(mixed $emailVerifiedAt, string $email): ?Carbon
    {
        if ($emailVerifiedAt instanceof Carbon) {
            return $emailVerifiedAt;
        }

        if (is_string($emailVerifiedAt) && $emailVerifiedAt !== '') {
            return Carbon::parse($emailVerifiedAt);
        }

        return $email !== '' ? now() : null;
    }

    private function resolveProvisionedUsername(string $username, string $email, ?int $ssoUserId): string
    {
        $candidate = trim($username);

        if ($candidate === '' && $email !== '') {
            $candidate = Str::before($email, '@');
        }

        if ($candidate === '') {
            $candidate = 'sso-user';
        }

        return $this->makeUniqueValue('username', $candidate, $ssoUserId);
    }

    private function resolveProvisionedEmail(string $email, string $username, ?int $ssoUserId): string
    {
        $candidate = trim($email);

        if ($candidate === '') {
            $suffix = $ssoUserId !== null ? (string) $ssoUserId : Str::lower(Str::random(6));
            $candidate = sprintf('%s+%s@placeholder.sso.local', Str::slug($username, '.'), $suffix);
        }

        return $this->makeUniqueValue('email', $candidate, $ssoUserId);
    }

    private function makeUniqueValue(string $column, string $candidate, ?int $ssoUserId): string
    {
        $value = $candidate;
        $suffix = $ssoUserId !== null ? (string) $ssoUserId : Str::lower(Str::random(6));
        $attempt = 0;

        while (User::query()->where($column, $value)->exists()) {
            $attempt++;

            if ($column === 'email') {
                $localPart = Str::before($candidate, '@');
                $domainPart = Str::after($candidate, '@');
                $value = sprintf('%s+%s-%d@%s', $localPart, $suffix, $attempt, $domainPart);

                continue;
            }

            $value = sprintf('%s-%s-%d', $candidate, $suffix, $attempt);
        }

        return $value;
    }
}
