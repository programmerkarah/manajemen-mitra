<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HonorReturnNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->isMethodSafe() ||
            ! $response instanceof RedirectResponse
        ) {
            return $response;
        }

        $returnTo = $request->input('_return_to');

        if (! is_string($returnTo) || ! $this->isSafeInternalPath($returnTo)) {
            return $response;
        }

        // A mutation must redirect with 303 so the follow-up request is GET.
        // Using 302 here can make fetch/Inertia preserve PATCH/PUT and resend it
        // to the destination (for example PATCH /users), causing a 405.
        return redirect($returnTo, 303);
    }

    private function isSafeInternalPath(string $returnTo): bool
    {
        if (
            $returnTo === '' ||
            ! str_starts_with($returnTo, '/') ||
            str_starts_with($returnTo, '//')
        ) {
            return false;
        }

        $path = parse_url($returnTo, PHP_URL_PATH);

        if (! is_string($path)) {
            return false;
        }

        return ! in_array($path, [
            '/login',
            '/logout',
            '/register',
            '/sso/redirect',
            '/sso/callback',
        ], true);
    }
}
