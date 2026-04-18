<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SessionConcurrencyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $ssoEnabled = config('services.sso.base_url') !== '' && config('services.sso.client_id') !== null;
        
        // Always show login page with SSO button
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
            'ssoEnabled' => $ssoEnabled,
            'ssoLoginUrl' => $ssoEnabled ? route('sso.redirect') : null,
            'ssoRegisterUrl' => $ssoEnabled ? config('services.sso.register_url') : null,
            'canResetPassword' => false, // Password reset via SSO only
            'canRegister' => false, // Registration via SSO only
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('sso.redirect');
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
