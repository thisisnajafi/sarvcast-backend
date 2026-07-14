<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminAuditMiddleware
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * Log write actions on admin APIs for traceability (DB + file fallback).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $user = auth('sanctum')->user();

        Log::info('Admin API write action', [
            'user_id' => $user?->id,
            'role' => $user?->role,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'status_code' => $response->getStatusCode(),
            'request_id' => $request->header('X-Request-Id'),
            'validation_errors' => $response->getStatusCode() === 422
                ? $this->extractValidationErrors($response)
                : null,
        ]);

        try {
            $this->activityLog->recordAdminHttpAction($request, $response);
        } catch (\Throwable $e) {
            Log::error('Failed to queue admin activity log: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return $response;
    }

    /**
     * @return array<string, array<int, string>>|null
     */
    private function extractValidationErrors(Response $response): ?array
    {
        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) || ! isset($decoded['errors']) || ! is_array($decoded['errors'])) {
            return null;
        }

        return $decoded['errors'];
    }
}
