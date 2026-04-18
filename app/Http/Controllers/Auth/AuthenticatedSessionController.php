<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
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
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
