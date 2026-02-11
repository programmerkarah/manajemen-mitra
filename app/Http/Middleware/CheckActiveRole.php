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
        \Log::info('🛡️ [MIDDLEWARE] CheckActiveRole - Request intercepted', [
            'url' => $request->url(),
            'method' => $request->method(),
            'required_roles' => $roles,
            'user_id' => auth()->id()
        ]);
        
        $user = effectiveUser($request);

        if (! $user) {
            \Log::warning('⚠️ [MIDDLEWARE] No user found, redirecting to login');
            return redirect()->route('login');
        }

        // Only refresh if not in view_as mode to preserve effectiveUser data
        if (! session()->has('view_as_user_id')) {
            // Refresh user to get latest roles from database
            $user->refresh();
        }

        // Always load roles
        $user->load(['roles']);
        
        \Log::info('👤 [MIDDLEWARE] User loaded', [
            'user_id' => $user->id,
            'email' => $user->email,
            'active_role' => $user->getActiveRole()?->name,
            'all_roles' => $user->roles->pluck('name')->toArray()
        ]);

        // If no roles specified, just ensure user has active role
        if (empty($roles)) {
            if (! $user->getActiveRole()) {
                \Log::error('❌ [MIDDLEWARE] User has no active role');
                abort(403, 'Anda tidak memiliki role aktif.');
            }
            
            \Log::info('✅ [MIDDLEWARE] User has active role, proceeding');
            return $next($request);
        }

        // Check if user's active role matches any of the specified roles
        if (! $user->hasAnyActiveRole($roles)) {
            \Log::warning('⚠️ [MIDDLEWARE] User active role does not match required roles', [
                'active_role' => $user->getActiveRole()?->name,
                'required_roles' => $roles
            ]);
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut dengan role saat ini.');
        }

        \Log::info('✅ [MIDDLEWARE] Role check passed, proceeding to controller');
        return $next($request);
    }
}
