<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SocialService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SocialController extends Controller
{
    protected $socialService;

    public function __construct(SocialService $socialService)
    {
        $this->socialService = $socialService;
    }

    /**
     * Follow a user
     */
    public function followUser(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ], [
            'user_id.required' => 'شناسه کاربر الزامی است',
            'user_id.exists' => 'کاربر یافت نشد'
        ]);

        try {
            $followerId = auth()->id();
            $result = $this->socialService->followUser($followerId, $userId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دنبال کردن کاربر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unfollow a user
     */
    public function unfollowUser(Request $request, int $userId): JsonResponse
    {
        try {
            $followerId = auth()->id();
            $result = $this->socialService->unfollowUser($followerId, $userId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در متوقف کردن دنبال: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Share content
     */
    public function shareContent(Request $request): JsonResponse
    {
        $request->validate([
            'shareable_type' => 'required|string|in:story,episode,playlist',
            'shareable_id' => 'required|integer',
            'share_type' => 'required|string|in:link,embed,social_media',
            'platform' => 'nullable|string|in:facebook,twitter,instagram,telegram,whatsapp',
            'message' => 'nullable|string|max:500'
        ], [
            'shareable_type.required' => 'نوع محتوا الزامی است',
            'shareable_type.in' => 'نوع محتوا نامعتبر است',
            'shareable_id.required' => 'شناسه محتوا الزامی است',
            'share_type.required' => 'نوع اشتراک‌گذاری الزامی است',
            'share_type.in' => 'نوع اشتراک‌گذاری نامعتبر است',
            'platform.in' => 'پلتفرم نامعتبر است',
            'message.max' => 'پیام نمی‌تواند بیشتر از 500 کاراکتر باشد'
        ]);

        try {
            $userId = auth()->id();
            $shareData = $request->only(['share_type', 'platform', 'message']);
            $shareData['metadata'] = $request->get('metadata');

            $result = $this->socialService->shareContent(
                $userId,
                $request->get('shareable_type'),
                $request->get('shareable_id'),
                $shareData
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در به اشتراک گذاری محتوا: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's followers
     */
    public function getUserFollowers(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0'
        ], [
            'limit.integer' => 'تعداد دنبال‌کنندگان باید عدد باشد',
            'limit.min' => 'تعداد دنبال‌کنندگان باید حداقل 1 باشد',
            'limit.max' => 'تعداد دنبال‌کنندگان نمی‌تواند بیشتر از 100 باشد',
            'offset.integer' => 'آفست باید عدد باشد',
            'offset.min' => 'آفست نمی‌تواند منفی باشد'
        ]);

        try {
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);

            $result = $this->socialService->getUserFollowers($userId, $limit, $offset);

            return response()->json([
                'success' => true,
                'message' => 'دنبال‌کنندگان کاربر دریافت شد',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت دنبال‌کنندگان: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's following
     */
    public function getUserFollowing(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0'
        ], [
            'limit.integer' => 'تعداد دنبال‌شده‌ها باید عدد باشد',
            'limit.min' => 'تعداد دنبال‌شده‌ها باید حداقل 1 باشد',
            'limit.max' => 'تعداد دنبال‌شده‌ها نمی‌تواند بیشتر از 100 باشد',
            'offset.integer' => 'آفست باید عدد باشد',
            'offset.min' => 'آفست نمی‌تواند منفی باشد'
        ]);

        try {
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);

            $result = $this->socialService->getUserFollowing($userId, $limit, $offset);

            return response()->json([
                'success' => true,
                'message' => 'دنبال‌شده‌های کاربر دریافت شد',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت دنبال‌شده‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's activity feed
     */
    public function getUserActivityFeed(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ], [
            'limit.integer' => 'تعداد فعالیت‌ها باید عدد باشد',
            'limit.min' => 'تعداد فعالیت‌ها باید حداقل 1 باشد',
            'limit.max' => 'تعداد فعالیت‌ها نمی‌تواند بیشتر از 50 باشد',
            'offset.integer' => 'آفست باید عدد باشد',
            'offset.min' => 'آفست نمی‌تواند منفی باشد'
        ]);

        try {
            $userId = auth()->id();
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);

            $result = $this->socialService->getUserActivityFeed($userId, $limit, $offset);

            return response()->json([
                'success' => true,
                'message' => 'فید فعالیت کاربر دریافت شد',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فید فعالیت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a playlist
     */
    public function createPlaylist(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'nullable|boolean',
            'is_collaborative' => 'nullable|boolean',
            'cover_image' => 'nullable|string|max:255'
        ], [
            'name.required' => 'نام پلی‌لیست الزامی است',
            'name.max' => 'نام پلی‌لیست نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'cover_image.max' => 'آدرس تصویر نمی‌تواند بیشتر از 255 کاراکتر باشد'
        ]);

        try {
            $userId = auth()->id();
            $playlistData = $request->only(['name', 'description', 'is_public', 'is_collaborative', 'cover_image']);
            $playlistData['metadata'] = $request->get('metadata');

            $result = $this->socialService->createPlaylist($userId, $playlistData);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد پلی‌لیست: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to playlist
     */
    public function addToPlaylist(Request $request, int $playlistId): JsonResponse
    {
        $request->validate([
            'item_type' => 'required|string|in:story,episode',
            'item_id' => 'required|integer'
        ], [
            'item_type.required' => 'نوع آیتم الزامی است',
            'item_type.in' => 'نوع آیتم نامعتبر است',
            'item_id.required' => 'شناسه آیتم الزامی است'
        ]);

        try {
            $userId = auth()->id();

            $result = $this->socialService->addToPlaylist(
                $userId,
                $playlistId,
                $request->get('item_type'),
                $request->get('item_id')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اضافه کردن به پلی‌لیست: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add comment
     */
    public function addComment(Request $request): JsonResponse
    {
        $request->validate([
            'commentable_type' => 'required|string|in:story,episode,playlist',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:user_comments,id'
        ], [
            'commentable_type.required' => 'نوع محتوا الزامی است',
            'commentable_type.in' => 'نوع محتوا نامعتبر است',
            'commentable_id.required' => 'شناسه محتوا الزامی است',
            'content.required' => 'متن نظر الزامی است',
            'content.max' => 'متن نظر نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'parent_id.exists' => 'نظر والد یافت نشد'
        ]);

        try {
            $userId = auth()->id();

            $result = $this->socialService->addComment(
                $userId,
                $request->get('commentable_type'),
                $request->get('commentable_id'),
                $request->get('content'),
                $request->get('parent_id')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال نظر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Like comment
     */
    public function likeComment(Request $request, int $commentId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $result = $this->socialService->likeComment($userId, $commentId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در لایک کردن نظر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's social statistics
     */
    public function getUserSocialStats(Request $request, int $userId): JsonResponse
    {
        try {
            $stats = $this->socialService->getUserSocialStats($userId);

            return response()->json([
                'success' => true,
                'message' => 'آمار اجتماعی کاربر دریافت شد',
                'data' => [
                    'stats' => $stats,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار اجتماعی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trending content based on social activity
     */
    public function getTrendingContent(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'limit.integer' => 'تعداد محتوا باید عدد باشد',
            'limit.min' => 'تعداد محتوا باید حداقل 1 باشد',
            'limit.max' => 'تعداد محتوا نمی‌تواند بیشتر از 50 باشد'
        ]);

        try {
            $limit = $request->get('limit', 20);
            $trendingContent = $this->socialService->getTrendingContent($limit);

            return response()->json([
                'success' => true,
                'message' => 'محتوای محبوب اجتماعی دریافت شد',
                'data' => [
                    'trending_content' => $trendingContent,
                    'total' => count($trendingContent)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت محتوای محبوب: ' . $e->getMessage()
            ], 500);
        }
    }
}