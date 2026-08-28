<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogTagController extends Controller
{
    public function apiIndex(Request $request)
    {
        $query = BlogTag::query()->withCount('posts');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginator = $query->orderBy('name')->paginate($perPage);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiStore(Request $request)
    {
        $tag = BlogTag::create($this->validated($request));

        return AdminApiResponse::success($tag, 'برچسب ایجاد شد.', 201);
    }

    public function apiShow(BlogTag $blogTag)
    {
        return AdminApiResponse::success($blogTag->loadCount('posts'));
    }

    public function apiUpdate(Request $request, BlogTag $blogTag)
    {
        $blogTag->update($this->validated($request, $blogTag->id));

        return AdminApiResponse::success($blogTag->fresh(), 'برچسب به‌روزرسانی شد.');
    }

    public function apiDestroy(BlogTag $blogTag)
    {
        $blogTag->delete();

        return AdminApiResponse::okMessage('برچسب حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('blog_tags', 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:180'],
            'focus_keyword' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = array_key_exists('is_active', $validated)
            ? (bool) $validated['is_active']
            : true;

        return $validated;
    }
}
