<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoryComment;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class StoryCommentController extends Controller
{
    /**
     * Get comments for a story
     */
    public function getComments(Request $request, int $storyId): JsonResponse
    {
        Log::info('Getting comments for story', [
            'story_id' => $storyId,
            'request_data' => $request->all(),
            'user_id' => $request->user()?->id,
        ]);

        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include_pending' => 'nullable|boolean',
            'sort_by' => 'nullable|in:latest,oldest,most_liked',
        ], [
            'page.integer' => 'شماره صفحه باید عدد باشد',
            'page.min' => 'شماره صفحه باید حداقل 1 باشد',
            'per_page.integer' => 'تعداد در هر صفحه باید عدد باشد',
            'per_page.min' => 'تعداد در هر صفحه باید حداقل 1 باشد',
            'per_page.max' => 'تعداد در هر صفحه نمی‌تواند بیشتر از 100 باشد',
            'sort_by.in' => 'ترتیب باید یکی از: latest, oldest, most_liked باشد'
        ]);

        if ($validator->fails()) {
            Log::warning('Comment validation failed', [
                'story_id' => $storyId,
                'errors' => $validator->errors()->toArray(),
            ]);
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
            $includePending = $request->boolean('include_pending', false);
            $sortBy = $request->get('sort_by', 'latest');
            // Always include replies in the response (removed from query params)
            $includeReplies = true;
            $userId = $request->user()?->id;

            Log::info('Story found, querying comments', [
                'story_id' => $storyId,
                'page' => $page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'include_pending' => $includePending,
                'include_replies' => $includeReplies,
            ]);

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

            Log::info('Comments retrieved successfully', [
                'story_id' => $storyId,
                'comments_count' => $comments->count(),
                'total_comments' => $comments->total(),
            ]);

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
            Log::error('Error getting comments', [
                'story_id' => $storyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
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
        Log::info('Adding comment to story', [
            'story_id' => $storyId,
            'request_data' => $request->all(),
            'request_headers' => $request->headers->all(),
            'user_id' => $request->user()?->id,
        ]);

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|integer|exists:story_comments,id',
            'rating' => 'nullable|integer|min:1|max:5',
            'metadata' => 'nullable|array'
        ], [
            'content.string' => 'متن نظر باید رشته باشد',
            'content.max' => 'متن نظر نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'parent_id.exists' => 'نظر والد یافت نشد',
            'rating.integer' => 'امتیاز باید عدد باشد',
            'rating.min' => 'امتیاز نمی‌تواند کمتر از 1 باشد',
            'rating.max' => 'امتیاز نمی‌تواند بیشتر از 5 باشد'
        ]);

        // Content or rating must be provided
        if (empty($request->get('content')) && empty($request->get('rating'))) {
            Log::warning('Comment submission failed: content and rating both empty', [
                'story_id' => $storyId,
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حداقل یکی از فیلدهای نظر یا امتیاز باید پر شود',
                'errors' => ['content' => ['متن نظر یا امتیاز الزامی است']]
            ], 422);
        }

        if ($validator->fails()) {
            Log::warning('Comment validation failed', [
                'story_id' => $storyId,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'داده‌های ورودی نامعتبر',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info('Validating story and user', [
                'story_id' => $storyId,
                'user_id' => $request->user()?->id,
            ]);

            $story = Story::findOrFail($storyId);
            $user = $request->user();

            if (!$user) {
                Log::error('User not authenticated', [
                    'story_id' => $storyId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است'
                ], 401);
            }

            Log::info('Story and user validated', [
                'story_id' => $storyId,
                'story_title' => $story->title,
                'user_id' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
            ]);

            // Check if user has already commented on this story recently (rate limiting)
            $recentComment = StoryComment::where('story_id', $storyId)
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($recentComment) {
                Log::info('Rate limit hit for user comment', [
                    'story_id' => $storyId,
                    'user_id' => $user->id,
                    'recent_comment_id' => $recentComment->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'شما نمی‌توانید در کمتر از 2 دقیقه نظر جدیدی ارسال کنید'
                ], 429);
            }

            // If this is a reply, validate parent comment
            $parentId = $request->get('parent_id');
            Log::info('Processing comment data', [
                'story_id' => $storyId,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'has_content' => !empty($request->get('content')),
                'content_length' => strlen($request->get('content', '')),
                'has_rating' => $request->has('rating'),
                'rating_value' => $request->get('rating'),
            ]);

            if ($parentId) {
                $parentComment = StoryComment::where('id', $parentId)
                    ->where('story_id', $storyId)
                    ->approved()
                    ->visible()
                    ->first();

                if (!$parentComment) {
                    Log::warning('Parent comment not found or not approved', [
                        'story_id' => $storyId,
                        'parent_id' => $parentId,
                        'user_id' => $user->id,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'نظر والد یافت نشد یا تایید نشده است'
                    ], 404);
                }
                Log::info('Parent comment validated', [
                    'parent_id' => $parentId,
                    'parent_comment_id' => $parentComment->id,
                ]);
            }

            // Rating can only be set on top-level comments (not replies)
            $rating = null;
            if (!$parentId && $request->has('rating') && $request->get('rating') !== null) {
                $rating = (int) $request->get('rating');
                Log::info('Rating provided for top-level comment', [
                    'rating' => $rating,
                    'rating_type' => gettype($rating),
                ]);
            }

            $content = $request->get('content', '');
            // Ensure content is a string, even if empty
            if ($content === null) {
                $content = '';
            }

            Log::info('Creating comment', [
                'story_id' => $storyId,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'content_length' => strlen($content),
                'content_preview' => substr($content, 0, 50),
                'rating' => $rating,
                'metadata' => $request->get('metadata', []),
            ]);

            $commentData = [
                'story_id' => $storyId,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'content' => $content,
                'is_approved' => true, // Auto-approve for now
                'is_visible' => true,
                'metadata' => $request->get('metadata', [])
            ];

            // Only add rating if it's not null
            if ($rating !== null) {
                $commentData['rating'] = $rating;
            }

            Log::info('Comment data prepared', [
                'comment_data' => $commentData,
            ]);

            $comment = StoryComment::create($commentData);

            Log::info('Comment created successfully', [
                'comment_id' => $comment->id,
                'story_id' => $storyId,
                'user_id' => $user->id,
            ]);

            // If rating is provided, create/update StoryRating
            if ($rating !== null) {
                Log::info('Creating/updating story rating', [
                    'story_id' => $storyId,
                    'user_id' => $user->id,
                    'rating' => $rating,
                ]);

                try {
                    $storyRating = \App\Models\StoryRating::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'story_id' => $storyId,
                        ],
                        [
                            'rating' => (float) $rating, // Convert to float for decimal column
                            'review' => $content,
                        ]
                    );

                    Log::info('Story rating created/updated', [
                        'story_rating_id' => $storyRating->id,
                        'story_id' => $storyId,
                        'user_id' => $user->id,
                    ]);

                    // Update story's average rating
                    $this->updateStoryRatingStats($story);

                    Log::info('Story rating stats updated', [
                        'story_id' => $storyId,
                    ]);
                } catch (\Exception $ratingError) {
                    Log::error('Error creating/updating story rating', [
                        'story_id' => $storyId,
                        'user_id' => $user->id,
                        'rating' => $rating,
                        'error' => $ratingError->getMessage(),
                        'trace' => $ratingError->getTraceAsString(),
                    ]);
                    // Don't fail the comment creation if rating fails
                }
            }

            // If this is a reply, increment parent's replies count
            if ($parentId) {
                try {
                    $parentComment->incrementRepliesCount();
                    Log::info('Parent comment replies count incremented', [
                        'parent_id' => $parentId,
                    ]);
                } catch (\Exception $replyError) {
                    Log::error('Error incrementing parent replies count', [
                        'parent_id' => $parentId,
                        'error' => $replyError->getMessage(),
                    ]);
                    // Don't fail the comment creation if increment fails
                }
            }

            Log::info('Loading comment relationships', [
                'comment_id' => $comment->id,
            ]);

            $comment->load(['user', 'parent']);

            Log::info('Comment submission successful', [
                'comment_id' => $comment->id,
                'story_id' => $storyId,
                'user_id' => $user->id,
                'is_reply' => $parentId !== null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $parentId ? 'پاسخ شما با موفقیت ارسال شد' : 'نظر شما با موفقیت ارسال شد',
                'data' => [
                    'comment' => $comment->toApiResponse($user->id)
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error adding comment', [
                'story_id' => $storyId,
                'user_id' => $request->user()?->id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception_class' => get_class($e),
            ]);
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

    /**
     * Update story rating statistics
     */
    private function updateStoryRatingStats(Story $story): void
    {
        try {
            Log::info('Updating story rating stats', [
                'story_id' => $story->id,
            ]);

            $totalRatings = \App\Models\StoryRating::where('story_id', $story->id)->count();
            $averageRating = \App\Models\StoryRating::where('story_id', $story->id)->avg('rating') ?? 0;

            Log::info('Story rating stats calculated', [
                'story_id' => $story->id,
                'total_ratings' => $totalRatings,
                'average_rating' => $averageRating,
            ]);

            $story->update([
                'total_ratings' => $totalRatings,
                'avg_rating' => round($averageRating, 2)
            ]);

            Log::info('Story rating stats updated successfully', [
                'story_id' => $story->id,
                'total_ratings' => $totalRatings,
                'avg_rating' => round($averageRating, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating story rating stats', [
                'story_id' => $story->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }
}