<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAllowedAdminOrigin
{
    /**
     * Enforce allowlisted browser origins for admin dashboard APIs.
     * This is intentionally optional via env flag to avoid breaking
     * local scripts and non-browser clients during migration.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enforce = filter_var(env('ADMIN_DASHBOARD_ENFORCE_ORIGIN', false), FILTER_VALIDATE_BOOL);
        if (!$enforce) {
            return $next($request);
        }

        $origin = (string) $request->headers->get('Origin', '');
        $referer = (string) $request->headers->get('Referer', '');
        $source = $origin !== '' ? $origin : $referer;

        if ($source === '') {
            return $next($request);
        }

        $sourceHost = parse_url($source, PHP_URL_HOST);
        if (!$sourceHost) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request origin.',
                'error' => 'INVALID_ORIGIN',
            ], 403);
        }

        $allowedHosts = collect([
            env('ADMIN_DASHBOARD_URL'),
            env('APP_URL'),
        ])->filter()->map(function ($url) {
            return parse_url((string) $url, PHP_URL_HOST);
        })->filter()->values()->all();

        if (!in_array($sourceHost, $allowedHosts, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Origin is not allowed for admin APIs.',
                'error' => 'ORIGIN_NOT_ALLOWED',
            ], 403);
        }

        return $next($request);
    }
}

