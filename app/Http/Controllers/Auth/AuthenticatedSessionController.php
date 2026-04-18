<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SessionConcurrencyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected SessionConcurrencyManager $sessionConcurrencyManager) {}

    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        $ssoActive = $this->resolveSsoActive();
        $ssoConfigured = config('services.sso.base_url') !== '' && config('services.sso.client_id') !== null;
        $ssoEnabled = $ssoActive && $ssoConfigured;

        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
            'ssoEnabled' => $ssoEnabled,
            'ssoActive' => $ssoActive,
            'ssoLoginUrl' => $ssoEnabled ? route('sso.redirect') : null,
            'ssoRegisterUrl' => config('services.sso.register_url'),
            'canResetPassword' => ! $ssoActive,
            'canRegister' => ! $ssoActive,
        ]);
    }

    /**
     * Resolve whether SSO is active by querying the SSO API.
     * Result is cached for 60 seconds to avoid excessive API calls.
     */
    private function resolveSsoActive(): bool
    {
        $clientId = config('services.sso.client_id');
        $baseUrl = config('services.sso.base_url');

        if (! $clientId || ! $baseUrl) {
            return false;
        }

        $cacheKey = 'sso:application_active:'.$clientId;

        return Cache::remember($cacheKey, 60, function () use ($baseUrl, $clientId): bool {
            try {
                $response = Http::timeout(5)
                    ->get(rtrim($baseUrl, '/').'/api/application/status', [
                        'client_id' => $clientId,
                    ]);

                if ($response->successful()) {
                    return (bool) $response->json('is_active', false);
                }
            } catch (\Throwable $e) {
                Log::warning('SSO application status check failed, defaulting to native login.', [
                    'error' => $e->getMessage(),
                ]);
            }

            return false;
        });
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $ssoActive = $this->resolveSsoActive();

        if ($ssoActive) {
            return redirect()->route('sso.redirect');
        }

        $request->authenticate();

        $request->session()->regenerate();

        // Update last login time
        $user = Auth::user();
        if ($user) {
            $user->update(['last_login_at' => now()]);
            $this->sessionConcurrencyManager->activateLatestSession($request, $user->id);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        Auth::guard('web')->logout();

        if (is_int($userId)) {
            $this->sessionConcurrencyManager->forgetIfCurrentSession($request, $userId);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
