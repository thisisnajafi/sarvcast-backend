<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    public function apiIndex(Request $request)
    {
        $query = BlogPost::query()->with(['category:id,name,slug', 'tags:id,name,slug']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('focus_keyword', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($request->filled('blog_category_id')) {
            $query->where('blog_category_id', $request->integer('blog_category_id'));
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginator = $query->orderByDesc('updated_at')->paginate($perPage);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiStore(Request $request)
    {
        $validated = $this->validated($request);
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $validated['author_id'] = $validated['author_id'] ?? $request->user()?->id;

        $post = BlogPost::create($validated);
        $post->tags()->sync($tagIds);

        return AdminApiResponse::success($post->fresh(['category', 'tags']), 'مقاله ذخیره شد.', 201);
    }

    public function apiShow(BlogPost $blogPost)
    {
        return AdminApiResponse::success($blogPost->load(['category', 'tags', 'author:id,first_name,last_name']));
    }

    public function apiUpdate(Request $request, BlogPost $blogPost)
    {
        $validated = $this->validated($request, $blogPost->id);
        $tagIds = $validated['tag_ids'] ?? null;
        unset($validated['tag_ids']);

        $blogPost->update($validated);
        if (is_array($tagIds)) {
            $blogPost->tags()->sync($tagIds);
        }

        return AdminApiResponse::success($blogPost->fresh(['category', 'tags']), 'مقاله به‌روزرسانی شد.');
    }

    public function apiDestroy(BlogPost $blogPost)
    {
        $blogPost->delete();

        return AdminApiResponse::okMessage('مقاله حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:191', Rule::unique('blog_posts', 'slug')->ignore($id)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:180'],
            'focus_keyword' => ['nullable', 'string', 'max:80'],
            'secondary_keywords' => ['nullable', 'array', 'max:5'],
            'secondary_keywords.*' => ['string', 'max:80'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:110'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image_url' => ['nullable', 'string', 'max:500'],
            'twitter_card' => ['nullable', Rule::in(['summary', 'summary_large_image'])],
            'noindex' => ['nullable', 'boolean'],
            'schema_type' => ['nullable', Rule::in(BlogPost::SCHEMA_TYPES)],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:200'],
            'faqs.*.answer' => ['nullable', 'string', 'max:2000'],
            'how_to_steps' => ['nullable', 'array'],
            'how_to_steps.*.name' => ['nullable', 'string', 'max:120'],
            'how_to_steps.*.text' => ['nullable', 'string', 'max:2000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:blog_tags,id'],
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['twitter_card'] = $validated['twitter_card'] ?? 'summary_large_image';
        $validated['schema_type'] = $validated['schema_type'] ?? 'Article';
        $validated['noindex'] = (bool) ($validated['noindex'] ?? false);

        if (! empty($validated['faqs'])) {
            $validated['faqs'] = array_values(array_filter(
                $validated['faqs'],
                fn ($row) => filled($row['question'] ?? null) && filled($row['answer'] ?? null)
            ));
        }

        if (! empty($validated['how_to_steps'])) {
            $validated['how_to_steps'] = array_values(array_filter(
                $validated['how_to_steps'],
                fn ($row) => filled($row['name'] ?? null) && filled($row['text'] ?? null)
            ));
        }

        return $validated;
    }
}
