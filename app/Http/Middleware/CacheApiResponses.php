<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = 300): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Generate cache key based on request
        $cacheKey = $this->generateCacheKey($request);

        // Check if response is cached
        if (Cache::has($cacheKey)) {
            $cachedResponse = response()->json(Cache::get($cacheKey));
            $cachedResponse->headers->set('Cache-Control', "public, max-age={$ttl}");
            $cachedResponse->headers->set('X-Cache', 'HIT');
            return $cachedResponse;
        }

        // Process request
        $response = $next($request);

        // Cache successful responses
        if ($response->getStatusCode() === 200) {
            $responseData = json_decode($response->getContent(), true);
            if ($responseData) {
                Cache::put($cacheKey, $responseData, $ttl);
            }
        }

        // Set cache headers for the response
        $response->headers->set('Cache-Control', "public, max-age={$ttl}");
        $response->headers->set('X-Cache', 'MISS');

        return $response;
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey(Request $request): string
    {
        $key = 'api_' . $request->path();
        
        // Add query parameters to key
        $queryParams = $request->query();
        if (!empty($queryParams)) {
            ksort($queryParams);
            $key .= '_' . md5(serialize($queryParams));
        }

        // Add user ID if authenticated
        if ($request->user()) {
            $key .= '_user_' . $request->user()->id;
        }

        return $key;
    }
}