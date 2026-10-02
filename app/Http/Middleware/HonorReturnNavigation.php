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

        return redirect($returnTo);
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
