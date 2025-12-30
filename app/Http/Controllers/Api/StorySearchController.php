<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StorySearchController extends Controller
{
    /**
     * Search stories for admin panel
     */
    public function searchStories(Request $request): JsonResponse
    {
        // Log the incoming request
        \Log::info('🔍 StorySearchController.searchStories - Request received', [
            'query' => $request->input('query'),
            'limit' => $request->input('limit'),
            'all_params' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:100',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            \Log::warning('❌ StorySearchController.searchStories - Validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $query = $request->input('query');
        $limit = $request->input('limit', 20);

        $storiesQuery = Story::with(['category', 'author', 'narrator'])
            ->withCount('episodes')
            ->withSum('episodes', 'play_count');

        // Search by title, subtitle, or description
        $storiesQuery->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
              ->orWhere('subtitle', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        });

        $stories = $storiesQuery->select([
            'id',
            'title',
            'subtitle',
            'description',
            'image_url',
            'cover_image_url',
            'category_id',
            'is_premium',
            'status',
            'workflow_status',
            'created_at',
            'updated_at'
        ])
        ->limit($limit)
        ->get();

        $formattedStories = $stories->map(function ($story) {
            return [
                'id' => $story->id,
                'title' => $story->title,
                'subtitle' => $story->subtitle,
                'description' => $story->description,
                'image_url' => $story->image_url,
                'cover_image_url' => $story->cover_image_url,
                'category' => $story->category ? [
                    'id' => $story->category->id,
                    'name' => $story->category->name,
                    'slug' => $story->category->slug,
                ] : null,
                'is_premium' => $story->is_premium,
                'status' => $story->status,
                'workflow_status' => $story->workflow_status,
                'episodes_count' => $story->episodes_count ?? 0,
                'play_count' => $story->episodes_sum_play_count ?? 0,
                'author' => $story->author ? [
                    'id' => $story->author->id,
                    'name' => trim(($story->author->first_name ?? '') . ' ' . ($story->author->last_name ?? '')),
                ] : null,
                'narrator' => $story->narrator ? [
                    'id' => $story->narrator->id,
                    'name' => trim(($story->narrator->first_name ?? '') . ' ' . ($story->narrator->last_name ?? '')),
                ] : null,
                'created_at' => $story->created_at->format('Y/m/d'),
                'updated_at' => $story->updated_at->format('Y/m/d'),
            ];
        });

        $response = [
            'success' => true,
            'data' => [
                'stories' => $formattedStories,
                'total' => $formattedStories->count(),
                'query' => $query,
            ],
        ];

        // Log the response
        \Log::info('✅ StorySearchController.searchStories - Response sent', [
            'query' => $query,
            'stories_found' => $formattedStories->count(),
            'response' => $response,
        ]);

        $jsonResponse = response()->json($response);
        // Disable caching for search results - they need to be retrieved immediately
        $jsonResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $jsonResponse->headers->set('Pragma', 'no-cache');
        $jsonResponse->headers->set('Expires', '0');
        return $jsonResponse;
    }
}

