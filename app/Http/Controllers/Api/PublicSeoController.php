<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\SeoRedirect;
use App\Models\SeoSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSeoController extends Controller
{
    public function robots(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'content' => SeoSetting::getValue(SeoSetting::ROBOTS_KEY, ''),
            ],
        ]);
    }

    public function redirects(): JsonResponse
    {
        $rows = SeoRedirect::query()
            ->active()
            ->orderBy('id')
            ->get(['source', 'destination', 'status_code']);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function blogIndex(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);
        $paginator = BlogPost::query()
            ->published()
            ->where('noindex', false)
            ->with(['category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'lastPage' => $paginator->lastPage(),
            ],
        ]);
    }

    public function blogShow(string $slug): JsonResponse
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->with(['category:id,name,slug', 'tags:id,name,slug'])
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'مقاله پیدا نشد.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }
}
