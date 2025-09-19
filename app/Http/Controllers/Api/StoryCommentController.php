<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoryComment;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StoryCommentController extends Controller
{
    /**
     * Get comments for a story
     */
    public function getComments(Request $request, int $storyId): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include_pending' => 'nullable|boolean'
        ], [
            'page.integer' => 'شماره صفحه باید عدد باشد',
            'page.min' => 'شماره صفحه باید حداقل 1 باشد',
            'per_page.integer' => 'تعداد در هر صفحه باید عدد باشد',
            'per_page.min' => 'تعداد در هر صفحه باید حداقل 1 باشد',
            'per_page.max' => 'تعداد در هر صفحه نمی‌تواند بیشتر از 100 باشد'
        ]);

        try {
            $story = Story::findOrFail($storyId);
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $includePending = $request->get('include_pending', false);

            $query = $story->comments()->with('user');

            if (!$includePending) {
                $query->approved()->visible();
            }

            $comments = $query->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            $commentsData = $comments->map(function($comment) {
                return $comment->toApiResponse();
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
        $request->validate([
            'comment' => 'required|string|min:1|max:1000'
        ], [
            'comment.required' => 'نظر الزامی است',
            'comment.string' => 'نظر باید متن باشد',
            'comment.min' => 'نظر نمی‌تواند خالی باشد',
            'comment.max' => 'نظر نمی‌تواند بیشتر از 1000 کاراکتر باشد'
        ]);

        try {
            $story = Story::findOrFail($storyId);
            $user = $request->user();

            // Check if user has already commented on this story recently (rate limiting)
            $recentComment = StoryComment::where('story_id', $storyId)
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();

            if ($recentComment) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما نمی‌توانید در کمتر از 5 دقیقه نظر جدیدی ارسال کنید'
                ], 429);
            }

            $comment = StoryComment::create([
                'story_id' => $storyId,
                'user_id' => $user->id,
                'comment' => $request->get('comment'),
                'is_approved' => false, // Comments need approval
                'is_visible' => true
            ]);

            $comment->load('user');

            return response()->json([
                'success' => true,
                'message' => 'نظر شما با موفقیت ارسال شد و در انتظار تایید است',
                'data' => [
                    'comment' => $comment->toApiResponse()
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
     * Get user's comments
     */
    public function getUserComments(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $user = $request->user();
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            $comments = StoryComment::where('user_id', $user->id)
                ->with(['story:id,title,image_url', 'user'])
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            $commentsData = $comments->map(function($comment) {
                $data = $comment->toApiResponse();
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

            // Check if comment is recent (within 1 hour)
            if ($comment->created_at->diffInHours(now()) > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'نمی‌توانید نظرات قدیمی‌تر از 1 ساعت را حذف کنید'
                ], 403);
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
}