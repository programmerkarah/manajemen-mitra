<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log incoming request
        $requestId = uniqid('req_', true);
        $request->attributes->set('request_id', $requestId);

        $startTime = microtime(true);

        try {
            $response = $next($request);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $isInertiaRequest = $request->header('X-Inertia') === 'true';

            Log::info('📤 [RESPONSE OUT] '.$requestId, [
                'status' => $response->getStatusCode(),
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'route' => $request->route()?->getName(),
                'is_inertia_request' => $isInertiaRequest,
                'x_inertia_version' => $request->header('X-Inertia-Version'),
                'duration_ms' => $duration,
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);

            if ($response->getStatusCode() === 409) {
                Log::warning('⚠️ [INERTIA CONFLICT] '.$requestId, [
                    'method' => $request->method(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'route' => $request->route()?->getName(),
                    'is_inertia_request' => $isInertiaRequest,
                    'x_inertia_version' => $request->header('X-Inertia-Version'),
                    'response_x_inertia_location' => $response->headers->get('X-Inertia-Location'),
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            throw $e;
        }
    }
}
