<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get personalized recommendations for authenticated user
     */
    public function getPersonalizedRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'type' => 'nullable|string|in:all,collaborative,content_based,trending,similar_users,category_based'
        ], [
            'limit.integer' => 'تعداد پیشنهادات باید عدد باشد',
            'limit.min' => 'تعداد پیشنهادات باید حداقل 1 باشد',
            'limit.max' => 'تعداد پیشنهادات نمی‌تواند بیشتر از 50 باشد',
            'type.in' => 'نوع پیشنهاد نامعتبر است'
        ]);

        $userId = auth()->id();
        $limit = $request->get('limit', 10);
        $type = $request->get('type', 'all');

        try {
            if ($type === 'all') {
                $recommendations = $this->recommendationService->getPersonalizedRecommendations($userId, $limit);
            } else {
                $recommendations = $this->getRecommendationsByType($userId, $type, $limit);
            }

            return response()->json([
                'success' => true,
                'message' => 'پیشنهادات شخصی‌سازی شده دریافت شد',
                'data' => [
                    'recommendations' => $recommendations,
                    'total' => count($recommendations),
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
     * Get recommendations for new users
     */
    public function getNewUserRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'limit.integer' => 'تعداد پیشنهادات باید عدد باشد',
            'limit.min' => 'تعداد پیشنهادات باید حداقل 1 باشد',
            'limit.max' => 'تعداد پیشنهادات نمی‌تواند بیشتر از 50 باشد'
        ]);

        $limit = $request->get('limit', 10);

        try {
            $recommendations = $this->recommendationService->getNewUserRecommendations($limit);

            return response()->json([
                'success' => true,
                'message' => 'پیشنهادات برای کاربران جدید دریافت شد',
                'data' => [
                    'recommendations' => $recommendations,
                    'total' => count($recommendations)
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
     * Get trending content recommendations
     */
    public function getTrendingRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'limit.integer' => 'تعداد پیشنهادات باید عدد باشد',
            'limit.min' => 'تعداد پیشنهادات باید حداقل 1 باشد',
            'limit.max' => 'تعداد پیشنهادات نمی‌تواند بیشتر از 50 باشد'
        ]);

        $limit = $request->get('limit', 10);

        try {
            $recommendations = $this->recommendationService->getTrendingContent($limit);

            return response()->json([
                'success' => true,
                'message' => 'پیشنهادات محتوای محبوب دریافت شد',
                'data' => [
                    'recommendations' => $recommendations,
                    'total' => count($recommendations)
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
     * Get similar content recommendations
     */
    public function getSimilarContent(Request $request, int $storyId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'limit.integer' => 'تعداد پیشنهادات باید عدد باشد',
            'limit.min' => 'تعداد پیشنهادات باید حداقل 1 باشد',
            'limit.max' => 'تعداد پیشنهادات نمی‌تواند بیشتر از 50 باشد'
        ]);

        $limit = $request->get('limit', 10);

        try {
            $recommendations = $this->recommendationService->getSimilarContent($storyId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'پیشنهادات محتوای مشابه دریافت شد',
                'data' => [
                    'recommendations' => $recommendations,
                    'total' => count($recommendations),
                    'story_id' => $storyId
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
     * Get user preferences
     */
    public function getUserPreferences(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $preferences = $this->recommendationService->getUserPreferences($userId);

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
            $behavior = $this->recommendationService->getUserBehavior($userId);

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
     * Get similar users
     */
    public function getSimilarUsers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:20'
        ], [
            'limit.integer' => 'تعداد کاربران مشابه باید عدد باشد',
            'limit.min' => 'تعداد کاربران مشابه باید حداقل 1 باشد',
            'limit.max' => 'تعداد کاربران مشابه نمی‌تواند بیشتر از 20 باشد'
        ]);

        try {
            $userId = auth()->id();
            $limit = $request->get('limit', 10);
            $similarUsers = $this->recommendationService->findSimilarUsers($userId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'کاربران مشابه دریافت شد',
                'data' => [
                    'similar_users' => $similarUsers,
                    'total' => count($similarUsers),
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت کاربران مشابه: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear user recommendation cache
     */
    public function clearRecommendationCache(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $this->recommendationService->clearUserRecommendationCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'کش پیشنهادات کاربر پاک شد',
                'data' => [
                    'user_id' => $userId
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
     * Get recommendation explanation
     */
    public function getRecommendationExplanation(Request $request, int $storyId): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            // Get user's personalized recommendations to find this story
            $recommendations = $this->recommendationService->getPersonalizedRecommendations($userId, 50);
            
            $explanation = null;
            foreach ($recommendations as $recommendation) {
                if ($recommendation['story_id'] == $storyId) {
                    $explanation = [
                        'type' => $recommendation['type'],
                        'reason' => $recommendation['reason'],
                        'score' => $recommendation['score']
                    ];
                    break;
                }
            }

            if (!$explanation) {
                return response()->json([
                    'success' => false,
                    'message' => 'توضیحی برای این پیشنهاد یافت نشد'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'توضیح پیشنهاد دریافت شد',
                'data' => [
                    'explanation' => $explanation,
                    'story_id' => $storyId,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت توضیح پیشنهاد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recommendations by specific type
     */
    private function getRecommendationsByType(int $userId, string $type, int $limit): array
    {
        switch ($type) {
            case 'collaborative':
                return $this->recommendationService->getCollaborativeFilteringRecommendations($userId, $limit);
            case 'content_based':
                return $this->recommendationService->getContentBasedRecommendations($userId, $limit);
            case 'trending':
                return $this->recommendationService->getTrendingRecommendations($userId, $limit);
            case 'similar_users':
                return $this->recommendationService->getSimilarUsersRecommendations($userId, $limit);
            case 'category_based':
                return $this->recommendationService->getCategoryBasedRecommendations($userId, $limit);
            default:
                return [];
        }
    }
}