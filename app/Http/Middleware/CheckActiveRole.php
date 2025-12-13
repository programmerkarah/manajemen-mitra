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
        $user = $request->user();

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
            $activeRole = $user->getActiveRole();
            $activeRoleName = $activeRole ? $activeRole->display_name : 'None';

            abort(403, "Akses ditolak. Role aktif Anda ({$activeRoleName}) tidak memiliki izin untuk halaman ini.");
        }

        return $next($request);
    }
}
