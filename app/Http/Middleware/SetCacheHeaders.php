<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip caching for certain conditions
        if (
            $request->method() !== 'GET' ||
            auth()->guest() ||
            $response->getStatusCode() !== 200
        ) {
            return $response;
        }

        // Skip caching for file downloads (BinaryFileResponse)
        if ($response instanceof BinaryFileResponse) {
            return $response;
        }

        // Cache configuration based on route
        $cacheTime = $this->getCacheTime($request);

        if ($cacheTime > 0) {
            $response->header('Cache-Control', "public, max-age={$cacheTime}");
            $response->header('Pragma', 'cache');
        } else {
            // No cache for sensitive pages
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }

        return $response;
    }

    /**
     * Determine cache time based on route
     */
    private function getCacheTime(Request $request): int
    {
        $routeName = $request->route()?->getName() ?? '';

        // SPK pages - cache for 5 minutes (300 seconds) as data changes frequently
        if (str_contains($routeName, 'spk')) {
            return 300; // 5 minutes
        }

        // Alokasi pages - cache for 10 minutes
        if (str_contains($routeName, 'alokasi')) {
            return 600; // 10 minutes
        }

        // Dashboard - cache for 1 minute (frequently updated)
        if ($routeName === 'dashboard') {
            return 60;
        }

        // Settings, profile - no cache
        if (str_contains($routeName, 'settings') || str_contains($routeName, 'profile')) {
            return 0;
        }

        // Default: cache for 10 minutes for other pages
        return 600;
    }
}
