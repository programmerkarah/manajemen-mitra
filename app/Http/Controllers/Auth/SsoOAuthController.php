<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoOAuthController extends Controller
{
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

        $localUser = User::query()
            ->when($email !== '', fn ($query) => $query->where('email', $email))
            ->when($username !== '', fn ($query) => $query->orWhere('username', $username))
            ->first();

        if (! $localUser) {
            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda belum terdaftar di aplikasi ini. Hubungi admin.',
            ]);
        }

        if (! $localUser->is_active) {
            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda nonaktif. Hubungi admin.',
            ]);
        }

        $name = isset($profile['name']) && is_string($profile['name'])
            ? trim($profile['name'])
            : $localUser->name;

        $emailVerifiedAt = $profile['email_verified_at'] ?? null;

        $localUser->forceFill([
            'name' => $name,
            'username' => $username !== '' ? $username : $localUser->username,
            'email' => $email !== '' ? $email : $localUser->email,
            'email_verified_at' => is_string($emailVerifiedAt) && $emailVerifiedAt !== ''
                ? $emailVerifiedAt
                : $localUser->email_verified_at,
        ])->save();

        Auth::login($localUser, true);
        $request->session()->regenerate();

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
}
