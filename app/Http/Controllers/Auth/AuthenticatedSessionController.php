<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SessionConcurrencyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

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

    private function resolveSsoActive(): bool
    {
        $clientId = config('services.sso.client_id');
        $baseUrl = config('services.sso.base_url');

        if (! $clientId || ! $baseUrl) {
            return false;
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
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

        $user = Auth::user();

        if (
            $user
            && Features::enabled(Features::twoFactorAuthentication())
            && ! is_null($user->two_factor_secret)
            && ! is_null($user->two_factor_confirmed_at)
        ) {
            $userId = $user->getAuthIdentifier();

            if (! is_int($userId) && ! ctype_digit((string) $userId)) {
                abort(500, 'User identifier tidak valid untuk autentikasi dua faktor.');
            }

            Auth::guard('web')->logout();
            $request->session()->put([
                'login.id' => (int) $userId,
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();

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
