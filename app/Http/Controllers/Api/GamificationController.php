<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GamificationController extends Controller
{
    protected $gamificationService;
    
    /**
     * Gamification system disabled flag
     */
    private bool $disabled = true;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Get user's gamification profile
     */
    public function getUserProfile(): JsonResponse
    {
        if ($this->disabled) {
            return response()->json([
                'success' => false,
                'message' => 'سیستم گیمیفیکیشن غیرفعال است'
            ], 403);
        }
        
        try {
            $userId = auth()->id();
            $profile = $this->gamificationService->getUserProfile($userId);

            return response()->json([
                'success' => true,
                'message' => 'پروفایل گیمیفیکیشن کاربر دریافت شد',
                'data' => [
                    'profile' => $profile,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پروفایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Award points to user
     */
    public function awardPoints(Request $request): JsonResponse
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'source_type' => 'required|string',
            'source_id' => 'nullable|integer',
            'description' => 'required|string|max:255'
        ], [
            'points.required' => 'تعداد امتیاز الزامی است',
            'points.integer' => 'تعداد امتیاز باید عدد باشد',
            'points.min' => 'تعداد امتیاز باید حداقل 1 باشد',
            'source_type.required' => 'نوع منبع الزامی است',
            'description.required' => 'توضیحات الزامی است',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 255 کاراکتر باشد'
        ]);

        try {
            $userId = auth()->id();
            $result = $this->gamificationService->awardPoints(
                $userId,
                $request->get('points'),
                $request->get('source_type'),
                $request->get('source_id'),
                $request->get('description'),
                $request->get('metadata', [])
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعطای امتیاز: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100'
        ], [
            'limit.integer' => 'تعداد ورودی‌ها باید عدد باشد',
            'limit.min' => 'تعداد ورودی‌ها باید حداقل 1 باشد',
            'limit.max' => 'تعداد ورودی‌ها نمی‌تواند بیشتر از 100 باشد'
        ]);

        try {
            $limit = $request->get('limit', 50);
            $result = $this->gamificationService->getLeaderboard($slug, $limit);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جدول امتیازات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update leaderboard
     */
    public function updateLeaderboard(string $slug): JsonResponse
    {
        try {
            $result = $this->gamificationService->updateLeaderboard($slug);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی جدول امتیازات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user streak
     */
    public function updateStreak(Request $request): JsonResponse
    {
        $request->validate([
            'streak_type' => 'required|string|in:listening,login,daily_goal',
            'increment' => 'nullable|boolean'
        ], [
            'streak_type.required' => 'نوع استریک الزامی است',
            'streak_type.in' => 'نوع استریک نامعتبر است'
        ]);

        try {
            $userId = auth()->id();
            $result = $this->gamificationService->updateStreak(
                $userId,
                $request->get('streak_type'),
                $request->get('increment', true)
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی استریک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available challenges
     */
    public function getAvailableChallenges(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'limit.integer' => 'تعداد چالش‌ها باید عدد باشد',
            'limit.min' => 'تعداد چالش‌ها باید حداقل 1 باشد',
            'limit.max' => 'تعداد چالش‌ها نمی‌تواند بیشتر از 50 باشد'
        ]);

        try {
            $userId = auth()->id();
            $limit = $request->get('limit', 10);
            $challenges = $this->gamificationService->getAvailableChallenges($userId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'چالش‌های موجود دریافت شد',
                'data' => [
                    'challenges' => $challenges,
                    'total' => count($challenges),
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت چالش‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join challenge
     */
    public function joinChallenge(Request $request, int $challengeId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $result = $this->gamificationService->joinChallenge($userId, $challengeId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در شرکت در چالش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user achievements
     */
    public function getUserAchievements(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'category' => 'nullable|string'
        ], [
            'limit.integer' => 'تعداد دستاوردها باید عدد باشد',
            'limit.min' => 'تعداد دستاوردها باید حداقل 1 باشد',
            'limit.max' => 'تعداد دستاوردها نمی‌تواند بیشتر از 100 باشد'
        ]);

        try {
            $userId = auth()->id();
            $limit = $request->get('limit', 20);
            $category = $request->get('category');

            $query = \App\Models\UserAchievement::where('user_id', $userId)
                ->with(['achievement'])
                ->orderBy('unlocked_at', 'desc');

            if ($category) {
                $query->whereHas('achievement', function($q) use ($category) {
                    $q->where('category', $category);
                });
            }

            $achievements = $query->limit($limit)->get();

            return response()->json([
                'success' => true,
                'message' => 'دستاوردهای کاربر دریافت شد',
                'data' => [
                    'achievements' => $achievements,
                    'total' => $achievements->count(),
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت دستاوردها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user badges
     */
    public function getUserBadges(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'rarity' => 'nullable|string|in:common,uncommon,rare,epic,legendary'
        ], [
            'limit.integer' => 'تعداد نشان‌ها باید عدد باشد',
            'limit.min' => 'تعداد نشان‌ها باید حداقل 1 باشد',
            'limit.max' => 'تعداد نشان‌ها نمی‌تواند بیشتر از 100 باشد',
            'rarity.in' => 'نوع کمیابی نامعتبر است'
        ]);

        try {
            $userId = auth()->id();
            $limit = $request->get('limit', 20);
            $rarity = $request->get('rarity');

            $query = \App\Models\UserBadge::where('user_id', $userId)
                ->with(['badge'])
                ->orderBy('earned_at', 'desc');

            if ($rarity) {
                $query->whereHas('badge', function($q) use ($rarity) {
                    $q->where('rarity', $rarity);
                });
            }

            $badges = $query->limit($limit)->get();

            return response()->json([
                'success' => true,
                'message' => 'نشان‌های کاربر دریافت شد',
                'data' => [
                    'badges' => $badges,
                    'total' => $badges->count(),
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت نشان‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user streaks
     */
    public function getUserStreaks(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $streaks = \App\Models\UserStreak::where('user_id', $userId)->get();

            return response()->json([
                'success' => true,
                'message' => 'استریک‌های کاربر دریافت شد',
                'data' => [
                    'streaks' => $streaks,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت استریک‌ها: ' . $e->getMessage()
            ], 500);
        }
    }
}