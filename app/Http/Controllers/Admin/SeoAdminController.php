<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\BlogPost;
use App\Models\SeoRedirect;
use App\Models\SeoSetting;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SeoAdminController extends Controller
{
    public function redirectsIndex(Request $request)
    {
        $query = SeoRedirect::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 30), 1), 100);
        $paginator = $query->orderByDesc('id')->paginate($perPage);

        return AdminApiResponse::paginated($paginator);
    }

    public function redirectsStore(Request $request)
    {
        $validated = $this->validatedRedirect($request);
        $redirect = SeoRedirect::create($validated);

        return AdminApiResponse::success($redirect, 'ریدایرکت ذخیره شد.', 201);
    }

    public function redirectsUpdate(Request $request, SeoRedirect $seoRedirect)
    {
        $seoRedirect->update($this->validatedRedirect($request, $seoRedirect->id));

        return AdminApiResponse::success($seoRedirect->fresh(), 'ریدایرکت به‌روزرسانی شد.');
    }

    public function redirectsDestroy(SeoRedirect $seoRedirect)
    {
        $seoRedirect->delete();

        return AdminApiResponse::okMessage('ریدایرکت حذف شد.');
    }

    public function robotsShow()
    {
        return AdminApiResponse::success([
            'content' => SeoSetting::getValue(SeoSetting::ROBOTS_KEY, ''),
            'updated_at' => SeoSetting::query()->where('key', SeoSetting::ROBOTS_KEY)->value('updated_at'),
        ]);
    }

    public function robotsUpdate(Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
        ]);

        $row = SeoSetting::putValue(SeoSetting::ROBOTS_KEY, $validated['content']);

        return AdminApiResponse::success([
            'content' => $row->value,
            'updated_at' => $row->updated_at,
        ], 'robots.txt ذخیره شد.');
    }

    public function overview()
    {
        $duplicateTitles = BlogPost::query()
            ->select('seo_title', DB::raw('COUNT(*) as total'))
            ->whereNotNull('seo_title')
            ->where('seo_title', '!=', '')
            ->groupBy('seo_title')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $missingMeta = BlogPost::query()
            ->where(function ($q) {
                $q->whereNull('meta_description')->orWhere('meta_description', '');
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'title', 'slug', 'status']);

        $thin = BlogPost::query()
            ->where('word_count', '<', 300)
            ->orderBy('word_count')
            ->limit(20)
            ->get(['id', 'title', 'slug', 'word_count', 'status']);

        return AdminApiResponse::success([
            'indexed_pages_estimate' => [
                'published_blog_posts' => BlogPost::query()->published()->count(),
                'published_stories' => class_exists(Story::class)
                    ? Story::query()->where('status', 'published')->count()
                    : 0,
                'active_redirects' => SeoRedirect::query()->active()->count(),
            ],
            'sitemap' => [
                'status' => 'build_time',
                'last_generated_at' => null,
                'note' => 'سایت لندینگ به‌صورت static export ساخته می‌شود؛ sitemap در زمان build تولید می‌شود، نه از این داشبورد.',
            ],
            'search_console' => [
                'connected' => false,
                'top_pages' => [],
                'crawl_errors' => [],
                'note' => 'اتصال دستی است. مراحل: manji-front/docs/SEO_MANUAL_ACTIONS.md بخش ۱. این داشبورد هنوز به API سرچ کنسول وصل نیست.',
            ],
            'content_issues' => [
                'missing_meta_descriptions' => $missingMeta,
                'duplicate_titles' => $duplicateTitles,
                'thin_content' => $thin,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedRedirect(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:500', Rule::unique('seo_redirects', 'source')->ignore($id)],
            'destination' => ['required', 'string', 'max:500'],
            'status_code' => ['nullable', Rule::in([301, 302])],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['source'] = SeoRedirect::normalizePath($validated['source']);
        $validated['destination'] = str_starts_with($validated['destination'], 'http')
            ? $validated['destination']
            : SeoRedirect::normalizePath($validated['destination']);
        $validated['status_code'] = (int) ($validated['status_code'] ?? 301);
        $validated['is_active'] = array_key_exists('is_active', $validated)
            ? (bool) $validated['is_active']
            : true;

        return $validated;
    }
}
