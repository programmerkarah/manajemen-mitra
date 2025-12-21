<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewAsUserMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only allow rhmtzikri to use this feature
        if ($request->user() && $request->user()->username === 'rhmtzikri') {
            $viewAsUserId = session('view_as_user_id');

            if ($viewAsUserId) {
                $viewAsUser = User::with('roles')->find($viewAsUserId);

                if ($viewAsUser) {
                    // Refresh to get latest data from database
                    $viewAsUser->refresh();
                    $viewAsUser->load(['roles']);

                    // Store both original and viewed user in request attributes
                    $request->attributes->set('original_user', $request->user());
                    $request->attributes->set('view_as_user', $viewAsUser);
                }
            }
        }

        return $next($request);
    }
}
