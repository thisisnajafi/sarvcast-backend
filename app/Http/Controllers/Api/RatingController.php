<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Story;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RatingController extends Controller
{
    /**
     * Get user's ratings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $ratings = Rating::getUserRatings($user->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'ratings' => $ratings->items(),
                    'pagination' => [
                        'current_page' => $ratings->currentPage(),
                        'last_page' => $ratings->lastPage(),
                        'per_page' => $ratings->perPage(),
                        'total' => $ratings->total(),
                        'has_more' => $ratings->hasMorePages()
                    ]
                ],
                'message' => 'امتیازات شما دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user ratings', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت امتیازات'
            ], 500);
        }
    }

    /**
     * Submit rating for a story
     */
    public function submitStoryRating(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'story_id' => 'required|integer|exists:stories,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000'
        ], [
            'story_id.required' => 'شناسه داستان الزامی است',
            'story_id.integer' => 'شناسه داستان باید عدد باشد',
            'story_id.exists' => 'داستان یافت نشد',
            'rating.required' => 'امتیاز الزامی است',
            'rating.min' => 'امتیاز نمی‌تواند کمتر از 1 باشد',
            'rating.max' => 'امتیاز نمی‌تواند بیشتر از 5 باشد',
            'review.max' => 'نقد نمی‌تواند بیشتر از 1000 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $storyId = $request->input('story_id');
            $rating = $request->input('rating');
            $review = $request->input('review');

            $ratingRecord = Rating::submitStoryRating($user->id, $storyId, $rating, $review);
            $stats = Rating::getStoryStats($storyId);

            return response()->json([
                'success' => true,
                'data' => [
                    'rating_id' => $ratingRecord->id,
                    'story_id' => $storyId,
                    'rating' => $rating,
                    'review' => $review,
                    'star_rating' => $ratingRecord->star_rating,
                    'stats' => $stats
                ],
                'message' => 'امتیاز داستان ثبت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit story rating', [
                'user_id' => $request->user()->id,
                'story_id' => $request->input('story_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت امتیاز داستان: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit rating for an episode
     */
    public function submitEpisodeRating(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => 'required|integer|exists:episodes,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000'
        ], [
            'episode_id.required' => 'شناسه قسمت الزامی است',
            'episode_id.integer' => 'شناسه قسمت باید عدد باشد',
            'episode_id.exists' => 'قسمت یافت نشد',
            'rating.required' => 'امتیاز الزامی است',
            'rating.min' => 'امتیاز نمی‌تواند کمتر از 1 باشد',
            'rating.max' => 'امتیاز نمی‌تواند بیشتر از 5 باشد',
            'review.max' => 'نقد نمی‌تواند بیشتر از 1000 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $episodeId = $request->input('episode_id');
            $rating = $request->input('rating');
            $review = $request->input('review');

            $ratingRecord = Rating::submitEpisodeRating($user->id, $episodeId, $rating, $review);
            $stats = Rating::getEpisodeStats($episodeId);

            return response()->json([
                'success' => true,
                'data' => [
                    'rating_id' => $ratingRecord->id,
                    'episode_id' => $episodeId,
                    'rating' => $rating,
                    'review' => $review,
                    'star_rating' => $ratingRecord->star_rating,
                    'stats' => $stats
                ],
                'message' => 'امتیاز قسمت ثبت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit episode rating', [
                'user_id' => $request->user()->id,
                'episode_id' => $request->input('episode_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت امتیاز قسمت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get story ratings
     */
    public function getStoryRatings(Request $request, int $storyId): JsonResponse
    {
        try {
            // Check if story exists
            $story = Story::find($storyId);
            if (!$story) {
                return response()->json([
                    'success' => false,
                    'message' => 'داستان یافت نشد'
                ], 404);
            }

            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $ratings = Rating::getStoryRatings($storyId, $perPage);
            $stats = Rating::getStoryStats($storyId);

            return response()->json([
                'success' => true,
                'data' => [
                    'story_id' => $storyId,
                    'ratings' => $ratings->items(),
                    'stats' => $stats,
                    'pagination' => [
                        'current_page' => $ratings->currentPage(),
                        'last_page' => $ratings->lastPage(),
                        'per_page' => $ratings->perPage(),
                        'total' => $ratings->total(),
                        'has_more' => $ratings->hasMorePages()
                    ]
                ],
                'message' => 'امتیازات داستان دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get story ratings', [
                'story_id' => $storyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت امتیازات داستان'
            ], 500);
        }
    }

    /**
     * Get episode ratings
     */
    public function getEpisodeRatings(Request $request, int $episodeId): JsonResponse
    {
        try {
            // Check if episode exists
            $episode = Episode::find($episodeId);
            if (!$episode) {
                return response()->json([
                    'success' => false,
                    'message' => 'قسمت یافت نشد'
                ], 404);
            }

            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $ratings = Rating::getEpisodeRatings($episodeId, $perPage);
            $stats = Rating::getEpisodeStats($episodeId);

            return response()->json([
                'success' => true,
                'data' => [
                    'episode_id' => $episodeId,
                    'ratings' => $ratings->items(),
                    'stats' => $stats,
                    'pagination' => [
                        'current_page' => $ratings->currentPage(),
                        'last_page' => $ratings->lastPage(),
                        'per_page' => $ratings->perPage(),
                        'total' => $ratings->total(),
                        'has_more' => $ratings->hasMorePages()
                    ]
                ],
                'message' => 'امتیازات قسمت دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get episode ratings', [
                'episode_id' => $episodeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت امتیازات قسمت'
            ], 500);
        }
    }

    /**
     * Get user's rating for a story
     */
    public function getUserStoryRating(Request $request, int $storyId): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if story exists
            $story = Story::find($storyId);
            if (!$story) {
                return response()->json([
                    'success' => false,
                    'message' => 'داستان یافت نشد'
                ], 404);
            }

            $rating = Rating::getUserStoryRating($user->id, $storyId);

            if (!$rating) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'story_id' => $storyId,
                        'has_rated' => false,
                        'rating' => null,
                        'review' => null
                    ],
                    'message' => 'شما هنوز این داستان را امتیاز نداده‌اید'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'story_id' => $storyId,
                    'has_rated' => true,
                    'rating' => $rating->rating,
                    'review' => $rating->review,
                    'star_rating' => $rating->star_rating,
                    'created_at' => $rating->created_at
                ],
                'message' => 'امتیاز شما برای این داستان دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user story rating', [
                'user_id' => $request->user()->id,
                'story_id' => $storyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت امتیاز شما'
            ], 500);
        }
    }

    /**
     * Get user's rating for an episode
     */
    public function getUserEpisodeRating(Request $request, int $episodeId): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if episode exists
            $episode = Episode::find($episodeId);
            if (!$episode) {
                return response()->json([
                    'success' => false,
                    'message' => 'قسمت یافت نشد'
                ], 404);
            }

            $rating = Rating::getUserEpisodeRating($user->id, $episodeId);

            if (!$rating) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'episode_id' => $episodeId,
                        'has_rated' => false,
                        'rating' => null,
                        'review' => null
                    ],
                    'message' => 'شما هنوز این قسمت را امتیاز نداده‌اید'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'episode_id' => $episodeId,
                    'has_rated' => true,
                    'rating' => $rating->rating,
                    'review' => $rating->review,
                    'star_rating' => $rating->star_rating,
                    'created_at' => $rating->created_at
                ],
                'message' => 'امتیاز شما برای این قسمت دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user episode rating', [
                'user_id' => $request->user()->id,
                'episode_id' => $episodeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت امتیاز شما'
            ], 500);
        }
    }

    /**
     * Get highest rated stories
     */
    public function getHighestRatedStories(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $limit = min($limit, 50); // Max 50

            $highestRated = Rating::getHighestRatedStories($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'highest_rated_stories' => $highestRated,
                    'limit' => $limit
                ],
                'message' => 'بالاترین امتیاز داستان‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get highest rated stories', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت بالاترین امتیاز داستان‌ها'
            ], 500);
        }
    }

    /**
     * Get highest rated episodes
     */
    public function getHighestRatedEpisodes(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $limit = min($limit, 50); // Max 50

            $highestRated = Rating::getHighestRatedEpisodes($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'highest_rated_episodes' => $highestRated,
                    'limit' => $limit
                ],
                'message' => 'بالاترین امتیاز قسمت‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get highest rated episodes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت بالاترین امتیاز قسمت‌ها'
            ], 500);
        }
    }

    /**
     * Get recent reviews
     */
    public function getRecentReviews(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 20);
            $limit = min($limit, 100); // Max 100

            $recentReviews = Rating::getRecentReviews($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_reviews' => $recentReviews,
                    'limit' => $limit
                ],
                'message' => 'آخرین نقدها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get recent reviews', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آخرین نقدها'
            ], 500);
        }
    }

    /**
     * Get user's rating statistics
     */
    public function getUserStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = Rating::getUserStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار امتیازات شما دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user rating stats', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار امتیازات'
            ], 500);
        }
    }

    /**
     * Get rating analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 30);
            $days = min($days, 365); // Max 1 year

            $analytics = Rating::getRatingAnalytics($days);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'تحلیل امتیازات دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get rating analytics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تحلیل امتیازات'
            ], 500);
        }
    }
}
