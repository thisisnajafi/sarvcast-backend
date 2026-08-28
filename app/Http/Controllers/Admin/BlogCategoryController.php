<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    public function apiIndex(Request $request)
    {
        $query = BlogCategory::query()->withCount('posts');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginator = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiStore(Request $request)
    {
        $category = BlogCategory::create($this->validated($request));

        return AdminApiResponse::success($category, 'دسته‌بندی وبلاگ ایجاد شد.', 201);
    }

    public function apiShow(BlogCategory $blogCategory)
    {
        return AdminApiResponse::success($blogCategory->loadCount('posts'));
    }

    public function apiUpdate(Request $request, BlogCategory $blogCategory)
    {
        $blogCategory->update($this->validated($request, $blogCategory->id));

        return AdminApiResponse::success($blogCategory->fresh(), 'دسته‌بندی وبلاگ به‌روزرسانی شد.');
    }

    public function apiDestroy(BlogCategory $blogCategory)
    {
        $blogCategory->delete();

        return AdminApiResponse::okMessage('دسته‌بندی وبلاگ حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('blog_categories', 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:180'],
            'focus_keyword' => ['nullable', 'string', 'max:80'],
            'og_image_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = array_key_exists('is_active', $validated)
            ? (bool) $validated['is_active']
            : true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
