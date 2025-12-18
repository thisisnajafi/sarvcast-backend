<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    /**
     * Get user's favorites
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $favorites = Favorite::getUserFavorites($user->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'favorites' => $favorites->items(),
                    'pagination' => [
                        'current_page' => $favorites->currentPage(),
                        'last_page' => $favorites->lastPage(),
                        'per_page' => $favorites->perPage(),
                        'total' => $favorites->total(),
                        'has_more' => $favorites->hasMorePages()
                    ]
                ],
                'message' => 'لیست علاقه‌مندی‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user favorites', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست علاقه‌مندی‌ها'
            ], 500);
        }
    }

    /**
     * Add story to favorites
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'story_id' => 'required|integer|exists:stories,id'
        ], [
            'story_id.required' => 'شناسه داستان الزامی است',
            'story_id.integer' => 'شناسه داستان باید عدد باشد',
            'story_id.exists' => 'داستان یافت نشد'
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

            // Check if story exists and is published
            $story = Story::where('id', $storyId)
                         ->where('status', 'published')
                         ->first();

            if (!$story) {
                return response()->json([
                    'success' => false,
                    'message' => 'داستان یافت نشد یا منتشر نشده است'
                ], 404);
            }

            // Check if already favorited
            if (Favorite::isFavorited($user->id, $storyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'این داستان قبلاً به علاقه‌مندی‌ها اضافه شده است'
                ], 409);
            }

            $added = Favorite::addToFavorites($user->id, $storyId);

            if ($added) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'story_id' => $storyId,
                        'is_favorited' => true,
                        'favorite_count' => Favorite::getStoryFavoriteCount($storyId)
                    ],
                    'message' => 'داستان به علاقه‌مندی‌ها اضافه شد'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اضافه کردن به علاقه‌مندی‌ها'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to add favorite', [
                'user_id' => $request->user()->id,
                'story_id' => $request->input('story_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در اضافه کردن به علاقه‌مندی‌ها'
            ], 500);
        }
    }

    /**
     * Remove story from favorites
     */
    public function destroy(Request $request, int $storyId): JsonResponse
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

            // Check if favorited
            if (!Favorite::isFavorited($user->id, $storyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'این داستان در علاقه‌مندی‌ها نیست'
                ], 404);
            }

            $removed = Favorite::removeFromFavorites($user->id, $storyId);

            if ($removed) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'story_id' => $storyId,
                        'is_favorited' => false,
                        'favorite_count' => Favorite::getStoryFavoriteCount($storyId)
                    ],
                    'message' => 'داستان از علاقه‌مندی‌ها حذف شد'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در حذف از علاقه‌مندی‌ها'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to remove favorite', [
                'user_id' => $request->user()->id,
                'story_id' => $storyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف از علاقه‌مندی‌ها'
            ], 500);
        }
    }

    /**
     * Toggle favorite status
     */
    public function toggle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'story_id' => 'required|integer|exists:stories,id'
        ], [
            'story_id.required' => 'شناسه داستان الزامی است',
            'story_id.integer' => 'شناسه داستان باید عدد باشد',
            'story_id.exists' => 'داستان یافت نشد'
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

            // Check if story exists and is published
            $story = Story::where('id', $storyId)
                         ->where('status', 'published')
                         ->first();

            if (!$story) {
                return response()->json([
                    'success' => false,
                    'message' => 'داستان یافت نشد یا منتشر نشده است'
                ], 404);
            }

            $result = Favorite::toggleFavorite($user->id, $storyId);

            if ($result['success']) {
                $message = $result['action'] === 'added' 
                    ? 'داستان به علاقه‌مندی‌ها اضافه شد' 
                    : 'داستان از علاقه‌مندی‌ها حذف شد';

                return response()->json([
                    'success' => true,
                    'data' => [
                        'story_id' => $storyId,
                        'action' => $result['action'],
                        'is_favorited' => $result['is_favorited'],
                        'favorite_count' => Favorite::getStoryFavoriteCount($storyId)
                    ],
                    'message' => $message
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در تغییر وضعیت علاقه‌مندی'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to toggle favorite', [
                'user_id' => $request->user()->id,
                'story_id' => $request->input('story_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در تغییر وضعیت علاقه‌مندی'
            ], 500);
        }
    }

    /**
     * Check if story is favorited
     */
    public function check(Request $request, int $storyId): JsonResponse
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

            $isFavorited = Favorite::isFavorited($user->id, $storyId);
            $favoriteCount = Favorite::getStoryFavoriteCount($storyId);

            return response()->json([
                'success' => true,
                'data' => [
                    'story_id' => $storyId,
                    'is_favorited' => $isFavorited,
                    'favorite_count' => $favoriteCount
                ],
                'message' => $isFavorited ? 'داستان در علاقه‌مندی‌ها است' : 'داستان در علاقه‌مندی‌ها نیست'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check favorite status', [
                'user_id' => $request->user()->id,
                'story_id' => $storyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بررسی وضعیت علاقه‌مندی'
            ], 500);
        }
    }

    /**
     * Get most favorited stories
     */
    public function mostFavorited(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $limit = min($limit, 50); // Max 50

            $mostFavorited = Favorite::getMostFavorited($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'stories' => $mostFavorited,
                    'limit' => $limit
                ],
                'message' => 'محبوب‌ترین داستان‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get most favorited stories', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت محبوب‌ترین داستان‌ها'
            ], 500);
        }
    }

    /**
     * Get favorite statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = Favorite::getFavoriteStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار علاقه‌مندی‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get favorite stats', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار علاقه‌مندی‌ها'
            ], 500);
        }
    }

    /**
     * Bulk add/remove favorites
     */
    public function bulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:add,remove',
            'story_ids' => 'required|array|min:1|max:50',
            'story_ids.*' => 'integer|exists:stories,id'
        ], [
            'action.required' => 'عملیات الزامی است',
            'action.in' => 'عملیات نامعتبر است',
            'story_ids.required' => 'شناسه‌های داستان الزامی است',
            'story_ids.array' => 'شناسه‌های داستان باید آرایه باشد',
            'story_ids.min' => 'حداقل یک داستان انتخاب کنید',
            'story_ids.max' => 'حداکثر 50 داستان انتخاب کنید',
            'story_ids.*.integer' => 'شناسه داستان باید عدد باشد',
            'story_ids.*.exists' => 'یکی از داستان‌ها یافت نشد'
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
            $action = $request->input('action');
            $storyIds = $request->input('story_ids');

            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($storyIds as $storyId) {
                try {
                    if ($action === 'add') {
                        $success = Favorite::addToFavorites($user->id, $storyId);
                        $results[] = [
                            'story_id' => $storyId,
                            'action' => 'add',
                            'success' => $success,
                            'message' => $success ? 'اضافه شد' : 'قبلاً اضافه شده بود'
                        ];
                    } else {
                        $success = Favorite::removeFromFavorites($user->id, $storyId);
                        $results[] = [
                            'story_id' => $storyId,
                            'action' => 'remove',
                            'success' => $success,
                            'message' => $success ? 'حذف شد' : 'در علاقه‌مندی‌ها نبود'
                        ];
                    }

                    if ($success) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }

                } catch (\Exception $e) {
                    $results[] = [
                        'story_id' => $storyId,
                        'action' => $action,
                        'success' => false,
                        'message' => 'خطا در پردازش'
                    ];
                    $errorCount++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'action' => $action,
                    'total_processed' => count($storyIds),
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'results' => $results
                ],
                'message' => "عملیات {$action} برای {$successCount} داستان با موفقیت انجام شد"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to perform bulk favorite operation', [
                'user_id' => $request->user()->id,
                'action' => $request->input('action'),
                'story_ids' => $request->input('story_ids'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی'
            ], 500);
        }
    }
}
