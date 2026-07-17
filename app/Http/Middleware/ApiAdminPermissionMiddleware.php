<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ContributorStoryAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminPermissionMiddleware
{
    /**
     * Enforce permission checks for write operations on admin APIs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication is required.',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Non-admin contributors: object-level rules live in api.contributor.
        if ($user instanceof User && ! app(ContributorStoryAccessService::class)->isFullAdmin($user)) {
            return $next($request);
        }

        $segment = (string) $request->segment(3); // /api/admin/{segment}/...
        $action = $this->resolveAction($request->method(), $request->path());
        $permission = $this->resolvePermission($segment, $action);

        if ($permission === null) {
            return $next($request);
        }

        $hasPermission = method_exists($user, 'hasPermission') && $user->hasPermission($permission);
        if (! $hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission for this operation.',
                'error' => 'FORBIDDEN',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }

    private function resolveAction(string $method, string $path): string
    {
        if (str_contains($path, '/bulk-action')) {
            return 'bulk';
        }

        if (str_contains($path, '/test-send')) {
            return 'send_test';
        }

        if (str_contains($path, '/preview')) {
            return 'read';
        }

        if (str_contains($path, '/dispatch') || str_contains($path, '/cancel')) {
            return 'send';
        }

        if (str_contains($path, '/export') || str_contains($path, '/download')) {
            return 'export';
        }

        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'read',
        };
    }

    private function resolvePermission(string $segment, string $action): ?string
    {
        $resource = match ($segment) {
            'user-analytics' => 'analytics.users',
            'revenue-analytics' => 'analytics.revenue',
            'system-analytics' => 'analytics.system',
            'content-moderation' => 'moderation.content',
            'performance-monitoring' => 'performance.monitoring',
            'timeline-management' => 'timeline.management',
            'audio-management' => 'audio.management',
            'file-upload' => 'files.upload',
            'story-editor' => 'story_editor',
            default => str_replace('-', '.', $segment),
        };

        if ($resource === '') {
            return null;
        }

        return $resource.'.'.$action;
    }
}
