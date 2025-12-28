<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EncryptFiltersInUrl
{
    /**
     * Handle an incoming request.
     * Encrypt filter parameters for security
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For POST requests with filters, encrypt them
        if ($request->isMethod('POST') && $this->hasFilterParams($request)) {
            // Get all filter parameters
            $filters = $this->getFilterParams($request);

            // Store encrypted filters in session for next request
            session(['encrypted_filters' => encryptFilters($filters)]);
        }

        return $next($request);
    }

    /**
     * Check if request has filter parameters
     */
    private function hasFilterParams(Request $request): bool
    {
        $filterKeys = ['search', 'status', 'tahun', 'bulan', 'jenis', 'jenis_kegiatan', 'jenis_petugas'];

        foreach ($filterKeys as $key) {
            if ($request->has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all filter parameters from request
     */
    private function getFilterParams(Request $request): array
    {
        return $request->only([
            'search',
            'status',
            'tahun',
            'bulan',
            'jenis',
            'jenis_kegiatan',
            'jenis_petugas',
        ]);
    }
}
