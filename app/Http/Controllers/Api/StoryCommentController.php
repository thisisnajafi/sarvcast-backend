<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoryComment;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StoryCommentController extends Controller
{
    /**
     * Get comments for a story
     */
    public function getComments(Request $request, int $storyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include_pending' => 'nullable|boolean',
            'sort_by' => 'nullable|in:latest,oldest,most_liked',
            'include_replies' => 'nullable|boolean'
        ], [
            'page.integer' => 'شماره صفحه باید عدد باشد',
            'page.min' => 'شماره صفحه باید حداقل 1 باشد',
            'per_page.integer' => 'تعداد در هر صفحه باید عدد باشد',
            'per_page.min' => 'تعداد در هر صفحه باید حداقل 1 باشد',
            'per_page.max' => 'تعداد در هر صفحه نمی‌تواند بیشتر از 100 باشد',
            'sort_by.in' => 'ترتیب باید یکی از: latest, oldest, most_liked باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'داده‌های ورودی نامعتبر',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $story = Story::findOrFail($storyId);
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $includePending = $request->get('include_pending', false);
            $sortBy = $request->get('sort_by', 'latest');
            $includeReplies = $request->get('include_replies', true);
            $userId = $request->user()?->id;

            $query = $story->comments()
                ->with(['user', 'replies.user'])
                ->topLevel(); // Only get top-level comments

            if (!$includePending) {
                $query->approved()->visible();
            }

            // Apply sorting
            switch ($sortBy) {
                case 'oldest':
                    $query->oldest();
                    break;
                case 'most_liked':
                    $query->mostLiked();
                    break;
                default:
                    $query->latest();
                    break;
            }

            $comments = $query->paginate($perPage, ['*'], 'page', $page);

            $commentsData = $comments->map(function($comment) use ($userId, $includeReplies) {
                return $comment->toApiResponse($userId);
            });

            return response()->json([
                'success' => true,
                'message' => 'نظرات داستان دریافت شد',
                'data' => [
                    'story_id' => $storyId,
                    'comments' => $commentsData,
                    'pagination' => [
                        'current_page' => $comments->currentPage(),
                        'per_page' => $comments->perPage(),
                        'total' => $comments->total(),
                        'last_page' => $comments->lastPage(),
                        'has_more' => $comments->hasMorePages()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت نظرات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a comment to a story
     */
    public function addComment(Request $request, int $storyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:1|max:1000',
            'parent_id' => 'nullable|integer|exists:story_comments,id',
            'metadata' => 'nullable|array'
        ], [
            'content.required' => 'متن نظر الزامی است',
            'content.string' => 'متن نظر باید رشته باشد',
            'content.min' => 'متن نظر نمی‌تواند خالی باشد',
            'content.max' => 'متن نظر نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'parent_id.exists' => 'نظر والد یافت نشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'داده‌های ورودی نامعتبر',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $story = Story::findOrFail($storyId);
            $user = $request->user();

            // Check if user has already commented on this story recently (rate limiting)
            $recentComment = StoryComment::where('story_id', $storyId)
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($recentComment) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما نمی‌توانید در کمتر از 2 دقیقه نظر جدیدی ارسال کنید'
                ], 429);
            }

            // If this is a reply, validate parent comment
            $parentId = $request->get('parent_id');
            if ($parentId) {
                $parentComment = StoryComment::where('id', $parentId)
                    ->where('story_id', $storyId)
                    ->approved()
                    ->visible()
                    ->first();

                if (!$parentComment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'نظر والد یافت نشد یا تایید نشده است'
                    ], 404);
                }
            }

            $comment = StoryComment::create([
                'story_id' => $storyId,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'content' => $request->get('content'),
                'is_approved' => true, // Auto-approve for now
                'is_visible' => true,
                'metadata' => $request->get('metadata', [])
            ]);

            // If this is a reply, increment parent's replies count
            if ($parentId) {
                $parentComment->incrementRepliesCount();
            }

            $comment->load(['user', 'parent']);

            return response()->json([
                'success' => true,
                'message' => $parentId ? 'پاسخ شما با موفقیت ارسال شد' : 'نظر شما با موفقیت ارسال شد',
                'data' => [
                    'comment' => $comment->toApiResponse($user->id)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال نظر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Like or unlike a comment
     */
    public function toggleLike(Request $request, int $commentId): JsonResponse
    {
        try {
            $user = $request->user();
            $comment = StoryComment::findOrFail($commentId);

            if (!$comment->isApproved() || !$comment->isVisible()) {
                return response()->json([
                    'success' => false,
                    'message' => 'نظر یافت نشد یا قابل لایک نیست'
                ], 404);
            }

            $isLiked = $comment->isLikedBy($user->id);
            
            if ($isLiked) {
                $comment->unlike($user->id);
                $message = 'لایک شما برداشته شد';
            } else {
                $comment->like($user->id);
                $message = 'نظر لایک شد';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'comment_id' => $commentId,
                    'likes_count' => $comment->fresh()->likes_count,
                    'is_liked' => !$isLiked
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در لایک نظر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's comments
     */
    public function getUserComments(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'story_id' => 'nullable|integer|exists:stories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'داده‌های ورودی نامعتبر',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $storyId = $request->get('story_id');

            $query = StoryComment::where('user_id', $user->id)
                ->with(['story:id,title,image_url', 'user']);

            if ($storyId) {
                $query->where('story_id', $storyId);
            }

            $comments = $query->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            $commentsData = $comments->map(function($comment) {
                $data = $comment->toApiResponse($comment->user_id);
                $data['story'] = [
                    'id' => $comment->story->id,
                    'title' => $comment->story->title,
                    'image_url' => $comment->story->image_url
                ];
                return $data;
            });

            return response()->json([
                'success' => true,
                'message' => 'نظرات کاربر دریافت شد',
                'data' => [
                    'comments' => $commentsData,
                    'pagination' => [
                        'current_page' => $comments->currentPage(),
                        'per_page' => $comments->perPage(),
                        'total' => $comments->total(),
                        'last_page' => $comments->lastPage(),
                        'has_more' => $comments->hasMorePages()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت نظرات کاربر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comment statistics for a story
     */
    public function getCommentStatistics(int $storyId): JsonResponse
    {
        try {
            $story = Story::findOrFail($storyId);

            $totalComments = $story->comments()->count();
            $approvedComments = $story->comments()->approved()->count();
            $pendingComments = $story->comments()->pending()->count();
            $pinnedComments = $story->comments()->pinned()->count();
            $recentComments = $story->comments()
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'آمار نظرات داستان دریافت شد',
                'data' => [
                    'story_id' => $storyId,
                    'statistics' => [
                        'total_comments' => $totalComments,
                        'approved_comments' => $approvedComments,
                        'pending_comments' => $pendingComments,
                        'pinned_comments' => $pinnedComments,
                        'recent_comments' => $recentComments
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار نظرات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user's own comment
     */
    public function deleteComment(Request $request, int $commentId): JsonResponse
    {
        try {
            $user = $request->user();
            $comment = StoryComment::where('id', $commentId)
                ->where('user_id', $user->id)
                ->first();

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'نظر یافت نشد یا شما مجاز به حذف آن نیستید'
                ], 404);
            }

            // Check if comment is recent (within 2 hours)
            if ($comment->created_at->diffInHours(now()) > 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'نمی‌توانید نظرات قدیمی‌تر از 2 ساعت را حذف کنید'
                ], 403);
            }

            // If this is a reply, decrement parent's replies count
            if ($comment->parent_id) {
                $parentComment = StoryComment::find($comment->parent_id);
                if ($parentComment) {
                    $parentComment->decrementRepliesCount();
                }
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'نظر با موفقیت حذف شد'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف نظر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin delete comment (admins can delete any comment)
     */
    public function adminDeleteComment(Request $request, int $commentId): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is admin or super admin
            if (!$user->isAdmin() && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز. شما مجوز حذف نظرات را ندارید.'
                ], 403);
            }

            $comment = StoryComment::findOrFail($commentId);

            // If this is a reply, decrement parent's replies count
            if ($comment->parent_id) {
                $parentComment = StoryComment::find($comment->parent_id);
                if ($parentComment) {
                    $parentComment->decrementRepliesCount();
                }
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'نظر با موفقیت حذف شد'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف نظر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get replies for a specific comment
     */
    public function getReplies(Request $request, int $commentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'داده‌های ورودی نامعتبر',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $comment = StoryComment::findOrFail($commentId);
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $userId = $request->user()?->id;

            $replies = $comment->replies()
                ->with('user')
                ->approved()
                ->visible()
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            $repliesData = $replies->map(function($reply) use ($userId) {
                return $reply->toApiResponse($userId);
            });

            return response()->json([
                'success' => true,
                'message' => 'پاسخ‌های نظر دریافت شد',
                'data' => [
                    'comment_id' => $commentId,
                    'replies' => $repliesData,
                    'pagination' => [
                        'current_page' => $replies->currentPage(),
                        'per_page' => $replies->perPage(),
                        'total' => $replies->total(),
                        'last_page' => $replies->lastPage(),
                        'has_more' => $replies->hasMorePages()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پاسخ‌ها: ' . $e->getMessage()
            ], 500);
        }
    }
}