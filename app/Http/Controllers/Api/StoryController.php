<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Favorite;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoryController extends Controller
{
    /**
     * Get paginated list of stories
     */
    public function index(Request $request)
    {
        $query = Story::with(['category', 'director', 'narrator'])
            ->published();

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
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

        $stories = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'stories' => $stories->items(),
                'pagination' => [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total()
                ]
            ]
        ]);
    }

    /**
     * Get detailed story information
     */
    public function show(Story $story)
    {
        $story->load(['category', 'director', 'writer', 'author', 'narrator', 'episodes', 'people']);
        
        // Check if user has favorited this story
        $isFavorite = false;
        if (Auth::check()) {
            $isFavorite = Favorite::where('user_id', Auth::id())
                ->where('story_id', $story->id)
                ->exists();
        }

        // Get user's rating if authenticated
        $userRating = null;
        if (Auth::check()) {
            $rating = Rating::where('user_id', Auth::id())
                ->where('story_id', $story->id)
                ->first();
            $userRating = $rating ? $rating->rating : null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'story' => $story,
                'is_favorite' => $isFavorite,
                'user_rating' => $userRating
            ]
        ]);
    }

    /**
     * Get episodes for a specific story
     */
    public function episodes(Story $story, Request $request)
    {
        $query = $story->episodes()->published();

        // Filter by premium status if user doesn't have active subscription
        if (Auth::check() && !Auth::user()->hasActiveSubscription()) {
            $query->where('is_premium', false);
        }

        $episodes = $query->orderBy('episode_number')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'episodes' => $episodes
            ]
        ]);
    }

    /**
     * Add story to favorites
     */
    public function addFavorite(Story $story)
    {
        $user = Auth::user();

        // Check if already favorited
        if (Favorite::where('user_id', $user->id)->where('story_id', $story->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Story is already in favorites'
            ], 400);
        }

        Favorite::create([
            'user_id' => $user->id,
            'story_id' => $story->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Story added to favorites'
        ]);
    }

    /**
     * Remove story from favorites
     */
    public function removeFavorite(Story $story)
    {
        $user = Auth::user();

        Favorite::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Story removed from favorites'
        ]);
    }

    /**
     * Rate a story
     */
    public function rate(Story $story, Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();

        // Update or create rating
        Rating::updateOrCreate(
            [
                'user_id' => $user->id,
                'story_id' => $story->id
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review
            ]
        );

        // Update story average rating
        $avgRating = Rating::where('story_id', $story->id)->avg('rating');
        $story->update(['rating' => round($avgRating, 2)]);

        return response()->json([
            'success' => true,
            'message' => 'Story rated successfully'
        ]);
    }
}
