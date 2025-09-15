<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayHistory;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PlayHistoryController extends Controller
{
    /**
     * Get user's play history
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $playHistory = PlayHistory::getUserPlayHistory($user->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'play_history' => $playHistory->items(),
                    'pagination' => [
                        'current_page' => $playHistory->currentPage(),
                        'last_page' => $playHistory->lastPage(),
                        'per_page' => $playHistory->perPage(),
                        'total' => $playHistory->total(),
                        'has_more' => $playHistory->hasMorePages()
                    ]
                ],
                'message' => 'تاریخچه پخش دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get play history', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه پخش'
            ], 500);
        }
    }

    /**
     * Record a play session
     */
    public function record(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => 'required|integer|exists:episodes,id',
            'duration_played' => 'required|integer|min:0',
            'total_duration' => 'required|integer|min:1',
            'device_info' => 'nullable|array'
        ], [
            'episode_id.required' => 'شناسه قسمت الزامی است',
            'episode_id.integer' => 'شناسه قسمت باید عدد باشد',
            'episode_id.exists' => 'قسمت یافت نشد',
            'duration_played.required' => 'مدت زمان پخش شده الزامی است',
            'duration_played.min' => 'مدت زمان پخش شده نمی‌تواند منفی باشد',
            'total_duration.required' => 'مدت زمان کل الزامی است',
            'total_duration.min' => 'مدت زمان کل باید حداقل 1 ثانیه باشد',
            'device_info.array' => 'اطلاعات دستگاه باید آرایه باشد'
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
            $durationPlayed = $request->input('duration_played');
            $totalDuration = $request->input('total_duration');
            $deviceInfo = $request->input('device_info');

            // Check if episode exists and is published
            $episode = Episode::where('id', $episodeId)
                             ->where('status', 'published')
                             ->first();

            if (!$episode) {
                return response()->json([
                    'success' => false,
                    'message' => 'قسمت یافت نشد یا منتشر نشده است'
                ], 404);
            }

            // Validate duration
            if ($durationPlayed > $totalDuration) {
                return response()->json([
                    'success' => false,
                    'message' => 'مدت زمان پخش شده نمی‌تواند بیشتر از مدت زمان کل باشد'
                ], 422);
            }

            $playHistory = PlayHistory::recordPlay(
                $user->id,
                $episodeId,
                $durationPlayed,
                $totalDuration,
                $deviceInfo
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'play_history_id' => $playHistory->id,
                    'episode_id' => $episodeId,
                    'story_id' => $episode->story_id,
                    'duration_played' => $durationPlayed,
                    'total_duration' => $totalDuration,
                    'completion_percentage' => $playHistory->completion_percentage,
                    'completed' => $playHistory->completed,
                    'played_at' => $playHistory->played_at
                ],
                'message' => 'جلسه پخش ثبت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record play session', [
                'user_id' => $request->user()->id,
                'episode_id' => $request->input('episode_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت جلسه پخش'
            ], 500);
        }
    }

    /**
     * Update play progress
     */
    public function updateProgress(Request $request, int $playHistoryId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'duration_played' => 'required|integer|min:0',
            'total_duration' => 'nullable|integer|min:1'
        ], [
            'duration_played.required' => 'مدت زمان پخش شده الزامی است',
            'duration_played.min' => 'مدت زمان پخش شده نمی‌تواند منفی باشد',
            'total_duration.min' => 'مدت زمان کل باید حداقل 1 ثانیه باشد'
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
            $durationPlayed = $request->input('duration_played');
            $totalDuration = $request->input('total_duration');

            $playHistory = PlayHistory::where('id', $playHistoryId)
                                    ->where('user_id', $user->id)
                                    ->first();

            if (!$playHistory) {
                return response()->json([
                    'success' => false,
                    'message' => 'رکورد پخش یافت نشد'
                ], 404);
            }

            // Validate duration
            $currentTotalDuration = $totalDuration ?? $playHistory->total_duration;
            if ($durationPlayed > $currentTotalDuration) {
                return response()->json([
                    'success' => false,
                    'message' => 'مدت زمان پخش شده نمی‌تواند بیشتر از مدت زمان کل باشد'
                ], 422);
            }

            $updated = $playHistory->updateProgress($durationPlayed, $totalDuration);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'play_history_id' => $playHistory->id,
                        'duration_played' => $durationPlayed,
                        'total_duration' => $playHistory->total_duration,
                        'completion_percentage' => $playHistory->completion_percentage,
                        'completed' => $playHistory->completed,
                        'remaining_time' => $playHistory->remaining_time,
                        'played_at' => $playHistory->played_at
                    ],
                    'message' => 'پیشرفت پخش به‌روزرسانی شد'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در به‌روزرسانی پیشرفت پخش'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update play progress', [
                'user_id' => $request->user()->id,
                'play_history_id' => $playHistoryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی پیشرفت پخش'
            ], 500);
        }
    }

    /**
     * Get user's recent play history
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = $request->input('limit', 10);
            $limit = min($limit, 50); // Max 50

            $recentHistory = PlayHistory::getUserRecentHistory($user->id, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_history' => $recentHistory,
                    'limit' => $limit
                ],
                'message' => 'تاریخچه پخش اخیر دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get recent play history', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه پخش اخیر'
            ], 500);
        }
    }

    /**
     * Get user's completed episodes
     */
    public function completed(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $completedEpisodes = PlayHistory::getUserCompletedEpisodes($user->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'completed_episodes' => $completedEpisodes->items(),
                    'pagination' => [
                        'current_page' => $completedEpisodes->currentPage(),
                        'last_page' => $completedEpisodes->lastPage(),
                        'per_page' => $completedEpisodes->perPage(),
                        'total' => $completedEpisodes->total(),
                        'has_more' => $completedEpisodes->hasMorePages()
                    ]
                ],
                'message' => 'قسمت‌های تکمیل شده دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get completed episodes', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت قسمت‌های تکمیل شده'
            ], 500);
        }
    }

    /**
     * Get user's incomplete episodes (in progress)
     */
    public function inProgress(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $incompleteEpisodes = PlayHistory::getUserIncompleteEpisodes($user->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'in_progress_episodes' => $incompleteEpisodes->items(),
                    'pagination' => [
                        'current_page' => $incompleteEpisodes->currentPage(),
                        'last_page' => $incompleteEpisodes->lastPage(),
                        'per_page' => $incompleteEpisodes->perPage(),
                        'total' => $incompleteEpisodes->total(),
                        'has_more' => $incompleteEpisodes->hasMorePages()
                    ]
                ],
                'message' => 'قسمت‌های در حال پخش دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get in-progress episodes', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت قسمت‌های در حال پخش'
            ], 500);
        }
    }

    /**
     * Get user's play statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = PlayHistory::getUserStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار پخش دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get play stats', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار پخش'
            ], 500);
        }
    }

    /**
     * Get episode play statistics
     */
    public function episodeStats(Request $request, int $episodeId): JsonResponse
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

            $stats = PlayHistory::getEpisodeStats($episodeId);

            return response()->json([
                'success' => true,
                'data' => [
                    'episode_id' => $episodeId,
                    'stats' => $stats
                ],
                'message' => 'آمار پخش قسمت دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get episode stats', [
                'episode_id' => $episodeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار پخش قسمت'
            ], 500);
        }
    }

    /**
     * Get story play statistics
     */
    public function storyStats(Request $request, int $storyId): JsonResponse
    {
        try {
            $stats = PlayHistory::getStoryStats($storyId);

            return response()->json([
                'success' => true,
                'data' => [
                    'story_id' => $storyId,
                    'stats' => $stats
                ],
                'message' => 'آمار پخش داستان دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get story stats', [
                'story_id' => $storyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار پخش داستان'
            ], 500);
        }
    }

    /**
     * Get most played episodes
     */
    public function mostPlayed(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $limit = min($limit, 50); // Max 50

            $mostPlayed = PlayHistory::getMostPlayedEpisodes($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'most_played_episodes' => $mostPlayed,
                    'limit' => $limit
                ],
                'message' => 'پربازدیدترین قسمت‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get most played episodes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پربازدیدترین قسمت‌ها'
            ], 500);
        }
    }

    /**
     * Get most played stories
     */
    public function mostPlayedStories(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $limit = min($limit, 50); // Max 50

            $mostPlayed = PlayHistory::getMostPlayedStories($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'most_played_stories' => $mostPlayed,
                    'limit' => $limit
                ],
                'message' => 'پربازدیدترین داستان‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get most played stories', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پربازدیدترین داستان‌ها'
            ], 500);
        }
    }

    /**
     * Get play analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 30);
            $days = min($days, 365); // Max 1 year

            $analytics = PlayHistory::getPlayAnalytics($days);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'تحلیل پخش دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get play analytics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تحلیل پخش'
            ], 500);
        }
    }
}
