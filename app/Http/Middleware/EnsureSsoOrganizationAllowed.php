<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoOrganizationAllowed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || is_null($user->sso_user_id)) {
            return $next($request);
        }

        $allowedTypes = config('services.sso.allowed_organization_types', []);

        if (! is_array($allowedTypes) || $allowedTypes === []) {
            return $next($request);
        }

        $organizationType = is_string($user->sso_organization_type) && trim($user->sso_organization_type) !== ''
            ? trim($user->sso_organization_type)
            : null;

        if ($organizationType !== null && in_array($organizationType, $allowedTypes, true)) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'username' => 'Akun Anda tidak diizinkan mengakses aplikasi ini berdasarkan organisasi.',
        ]);
    }
}
