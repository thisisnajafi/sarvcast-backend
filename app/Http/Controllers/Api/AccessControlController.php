<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AccessControlController extends Controller
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Get user's access level and subscription status
     */
    public function getUserAccessLevel(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $accessLevel = $this->accessControlService->getUserAccessLevel($user->id);

            return response()->json([
                'success' => true,
                'data' => $accessLevel,
                'message' => 'سطح دسترسی کاربر دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user access level', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت سطح دسترسی'
            ], 500);
        }
    }

    /**
     * Check access to specific story
     */
    public function checkStoryAccess(Request $request, int $storyId): JsonResponse
    {
        try {
            $user = $request->user();
            $accessInfo = $this->accessControlService->canAccessStory($user->id, $storyId);

            return response()->json([
                'success' => true,
                'data' => $accessInfo,
                'message' => 'بررسی دسترسی به داستان انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check story access', [
                'user_id' => $request->user()->id,
                'story_id' => $storyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بررسی دسترسی به داستان'
            ], 500);
        }
    }

    /**
     * Check access to specific episode
     */
    public function checkEpisodeAccess(Request $request, int $episodeId): JsonResponse
    {
        try {
            $user = $request->user();
            $accessInfo = $this->accessControlService->canAccessEpisode($user->id, $episodeId);

            return response()->json([
                'success' => true,
                'data' => $accessInfo,
                'message' => 'بررسی دسترسی به قسمت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check episode access', [
                'user_id' => $request->user()->id,
                'episode_id' => $episodeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بررسی دسترسی به قسمت'
            ], 500);
        }
    }

    /**
     * Check download access for content
     */
    public function checkDownloadAccess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content_type' => 'required|string|in:story,episode',
            'content_id' => 'required|integer|min:1'
        ], [
            'content_type.required' => 'نوع محتوا الزامی است',
            'content_type.in' => 'نوع محتوا باید story یا episode باشد',
            'content_id.required' => 'شناسه محتوا الزامی است',
            'content_id.integer' => 'شناسه محتوا باید عدد باشد',
            'content_id.min' => 'شناسه محتوا باید بزرگتر از صفر باشد'
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
            $contentType = $request->input('content_type');
            $contentId = $request->input('content_id');

            $accessInfo = $this->accessControlService->canDownloadContent($user->id, $contentType, $contentId);

            return response()->json([
                'success' => true,
                'data' => $accessInfo,
                'message' => 'بررسی دسترسی دانلود انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check download access', [
                'user_id' => $request->user()->id,
                'content_type' => $request->input('content_type'),
                'content_id' => $request->input('content_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بررسی دسترسی دانلود'
            ], 500);
        }
    }

    /**
     * Get premium features access
     */
    public function getPremiumFeatures(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $features = $this->accessControlService->canAccessPremiumFeatures($user->id);

            return response()->json([
                'success' => true,
                'data' => $features,
                'message' => 'دسترسی به ویژگی‌های پریمیوم دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get premium features', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت ویژگی‌های پریمیوم'
            ], 500);
        }
    }

    /**
     * Get access statistics (admin only)
     */
    public function getAccessStatistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->accessControlService->getAccessStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار دسترسی‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get access statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار دسترسی‌ها'
            ], 500);
        }
    }

    /**
     * Validate content access for API requests
     */
    public function validateContentAccess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content_type' => 'required|string|in:story,episode',
            'content_id' => 'required|integer|min:1'
        ], [
            'content_type.required' => 'نوع محتوا الزامی است',
            'content_type.in' => 'نوع محتوا باید story یا episode باشد',
            'content_id.required' => 'شناسه محتوا الزامی است',
            'content_id.integer' => 'شناسه محتوا باید عدد باشد',
            'content_id.min' => 'شناسه محتوا باید بزرگتر از صفر باشد'
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
            $contentType = $request->input('content_type');
            $contentId = $request->input('content_id');

            $accessInfo = $this->accessControlService->validateContentAccess($user->id, $contentType, $contentId);

            return response()->json([
                'success' => true,
                'data' => $accessInfo,
                'message' => 'اعتبارسنجی دسترسی انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to validate content access', [
                'user_id' => $request->user()->id,
                'content_type' => $request->input('content_type'),
                'content_id' => $request->input('content_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی دسترسی'
            ], 500);
        }
    }

    /**
     * Get content with access filtering
     */
    public function getFilteredContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content_type' => 'required|string|in:stories,episodes',
            'story_id' => 'nullable|integer|min:1'
        ], [
            'content_type.required' => 'نوع محتوا الزامی است',
            'content_type.in' => 'نوع محتوا باید stories یا episodes باشد',
            'story_id.integer' => 'شناسه داستان باید عدد باشد',
            'story_id.min' => 'شناسه داستان باید بزرگتر از صفر باشد'
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
            $contentType = $request->input('content_type');
            $storyId = $request->input('story_id');

            if ($contentType === 'stories') {
                $stories = \App\Models\Story::where('status', 'published')->get();
                $filteredContent = $this->accessControlService->filterStoriesByAccess($user->id, $stories);
            } else {
                $query = \App\Models\Episode::where('status', 'published');
                if ($storyId) {
                    $query->where('story_id', $storyId);
                }
                $episodes = $query->get();
                $filteredContent = $this->accessControlService->filterEpisodesByAccess($user->id, $episodes);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'content' => $filteredContent,
                    'content_type' => $contentType,
                    'total_count' => count($filteredContent)
                ],
                'message' => 'محتوای فیلتر شده دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get filtered content', [
                'user_id' => $request->user()->id,
                'content_type' => $request->input('content_type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت محتوای فیلتر شده'
            ], 500);
        }
    }
}
