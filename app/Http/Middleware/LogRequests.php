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

            Log::info('📤 [RESPONSE OUT] ' . $requestId, [
                'status' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);

            return $response;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            throw $e;
        }
    }
}
