<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\SessionConcurrencyManager;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class SsoOAuthController extends Controller
{
    public function __construct(protected SessionConcurrencyManager $sessionConcurrencyManager) {}

    public function redirect(Request $request): RedirectResponse
    {
        $isSyncRequest = $request->boolean('sync');
        $syncReturnTo = $this->sanitizeReturnTo($request->query('return_to'));
        $syncTransport = $this->resolveSyncTransport($request->query('transport'));

        if ($isSyncRequest && ! $this->isSsoActive()) {
            return $syncTransport === 'iframe'
                ? $this->syncCompleteRedirect(status: 'skipped')
                : redirect()->to($this->resolveSyncReturnTo($syncReturnTo));
        }

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
        $request->session()->put('sso_oauth_context', [
            'sync' => $isSyncRequest,
            'return_to' => $syncReturnTo,
            'transport' => $syncTransport,
        ]);

        $queryPayload = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => (string) config('services.sso.scope', ''),
            'state' => $state,
        ];

        $prompt = $isSyncRequest
            ? 'none'
            : trim((string) config('services.sso.prompt', ''));

        if ($prompt !== '') {
            $queryPayload['prompt'] = $prompt;
        }

        if ($isSyncRequest) {
            $queryPayload['sync'] = '1';
        }

        $query = http_build_query($queryPayload);

        return redirect()->away(rtrim($baseUrl, '/').'/oauth/authorize?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $oauthContext = $request->session()->pull('sso_oauth_context', []);
        $isSyncRequest = (bool) data_get($oauthContext, 'sync', false);
        $syncReturnTo = $this->sanitizeReturnTo(data_get($oauthContext, 'return_to'));
        $syncTransport = $this->resolveSyncTransport(data_get($oauthContext, 'transport'));
        $expectedState = $request->session()->pull('sso_oauth_state');
        $receivedState = $request->string('state')->toString();

        if (! is_string($expectedState) || $expectedState === '' || $expectedState !== $receivedState) {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'State OAuth SSO tidak valid.',
                'warning',
                [
                    'reason' => 'invalid_state',
                    'sync' => $isSyncRequest,
                ]
            );

            if ($isSyncRequest) {
                return $syncTransport === 'iframe'
                    ? $this->syncCompleteRedirect(status: 'failed')
                    : redirect()->to($this->resolveSyncReturnTo($syncReturnTo))
                        ->with('warning', 'Sinkronisasi sesi SSO gagal karena state tidak valid.');
            }

            return redirect()->route('login')->withErrors([
                'username' => 'State OAuth tidak valid. Silakan coba login lagi.',
            ]);
        }

        if ($request->filled('error')) {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'SSO mengembalikan error saat proses login.',
                'warning',
                [
                    'reason' => 'oauth_error',
                    'error' => $request->string('error')->toString(),
                    'sync' => $isSyncRequest,
                ]
            );

            if ($isSyncRequest) {
                $oauthError = $request->string('error')->toString();

                if ($this->isExpiredSsoSessionError($oauthError)) {
                    return $this->logoutAndRedirectToLogin($request, $syncTransport);
                }

                return $syncTransport === 'iframe'
                    ? $this->syncCompleteRedirect(status: 'failed')
                    : redirect()->to($this->resolveSyncReturnTo($syncReturnTo))
                        ->with('warning', 'Sinkronisasi sesi SSO belum berhasil.');
            }

            $errorDescription = $request->string('error_description')->toString();
            $message = $errorDescription !== '' ? $errorDescription : 'Login SSO dibatalkan atau gagal.';

            return redirect()->route('login')
                ->with('error', $message)
                ->withErrors([
                    'username' => $message,
                ]);
        }

        $code = $request->string('code')->toString();

        if ($code === '') {
            return redirect()->route('login')->withErrors([
                'username' => 'Kode otorisasi dari SSO tidak ditemukan.',
            ]);
        }

        /** @var Response $tokenResponse */
        $tokenRequest = Http::asForm()->acceptJson();

        if ($isSyncRequest) {
            $tokenRequest = $tokenRequest->withHeaders([
                'X-SSO-Sync' => '1',
            ]);
        }

        $tokenResponse = $tokenRequest
            ->acceptJson()
            ->post(rtrim($this->baseUrl(), '/').'/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => (string) config('services.sso.client_id'),
                'client_secret' => (string) config('services.sso.client_secret'),
                'redirect_uri' => $this->redirectUri(),
                'code' => $code,
            ]);

        if ($tokenResponse->failed()) {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'Gagal menukar kode OAuth ke access token.',
                'error',
                [
                    'reason' => 'token_exchange_failed',
                    'sync' => $isSyncRequest,
                ]
            );

            return redirect()->route('login')->withErrors([
                'username' => 'Gagal menukar kode OAuth ke access token.',
            ]);
        }

        $accessToken = (string) $tokenResponse->json('access_token');

        if ($accessToken === '') {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'Access token SSO tidak valid.',
                'error',
                [
                    'reason' => 'missing_access_token',
                    'sync' => $isSyncRequest,
                ]
            );

            return redirect()->route('login')->withErrors([
                'username' => 'Access token dari SSO tidak valid.',
            ]);
        }

        /** @var Response $profileResponse */
        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get(rtrim($this->baseUrl(), '/').$this->userEndpoint());

        if ($profileResponse->failed()) {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'Gagal mengambil profil pengguna dari SSO.',
                'error',
                [
                    'reason' => 'profile_fetch_failed',
                    'sync' => $isSyncRequest,
                ]
            );

            return redirect()->route('login')->withErrors([
                'username' => 'Gagal mengambil profil pengguna dari SSO.',
            ]);
        }

        $profile = $profileResponse->json();

        if (! is_array($profile)) {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'Format profil pengguna dari SSO tidak valid.',
                'error',
                [
                    'reason' => 'invalid_profile_payload',
                    'sync' => $isSyncRequest,
                ]
            );

            return redirect()->route('login')->withErrors([
                'username' => 'Format profil pengguna dari SSO tidak valid.',
            ]);
        }

        $organizationType = $this->resolveOrganizationType($profile);

        if (! $this->isAllowedOrganizationType($organizationType)) {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'Akun SSO tidak diizinkan berdasarkan organisasi.',
                'warning',
                [
                    'reason' => 'organization_not_allowed',
                    'organization_type' => $organizationType,
                    'sync' => $isSyncRequest,
                ]
            );

            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda tidak diizinkan mengakses aplikasi ini berdasarkan organisasi.',
            ]);
        }

        $ssoUserId = isset($profile['id']) && is_numeric($profile['id'])
            ? (int) $profile['id']
            : null;

        $email = isset($profile['email']) && is_string($profile['email'])
            ? trim($profile['email'])
            : '';

        $username = isset($profile['username']) && is_string($profile['username'])
            ? trim($profile['username'])
            : '';

        if ($email === '' && $username === '') {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'Profil SSO tidak memiliki email atau username yang bisa dipakai login.',
                'warning',
                [
                    'reason' => 'missing_login_identity',
                    'sync' => $isSyncRequest,
                ]
            );

            return redirect()->route('login')->withErrors([
                'username' => 'Profil SSO tidak memiliki email/username yang bisa dipakai login.',
            ]);
        }

        $localUser = $this->resolveLocalUser($ssoUserId, $email, $username);

        if (! $localUser) {
            $localUser = $this->provisionLocalUser($ssoUserId, $profile, $email, $username);
        }

        if (! $localUser->is_active) {
            ActivityLog::logAuth(
                'Login SSO Gagal',
                'Akun lokal yang dipetakan dari SSO tidak aktif.',
                'warning',
                [
                    'reason' => 'inactive_local_user',
                    'user_id' => $localUser->id,
                    'sync' => $isSyncRequest,
                ]
            );

            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda nonaktif. Hubungi admin.',
            ]);
        }

        /** @var User|null $authenticatedUser */
        $authenticatedUser = Auth::user();

        if ($authenticatedUser && $authenticatedUser->isNot($localUser)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $name = isset($profile['name']) && is_string($profile['name'])
            ? trim($profile['name'])
            : $localUser->name;

        // Sync data dari SSO ke local user
        $localUser->forceFill([
            'sso_user_id' => $ssoUserId,
            'sso_organization_type' => $organizationType,
            ...$this->resolveSsoSynchronizationAttributes(
                profile: $profile,
                fallbackName: $name,
                fallbackUsername: $localUser->username,
                fallbackEmail: $localUser->email,
                fallbackEmailVerifiedAt: $localUser->email_verified_at,
            ),
        ])->save();

        $this->ensureDefaultRole($localUser);

        if ($isSyncRequest) {
            // For a sync (prompt=none) callback, only update the user profile.
            // Do NOT regenerate the session or call activateLatestSession:
            // doing so broadcasts SessionInvalidated via WebSocket and kicks
            // the user out through the useSessionInvalidation hook.
            if (! Auth::check()) {
                return $syncTransport === 'iframe'
                    ? $this->syncCompleteRedirect(status: 'login_required')
                    : redirect()->route('login')->with('warning', 'Sesi aplikasi Anda sudah berakhir. Silakan login kembali.');
            }

            return $syncTransport === 'iframe'
                ? $this->syncCompleteRedirect(status: 'ok')
                : redirect()->to($this->resolveSyncReturnTo($syncReturnTo));
        }

        Auth::login($localUser, true);
        $request->session()->regenerate();
        $request->session()->put('last_user_activity_at', now()->timestamp);
        $this->sessionConcurrencyManager->activateLatestSession($request, $localUser->id);

        ActivityLog::logAuth(
            'Login',
            'Pengguna berhasil masuk ke aplikasi melalui SSO.',
            'success',
            [
                'source' => 'sso',
                'user_id' => $localUser->id,
            ]
        );

        return redirect()->intended(route('dashboard'));
    }

    private function resolveSyncReturnTo(?string $returnTo): string
    {
        return $returnTo ?? route('dashboard');
    }

    private function sanitizeReturnTo(mixed $returnTo): ?string
    {
        if (! is_string($returnTo)) {
            return null;
        }

        $trimmed = trim($returnTo);

        if ($trimmed === '' || ! str_starts_with($trimmed, '/')) {
            return null;
        }

        if (str_starts_with($trimmed, '//')) {
            return null;
        }

        return $trimmed;
    }

    private function isExpiredSsoSessionError(string $oauthError): bool
    {
        // Treat only explicit session expiration as a hard signal to invalidate local session.
        // Other prompt=none errors (e.g. login_required / interaction_required) can be transient
        // and should not force local logout.
        return in_array($oauthError, ['session_expired'], true);
    }

    private function logoutAndRedirectToLogin(Request $request, string $syncTransport = 'redirect'): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($syncTransport === 'iframe') {
            return $this->syncCompleteRedirect(status: 'login_required');
        }

        return redirect()->route('login')
            ->with('error', 'Sesi SSO Anda sudah berakhir. Silakan login ulang.');
    }

    private function syncCompleteRedirect(string $status): RedirectResponse
    {
        return redirect()->route('sso.sync-complete', ['status' => $status]);
    }

    private function resolveSyncTransport(mixed $transport): string
    {
        if (! is_string($transport)) {
            return 'redirect';
        }

        return in_array($transport, ['redirect', 'iframe'], true)
            ? $transport
            : 'redirect';
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

    private function isSsoActive(): bool
    {
        if (! (bool) config('services.sso.active', true)) {
            return false;
        }

        $clientId = config('services.sso.client_id');
        $baseUrl = $this->baseUrl();

        if (! $clientId || $baseUrl === '') {
            return false;
        }

        $cacheKey = sprintf('sso:application-active:%s:%s', (string) $clientId, md5($baseUrl));

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($baseUrl, $clientId): bool {
            try {
                /** @var Response $response */
                $response = Http::timeout(5)
                    ->acceptJson()
                    ->get(rtrim($baseUrl, '/').'/api/application/status', [
                        'client_id' => $clientId,
                    ]);

                if (! $response->successful()) {
                    return false;
                }

                return (bool) $response->json('is_active', false);
            } catch (\Throwable $exception) {
                Log::warning('SSO status check failed before sync redirect.', [
                    'error' => $exception->getMessage(),
                ]);

                return false;
            }
        });
    }

    private function ensureSsoApplicationIsActive(): bool
    {
        if ($this->isSsoActive()) {
            return true;
        }

        $baseUrl = $this->baseUrl();
        $clientId = (string) config('services.sso.client_id');

        if ($baseUrl === '' || $clientId === '') {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post(rtrim($baseUrl, '/').'/api/application/register', [
                    'client_id' => $clientId,
                    'redirect_uri' => $this->redirectUri(),
                    'name' => config('app.name', 'Laravel App'),
                    'source' => 'local-registration',
                ]);

            if ($response->successful()) {
                $registered = (bool) $response->json('registered', false);
                $isActive = (bool) $response->json('is_active', false);

                if ($registered || $isActive) {
                    Cache::forget(sprintf('sso:application-active:%s:%s', $clientId, md5($baseUrl)));

                    return true;
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('SSO app registration failed during sync.', [
                'error' => $exception->getMessage(),
                'client_id' => $clientId,
            ]);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function resolveOrganizationType(array $profile): ?string
    {
        $topLevelType = $profile['organization_type'] ?? null;

        if (is_string($topLevelType) && trim($topLevelType) !== '') {
            return trim($topLevelType);
        }

        $organization = $profile['organization'] ?? null;
        if (! is_array($organization)) {
            return null;
        }

        $nestedType = $organization['type'] ?? null;

        return is_string($nestedType) && trim($nestedType) !== ''
            ? trim($nestedType)
            : null;
    }

    private function isAllowedOrganizationType(?string $organizationType): bool
    {
        $allowedTypes = config('services.sso.allowed_organization_types', []);

        if (! is_array($allowedTypes) || $allowedTypes === []) {
            return true;
        }

        if (! is_string($organizationType) || $organizationType === '') {
            return false;
        }

        return in_array($organizationType, $allowedTypes, true);
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
        $emailVerifiedAt = $this->resolveEmailVerifiedAt($profile['email_verified_at'] ?? null, $resolvedEmail) ?? now();

        $user = User::query()->create([
            'sso_user_id' => $ssoUserId,
            'name' => $name,
            'username' => $resolvedUsername,
            'email' => $resolvedEmail,
            'password' => Hash::make(Str::random(40)),
            'is_active' => true,
        ]);

        $user->forceFill([
            'email_verified_at' => $emailVerifiedAt,
        ])->save();

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

        return null;
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

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function resolveSsoSynchronizationAttributes(
        array $profile,
        string $fallbackName,
        string $fallbackUsername,
        string $fallbackEmail,
        mixed $fallbackEmailVerifiedAt,
    ): array {
        $attributes = [
            'name' => $fallbackName,
            'username' => $this->resolveStringValue($profile['username'] ?? null) ?? $fallbackUsername,
            'email' => $this->resolveStringValue($profile['email'] ?? null) ?? $fallbackEmail,
            'email_verified_at' => array_key_exists('email_verified_at', $profile)
                ? $this->resolveEmailVerifiedAt($profile['email_verified_at'], $fallbackEmail)
                : $fallbackEmailVerifiedAt,
        ];

        if ($this->hasUserColumn('password')) {
            $passwordHash = $this->resolveStringValue($profile['password_hash'] ?? null);

            if ($passwordHash !== null) {
                $attributes['password'] = $passwordHash;
            }
        }

        if ($this->hasUserColumn('last_login_at')) {
            $attributes['last_login_at'] = $this->resolveDateTimeValue($profile['last_login_at'] ?? null);
        }

        if ($this->hasUserColumn('password_change_required')) {
            $attributes['password_change_required'] = (bool) ($profile['password_change_required'] ?? false);
        }

        return [
            ...$attributes,
            ...$this->resolveTwoFactorAttributes($profile),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function resolveTwoFactorAttributes(array $profile): array
    {
        if (! $this->hasUserColumn('two_factor_secret')
            || ! $this->hasUserColumn('two_factor_recovery_codes')
            || ! $this->hasUserColumn('two_factor_confirmed_at')) {
            return [];
        }

        $twoFactorPayload = $this->resolveTwoFactorPayload($profile);

        if ($twoFactorPayload === null) {
            return [];
        }

        if ($twoFactorPayload === false) {
            return [
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ];
        }

        [$secret, $recoveryCodes, $confirmedAt] = $twoFactorPayload;

        $normalizedRecoveryCodes = collect($recoveryCodes)
            ->filter(fn (mixed $code): bool => is_string($code) && trim($code) !== '')
            ->map(fn (string $code): string => trim($code))
            ->values()
            ->all();

        return [
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt(trim($secret)),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($normalizedRecoveryCodes)),
            'two_factor_confirmed_at' => $this->resolveDateTimeValue($confirmedAt),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{0:string,1:array<int,string>,2:mixed}|false|null
     */
    private function resolveTwoFactorPayload(array $profile): array|bool|null
    {
        if (! array_key_exists('two_factor', $profile)) {
            $legacySecret = $this->resolveStringValue($profile['two_factor_secret_plain'] ?? null);

            if ($legacySecret === null) {
                return null;
            }

            return [
                $legacySecret,
                is_array($profile['two_factor_recovery_codes_plain'] ?? null)
                    ? $profile['two_factor_recovery_codes_plain']
                    : [],
                $profile['two_factor_confirmed_at'] ?? null,
            ];
        }

        $twoFactor = $profile['two_factor'];

        if (! is_array($twoFactor)) {
            return false;
        }

        $secret = $this->resolveStringValue($twoFactor['secret'] ?? null)
            ?? $this->resolveStringValue($profile['two_factor_secret_plain'] ?? null);
        $recoveryCodes = $twoFactor['recovery_codes'] ?? $profile['two_factor_recovery_codes_plain'] ?? [];
        $confirmedAt = $twoFactor['confirmed_at'] ?? $profile['two_factor_confirmed_at'] ?? null;

        if ($secret === null) {
            return false;
        }

        return [
            $secret,
            is_array($recoveryCodes) ? $recoveryCodes : [],
            $confirmedAt,
        ];
    }

    private function resolveDateTimeValue(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }

    private function resolveStringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : null;
    }

    private function hasUserColumn(string $column): bool
    {
        return Schema::hasColumn('users', $column);
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
