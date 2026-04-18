<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if user is not authenticated, already has 2FA enabled, or is an SSO-managed user
        // SSO-managed users have 2FA handled by the SSO provider
        if (! $user || $user->hasEnabledTwoFactorAuthentication() || ! is_null($user->sso_user_id)) {
            return $next($request);
        }

        // Skip for 2FA setup prompt, 2FA routes, settings, verification, and logout routes
        if (
            $request->routeIs('two-factor.prompt') ||
            $request->routeIs('two-factor.*') ||
            $request->routeIs('profile.*') ||
            $request->routeIs('user-password.*') ||
            $request->routeIs('appearance.*') ||
            $request->routeIs('verification.*') ||
            $request->routeIs('logout') ||
            $request->routeIs('user.*') ||
            $request->routeIs('password.confirm') ||
            str_starts_with($request->path(), 'settings/') ||
            str_starts_with($request->path(), 'user/')
        ) {
            return $next($request);
        }

        // Redirect to 2FA setup prompt
        return redirect()->route('two-factor.prompt');
    }
}
