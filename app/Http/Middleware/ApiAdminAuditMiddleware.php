<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminAuditMiddleware
{
    /**
     * Log write actions on admin APIs for traceability.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            Log::info('Admin API write action', [
                'user_id' => optional(auth('sanctum')->user())->id,
                'role' => optional(auth('sanctum')->user())->role,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route_name' => optional($request->route())->getName(),
                'ip' => $request->ip(),
                'status_code' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}

