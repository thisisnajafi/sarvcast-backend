<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyApiDeprecationHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $sunset = env('ADMIN_LEGACY_API_SUNSET_AT', '2026-12-31T23:59:59Z');
        $docsUrl = env('ADMIN_LEGACY_API_MIGRATION_DOC_URL', '');

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', $sunset);
        $response->headers->set(
            'Warning',
            '299 - "Deprecated API namespace. Migrate to /api/admin/v1/* canonical routes."'
        );

        if ($docsUrl !== '') {
            $response->headers->set('Link', sprintf('<%s>; rel="deprecation"', $docsUrl));
        }

        return $response;
    }
}
