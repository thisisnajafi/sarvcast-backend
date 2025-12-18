<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StoryRatingController extends Controller
{
    /**
     * Get story ratings
     */
    public function index(Request $request, Story $story)
    {
        $query = $story->storyRatings()->with('user');

        // Filter by rating value
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by reviews only
        if ($request->boolean('reviews_only')) {
            $query->whereNotNull('review');
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $ratings = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Story ratings retrieved successfully',
            'data' => $ratings->items(),
            'pagination' => [
                'current_page' => $ratings->currentPage(),
                'last_page' => $ratings->lastPage(),
                'per_page' => $ratings->perPage(),
                'total' => $ratings->total()
            ]
        ]);
    }

    /**
     * Get user's rating for a story
     */
    public function show(Request $request, Story $story)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $rating = StoryRating::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User rating retrieved successfully',
            'data' => $rating
        ]);
    }

    /**
     * Create or update story rating
     */
    public function store(Request $request, Story $story)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:0|max:5',
            'review' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already rated this story
        $existingRating = StoryRating::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->first();

        if ($existingRating) {
            // Update existing rating
            $existingRating->update([
                'rating' => $request->rating,
                'review' => $request->review
            ]);
            $rating = $existingRating;
            $message = 'Rating updated successfully';
        } else {
            // Create new rating
            $rating = StoryRating::create([
                'user_id' => $user->id,
                'story_id' => $story->id,
                'rating' => $request->rating,
                'review' => $request->review
            ]);
            $message = 'Rating created successfully';
        }

        // Update story's average rating and rating count
        $this->updateStoryRatingStats($story);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $rating
        ]);
    }

    /**
     * Delete user's rating for a story
     */
    public function destroy(Request $request, Story $story)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $rating = StoryRating::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found'
            ], 404);
        }

        $rating->delete();

        // Update story's average rating and rating count
        $this->updateStoryRatingStats($story);

        return response()->json([
            'success' => true,
            'message' => 'Rating deleted successfully'
        ]);
    }

    /**
     * Get story rating statistics
     */
    public function statistics(Story $story)
    {
        $totalRatings = $story->storyRatings()->count();
        $averageRating = $story->storyRatings()->avg('rating') ?? 0;
        $ratingDistribution = $story->storyRatings()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get()
            ->pluck('count', 'rating')
            ->toArray();

        // Fill missing ratings with 0
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($ratingDistribution[$i])) {
                $ratingDistribution[$i] = 0;
            }
        }

        $reviewsCount = $story->storyRatings()->whereNotNull('review')->count();

        return response()->json([
            'success' => true,
            'message' => 'Story rating statistics retrieved successfully',
            'data' => [
                'total_ratings' => $totalRatings,
                'average_rating' => round($averageRating, 1),
                'rating_distribution' => $ratingDistribution,
                'reviews_count' => $reviewsCount
            ]
        ]);
    }

    /**
     * Update story rating statistics
     */
    private function updateStoryRatingStats(Story $story)
    {
        $totalRatings = $story->storyRatings()->count();
        $averageRating = $story->storyRatings()->avg('rating') ?? 0;

        $story->update([
            'total_ratings' => $totalRatings,
            'avg_rating' => round($averageRating, 2)
        ]);
    }
}