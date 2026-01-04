<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Transform stories for API response
     */
    private function transformStories($stories)
    {
        foreach ($stories as $story) {
            // Duration is already in seconds in database, no conversion needed
            // The API response should match the Flutter documentation format
        }
        return $stories;
    }
    /**
     * Get all active categories that have stories
     */
    public function index(Request $request)
    {
        $query = Category::active()
            ->ordered()
            ->whereHas('stories', function($q) {
                $q->where('status', 'published');
            })
            ->withCount(['stories' => function($q) {
                $q->where('status', 'published');
            }]);

        // Apply limit if specified (default to no limit to return all categories)
        $limit = $request->get('limit');
        if ($limit && $limit > 0) {
            $query->limit($limit);
        }

        // Apply pagination if page is specified
        if ($request->has('page')) {
            $categories = $query->paginate($request->get('per_page', 20));
            
            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories->items(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total()
                ]
            ]);
        }

        $categories = $query->get();

        // Transform categories to match API specification
        $transformedCategories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'color' => $category->color,
                'status' => $category->is_active ? 'active' : 'inactive',
                'order' => $category->sort_order,
                'story_count' => $category->stories_count,
                'icon_path' => $category->icon_path,
                'image_url' => $category->image_url,
                'created_at' => $category->created_at->toISOString(),
                'updated_at' => $category->updated_at->toISOString()
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $transformedCategories
        ]);
    }

    /**
     * Get stories in a category
     */
    public function stories(Category $category, Request $request)
    {
        $query = $category->stories()->with(['category', 'narrator', 'author', 'director', 'people'])->published();

        // Apply filters
        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        // Apply sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->orderBy('play_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            default:
                $query->latest();
        }

        // Apply limit if specified
        $limit = $request->get('limit', 6);
        if ($limit > 0) {
            $query->limit($limit);
        }

        // Apply pagination if page is specified
        if ($request->has('page')) {
            $stories = $query->paginate($request->get('per_page', 20));
            
            return response()->json([
                'success' => true,
                'message' => 'Category stories retrieved successfully',
                'data' => $this->transformStories($stories->items()),
                'pagination' => [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total()
                ]
            ]);
        }

        $stories = $query->get();

        // Transform stories to match API specification
        $transformedStories = $stories->map(function ($story) {
            // Only include episodes that have timelines
            $episodeIds = $story->episodes()
                ->whereHas('imageTimelines')
                ->pluck('id')
                ->toArray();
            
            return [
                'id' => $story->id,
                'title' => $story->title,
                'subtitle' => $story->subtitle,
                'description' => $story->description,
                'category_id' => $story->category_id,
                'age_group' => $story->age_group,
                'duration' => $story->duration * 60, // Convert minutes to seconds
                'status' => $story->status,
                'is_premium' => $story->is_premium,
                'is_completely_free' => $story->is_completely_free ?? true,
                'play_count' => $story->play_count ?? 0,
                'rating' => $story->rating ?? 0.0,
                'rating_count' => $story->rating_count ?? 0,
                'favorite_count' => $story->favorite_count ?? 0,
                'episode_count' => count($episodeIds),
                'created_at' => $story->created_at->toISOString(),
                'updated_at' => $story->updated_at->toISOString(),
                'category' => $story->category ? [
                    'id' => $story->category->id,
                    'name' => $story->category->name,
                    'slug' => $story->category->slug,
                    'description' => $story->category->description,
                    'color' => $story->category->color,
                    'status' => $story->category->status,
                    'order' => $story->category->order,
                    'story_count' => $story->category->story_count,
                    'icon_path' => $story->category->icon_path,
                    'created_at' => $story->category->created_at->toISOString(),
                    'updated_at' => $story->category->updated_at->toISOString()
                ] : null,
                'narrator' => $story->narrator ? [
                    'id' => $story->narrator->id,
                    'name' => $story->narrator->name,
                    'bio' => $story->narrator->bio,
                    'image_url' => $story->narrator->image_url ?? null,
                    'roles' => $story->narrator->roles ?? [],
                    'total_stories' => $story->narrator->total_stories ?? 0,
                    'total_episodes' => $story->narrator->total_episodes ?? 0,
                    'average_rating' => $story->narrator->average_rating ?? 0.0,
                    'is_verified' => $story->narrator->is_verified ?? false,
                    'last_active_at' => $story->narrator->last_active_at?->toISOString(),
                    'created_at' => $story->narrator->created_at->toISOString()
                ] : null,
                'image_url' => $story->image_url ?? null,
                'cover_image_url' => $story->cover_image_url ?? null,
                'total_episodes' => count($episodeIds),
                'free_episodes' => $story->episodes()->whereHas('imageTimelines')->where('is_premium', false)->count(),
                'episode_ids' => $episodeIds,
                'is_favorite' => false, // Will be set based on user authentication
                'progress' => 0.0, // Will be set based on user progress
                'tags' => $story->tags ?? [],
                'language' => $story->language ?? 'fa'
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Category stories retrieved successfully',
            'data' => $transformedStories
        ]);
    }
}
