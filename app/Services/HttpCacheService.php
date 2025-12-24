<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpCacheService
{
    /**
     * Add cache headers ke response
     */
    public static function addHeaders(Response $response, Request $request, int $maxAge = 300): Response
    {
        // Hanya cache GET requests
        if ($request->method() !== 'GET') {
            return $response;
        }

        // Generate ETag dari response content
        $content = $response->getContent();
        $etag = '"'.md5($content).'"';

        // Add cache headers
        $response->header('Cache-Control', "public, max-age={$maxAge}, must-revalidate");
        $response->header('ETag', $etag);
        $response->header('Vary', 'Accept-Encoding');

        // Check If-None-Match header (client cache validation)
        if ($request->header('If-None-Match') === $etag) {
            // Client has current version, return 304 Not Modified
            $response->setStatusCode(304);
            $response->setContent('');
        }

        return $response;
    }

    /**
     * Add no-cache headers untuk sensitive pages
     */
    public static function addNoCacheHeaders(Response $response): Response
    {
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        
        return $response;
    }

    /**
     * Add short-term cache untuk frequently accessed data
     */
    public static function addShortTermCache(Response $response): Response
    {
        return self::addHeaders($response, request(), 60);
    }

    /**
     * Add medium-term cache untuk semi-static data
     */
    public static function addMediumTermCache(Response $response): Response
    {
        return self::addHeaders(response(), request(), 600);
    }

    /**
     * Add long-term cache untuk static data
     */
    public static function addLongTermCache(Response $response): Response
    {
        return self::addHeaders(response(), request(), 3600);
    }
}
