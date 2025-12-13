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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if user is not authenticated or already has 2FA enabled
        if (! $user || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        // Skip for 2FA setup prompt, 2FA routes, settings, verification, and logout routes
        if (
            $request->routeIs('home') ||
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
