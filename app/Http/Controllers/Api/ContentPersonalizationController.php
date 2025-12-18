<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContentPersonalizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentPersonalizationController extends Controller
{
    protected $personalizationService;

    public function __construct(ContentPersonalizationService $personalizationService)
    {
        $this->personalizationService = $personalizationService;
    }

    /**
     * Get personalized content feed for user
     */
    public function getPersonalizedFeed(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'limit.integer' => 'تعداد محتوا باید عدد باشد',
            'limit.min' => 'تعداد محتوا باید حداقل 1 باشد',
            'limit.max' => 'تعداد محتوا نمی‌تواند بیشتر از 50 باشد'
        ]);

        $userId = auth()->id();
        $limit = $request->get('limit', 20);

        try {
            $feed = $this->personalizationService->getPersonalizedFeed($userId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'فید شخصی‌سازی شده دریافت شد',
                'data' => [
                    'feed' => $feed,
                    'total' => count($feed),
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فید شخصی‌سازی شده: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personalized search results
     */
    public function getPersonalizedSearch(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'query.required' => 'جستجو الزامی است',
            'query.min' => 'جستجو باید حداقل 2 کاراکتر باشد',
            'query.max' => 'جستجو نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'limit.integer' => 'تعداد نتایج باید عدد باشد',
            'limit.min' => 'تعداد نتایج باید حداقل 1 باشد',
            'limit.max' => 'تعداد نتایج نمی‌تواند بیشتر از 50 باشد'
        ]);

        $userId = auth()->id();
        $query = $request->get('query');
        $limit = $request->get('limit', 20);

        try {
            $results = $this->personalizationService->getPersonalizedSearchResults($userId, $query, $limit);

            return response()->json([
                'success' => true,
                'message' => 'نتایج جستجوی شخصی‌سازی شده دریافت شد',
                'data' => [
                    'results' => $results,
                    'total' => count($results),
                    'query' => $query,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجوی شخصی‌سازی شده: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personalized category recommendations
     */
    public function getPersonalizedCategoryRecommendations(Request $request, int $categoryId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'limit.integer' => 'تعداد پیشنهادات باید عدد باشد',
            'limit.min' => 'تعداد پیشنهادات باید حداقل 1 باشد',
            'limit.max' => 'تعداد پیشنهادات نمی‌تواند بیشتر از 50 باشد'
        ]);

        $userId = auth()->id();
        $limit = $request->get('limit', 10);

        try {
            $recommendations = $this->personalizationService->getPersonalizedCategoryRecommendations($userId, $categoryId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'پیشنهادات شخصی‌سازی شده دسته‌بندی دریافت شد',
                'data' => [
                    'recommendations' => $recommendations,
                    'total' => count($recommendations),
                    'category_id' => $categoryId,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پیشنهادات دسته‌بندی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personalized dashboard
     */
    public function getPersonalizedDashboard(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $dashboard = $this->personalizationService->getPersonalizedDashboard($userId);

            return response()->json([
                'success' => true,
                'message' => 'داشبورد شخصی‌سازی شده دریافت شد',
                'data' => [
                    'dashboard' => $dashboard,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت داشبورد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Learn user preferences from interactions
     */
    public function learnPreferences(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $this->personalizationService->learnUserPreferences($userId);

            return response()->json([
                'success' => true,
                'message' => 'ترجیحات کاربر یادگیری شد',
                'data' => [
                    'user_id' => $userId,
                    'learned_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در یادگیری ترجیحات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update preferences from interaction
     */
    public function updatePreferencesFromInteraction(Request $request): JsonResponse
    {
        $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
            'interaction_type' => 'required|string|in:favorite,play,rating_high,rating_low,dismiss,skip'
        ], [
            'story_id.required' => 'شناسه داستان الزامی است',
            'story_id.exists' => 'داستان یافت نشد',
            'interaction_type.required' => 'نوع تعامل الزامی است',
            'interaction_type.in' => 'نوع تعامل نامعتبر است'
        ]);

        try {
            $userId = auth()->id();
            $storyId = $request->get('story_id');
            $interactionType = $request->get('interaction_type');

            $this->personalizationService->updatePreferencesFromInteraction($userId, $storyId, $interactionType);

            return response()->json([
                'success' => true,
                'message' => 'ترجیحات بر اساس تعامل به‌روزرسانی شد',
                'data' => [
                    'user_id' => $userId,
                    'story_id' => $storyId,
                    'interaction_type' => $interactionType,
                    'updated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی ترجیحات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user preferences
     */
    public function getUserPreferences(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $preferences = $this->personalizationService->getUserPreferences($userId);

            return response()->json([
                'success' => true,
                'message' => 'ترجیحات کاربر دریافت شد',
                'data' => [
                    'preferences' => $preferences,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت ترجیحات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user behavior patterns
     */
    public function getUserBehavior(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $behavior = $this->personalizationService->getUserBehavior($userId);

            return response()->json([
                'success' => true,
                'message' => 'الگوهای رفتاری کاربر دریافت شد',
                'data' => [
                    'behavior' => $behavior,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت الگوهای رفتاری: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personalized content suggestions
     */
    public function getContentSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|in:continue_listening,recently_favorited,trending_preferred,new_from_preferred',
            'limit' => 'nullable|integer|min:1|max:20'
        ], [
            'type.in' => 'نوع پیشنهاد نامعتبر است',
            'limit.integer' => 'تعداد پیشنهادات باید عدد باشد',
            'limit.min' => 'تعداد پیشنهادات باید حداقل 1 باشد',
            'limit.max' => 'تعداد پیشنهادات نمی‌تواند بیشتر از 20 باشد'
        ]);

        try {
            $userId = auth()->id();
            $type = $request->get('type', 'continue_listening');
            $limit = $request->get('limit', 10);

            $dashboard = $this->personalizationService->getPersonalizedDashboard($userId);
            
            $suggestions = match($type) {
                'continue_listening' => $dashboard['continue_listening'] ?? [],
                'recently_favorited' => $dashboard['recently_favorited'] ?? [],
                'trending_preferred' => $dashboard['trending_in_preferences'] ?? [],
                'new_from_preferred' => $dashboard['new_from_preferred_creators'] ?? [],
                default => []
            };

            $suggestions = array_slice($suggestions, 0, $limit);

            return response()->json([
                'success' => true,
                'message' => 'پیشنهادات محتوا دریافت شد',
                'data' => [
                    'suggestions' => $suggestions,
                    'total' => count($suggestions),
                    'type' => $type,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پیشنهادات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's personalized insights
     */
    public function getPersonalizedInsights(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $dashboard = $this->personalizationService->getPersonalizedDashboard($userId);

            $insights = [
                'preferred_categories' => $dashboard['preferred_categories'] ?? [],
                'preferred_creators' => $dashboard['preferred_creators'] ?? [],
                'listening_patterns' => $dashboard['listening_patterns'] ?? [],
                'content_preferences' => $dashboard['content_preferences'] ?? [],
                'total_favorites' => count($dashboard['recently_favorited'] ?? []),
                'continue_listening_count' => count($dashboard['continue_listening'] ?? []),
                'recommended_stories_count' => count($dashboard['recommended_stories'] ?? [])
            ];

            return response()->json([
                'success' => true,
                'message' => 'بینش‌های شخصی‌سازی شده دریافت شد',
                'data' => [
                    'insights' => $insights,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت بینش‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear personalization cache
     */
    public function clearPersonalizationCache(): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            // Clear all personalization-related cache
            $patterns = [
                "user_preferences_{$userId}",
                "user_behavior_{$userId}",
                "personalized_feed_{$userId}_*",
                "personalized_dashboard_{$userId}",
                "personalized_search_{$userId}_*",
                "personalized_category_{$userId}_*"
            ];

            foreach ($patterns as $pattern) {
                \Illuminate\Support\Facades\Cache::forget($pattern);
            }

            return response()->json([
                'success' => true,
                'message' => 'کش شخصی‌سازی پاک شد',
                'data' => [
                    'user_id' => $userId,
                    'cleared_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در پاک کردن کش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personalization statistics
     */
    public function getPersonalizationStats(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $preferences = $this->personalizationService->getUserPreferences($userId);
            $behavior = $this->personalizationService->getUserBehavior($userId);

            $stats = [
                'total_preferences' => array_sum(array_map('count', $preferences)),
                'category_preferences' => count($preferences['category'] ?? []),
                'director_preferences' => count($preferences['director'] ?? []),
                'narrator_preferences' => count($preferences['narrator'] ?? []),
                'avg_session_duration' => $behavior['avg_session_duration'] ?? 0,
                'completion_rate' => round(($behavior['completion_rate'] ?? 0) * 100, 2),
                'total_sessions' => $behavior['total_sessions'] ?? 0,
                'preferred_hours_count' => count($behavior['preferred_hours'] ?? [])
            ];

            return response()->json([
                'success' => true,
                'message' => 'آمار شخصی‌سازی دریافت شد',
                'data' => [
                    'stats' => $stats,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار: ' . $e->getMessage()
            ], 500);
        }
    }
}