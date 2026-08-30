<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ContributorStoryAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict non-admin contributors to allowed panel surfaces only.
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

        if ($segment === 'resumes') {
            $method = strtoupper($request->method());
            if (! in_array($method, ['GET', 'HEAD', 'PUT', 'PATCH'], true)) {
                return $this->forbidden('این عملیات برای حساب شما مجاز نیست.');
            }

            return $next($request);
        }
        $path = trim($request->path(), '/');
        $method = strtoupper($request->method());

        if ($this->access->isHeadWriter($user)) {
            return $this->guardHeadWriter($request, $next, $user, $method, $path, $segment);
        }

        if ($this->access->isImageAssistantStaff($user)) {
            if ($segment === 'story-editor' && ($user->isWriter() || $user->isVoiceActor() || $this->access->isWriterStaff($user))) {
                return $this->guardStoryEditor($request, $next, $user, $method, $path);
            }

            return $this->guardImageAssistant($request, $next, $user, $method, $path, $segment);
        }

        if (! in_array($segment, ['stories', 'story-editor'], true)) {
            return $this->forbidden('دسترسی به این بخش فقط برای مدیران است.');
        }

        if ($segment === 'stories') {
            return $this->guardStories($request, $next, $method, $path);
        }

        return $this->guardStoryEditor($request, $next, $user, $method, $path);
    }

    private function guardHeadWriter(Request $request, Closure $next, User $user, string $method, string $path, string $segment): Response
    {
        if ($segment === 'writers') {
            if (! in_array($method, ['GET', 'HEAD'], true)) {
                return $this->forbidden('این عملیات برای حساب شما مجاز نیست.');
            }

            return $next($request);
        }

        if ($segment === 'stories') {
            if (
                ($method === 'POST' || $method === 'DELETE')
                && preg_match('#/stories/[^/]+/author$#', $path)
            ) {
                return $next($request);
            }

            return $this->guardStories($request, $next, $method, $path);
        }

        if ($segment === 'story-editor') {
            return $this->guardStoryEditor($request, $next, $user, $method, $path);
        }

        return $this->forbidden('دسترسی به این بخش فقط برای مدیران است.');
    }

    private function guardImageAssistant(Request $request, Closure $next, User $user, string $method, string $path, string $segment): Response
    {
        if ($segment === 'stories') {
            if (
                in_array($method, ['GET', 'HEAD'], true)
                && preg_match('#/stories/[^/]+/(production-assets|image-assistants)$#', $path)
            ) {
                return $next($request);
            }

            // Image assistants may not assign/revoke assistants or mutate stories.
            if (preg_match('#/stories/[^/]+/(author|image-assistants|sponsor)$#', $path)) {
                return $this->forbidden('این عملیات فقط برای مدیران است.');
            }

            return $this->guardStories($request, $next, $method, $path);
        }

        if ($segment === 'episodes') {
            if (! in_array($method, ['GET', 'HEAD'], true)) {
                return $this->forbidden('شما فقط مجاز به مشاهده قسمت‌ها هستید.');
            }

            if (str_contains($path, '/export') || str_contains($path, '/bulk-action') || str_contains($path, '/statistics')) {
                return $this->forbidden('این عملیات برای حساب شما مجاز نیست.');
            }

            return $next($request);
        }

        if ($segment === 'timeline-management') {
            if (str_contains($path, '/export') || str_contains($path, '/statistics')) {
                return $this->forbidden('این عملیات برای حساب شما مجاز نیست.');
            }

            return $next($request);
        }

        if ($segment === 'media') {
            if ($method === 'GET' || $method === 'HEAD') {
                if (str_contains($path, '/import-legacy') || str_contains($path, '/statistics') || str_contains($path, '/bulk-action')) {
                    return $this->forbidden('این عملیات برای حساب شما مجاز نیست.');
                }

                return $next($request);
            }

            if ($method === 'POST' && (str_ends_with($path, '/media') || preg_match('#/media/?$#', $path))) {
                return $next($request);
            }

            return $this->forbidden('این عملیات رسانه برای حساب شما مجاز نیست.');
        }

        return $this->forbidden('دسترسی به این بخش فقط برای مدیران است.');
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

        // Restore from backup: authors of the story may restore
        if ($method === 'POST' && preg_match('#story-editor/stories/([^/]+)/episodes/([^/]+)/backups/([^/]+)/restore$#', $path, $m)) {
            $storySlug = urldecode($m[1]);
            if (! $this->access->canEditEditorScript($user, $storySlug)) {
                return $this->forbidden('فقط نویسنده داستان می‌تواند اسکریپت را بازیابی کند.');
            }

            return $next($request);
        }

        // Backup delete is admin-only (single or bulk)
        if (
            ($method === 'DELETE' && preg_match('#story-editor/stories/[^/]+/episodes/[^/]+/backups/[^/]+$#', $path))
            || ($method === 'POST' && preg_match('#story-editor/stories/[^/]+/episodes/[^/]+/backups/delete$#', $path))
        ) {
            return $this->forbidden('حذف نسخه پشتیبان فقط برای مدیران مجاز است.');
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
