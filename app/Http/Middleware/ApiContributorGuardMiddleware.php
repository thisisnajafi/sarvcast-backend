<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ContributorStoryAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict non-admin contributors to stories + story-editor script surfaces only.
 */
class ApiContributorGuardMiddleware
{
    public function __construct(
        private readonly ContributorStoryAccessService $access,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        if ($this->access->isFullAdmin($user)) {
            return $next($request);
        }

        $segment = (string) $request->segment(3); // /api/admin/{segment}
        $path = trim($request->path(), '/');
        $method = strtoupper($request->method());

        if (! in_array($segment, ['stories', 'story-editor'], true)) {
            return $this->forbidden('دسترسی به این بخش فقط برای مدیران است.');
        }

        if ($segment === 'stories') {
            return $this->guardStories($request, $next, $method, $path);
        }

        return $this->guardStoryEditor($request, $next, $user, $method, $path);
    }

    private function guardStories(Request $request, Closure $next, string $method, string $path): Response
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $this->forbidden('شما فقط مجاز به مشاهده داستان‌ها هستید.');
        }

        if (str_contains($path, '/export') || str_contains($path, '/bulk-action') || str_contains($path, '/statistics')) {
            return $this->forbidden('این عملیات برای حساب شما مجاز نیست.');
        }

        return $next($request);
    }

    private function guardStoryEditor(Request $request, Closure $next, User $user, string $method, string $path): Response
    {
        $deniedSubstrings = [
            '/package',
            '/assets',
            '/import',
            '/image',
        ];

        foreach ($deniedSubstrings as $needle) {
            if (str_contains($path, $needle)) {
                return $this->forbidden('دسترسی به بسته تولید و دارایی‌ها فقط برای مدیران است.');
            }
        }

        // Creating scaffolds is admin-only
        if ($method === 'POST' && (str_ends_with($path, '/story-editor/stories') || preg_match('#/story-editor/stories/[^/]+/episodes$#', $path))) {
            return $this->forbidden('ایجاد داستان/قسمت در ویرایشگر فقط برای مدیران است.');
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            // Script update: /api/admin/story-editor/stories/{slug}/episodes/{episode}
            if (! preg_match('#story-editor/stories/([^/]+)/episodes/([^/]+)$#', $path, $m)) {
                return $this->forbidden('ویرایش این بخش برای حساب شما مجاز نیست.');
            }

            $storySlug = urldecode($m[1]);
            if (! $this->access->canEditEditorScript($user, $storySlug)) {
                return $this->forbidden('فقط نویسنده داستان می‌تواند اسکریپت را ویرایش کند.');
            }

            return $next($request);
        }

        if (! in_array($method, ['GET', 'HEAD'], true)) {
            return $this->forbidden('این عملیات برای حساب شما مجاز نیست.');
        }

        return $next($request);
    }

    private function forbidden(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'FORBIDDEN',
        ], 403);
    }
}
