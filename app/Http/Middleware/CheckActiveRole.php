<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = effectiveUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        // If no roles specified, just ensure user has active role
        if (empty($roles)) {
            if (! $user->getActiveRole()) {
                abort(403, 'Anda tidak memiliki role aktif.');
            }

            return $next($request);
        }

        // Check if user's active role matches any of the specified roles
        if (! $user->hasAnyActiveRole($roles)) {
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut dengan role saat ini.');
        }

        return $next($request);
    }
}
