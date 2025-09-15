<?php

namespace App\Services;

use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\UserFollow;
use App\Models\ContentShare;
use App\Models\UserActivity;
use App\Models\UserPlaylist;
use App\Models\PlaylistItem;
use App\Models\UserComment;
use App\Models\CommentLike;
use App\Models\UserMention;
use App\Models\SocialInteraction;
use App\Models\UserSocialSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SocialService
{
    /**
     * Follow a user
     */
    public function followUser(int $followerId, int $followingId): array
    {
        try {
            // Check if already following
            $existingFollow = UserFollow::where('follower_id', $followerId)
                ->where('following_id', $followingId)
                ->first();

            if ($existingFollow) {
                return [
                    'success' => false,
                    'message' => 'شما قبلاً این کاربر را دنبال می‌کنید'
                ];
            }

            // Check if trying to follow self
            if ($followerId === $followingId) {
                return [
                    'success' => false,
                    'message' => 'نمی‌توانید خودتان را دنبال کنید'
                ];
            }

            DB::beginTransaction();

            // Create follow relationship
            $follow = UserFollow::create([
                'follower_id' => $followerId,
                'following_id' => $followingId,
                'followed_at' => now()
            ]);

            // Check if this creates a mutual follow
            $reverseFollow = UserFollow::where('follower_id', $followingId)
                ->where('following_id', $followerId)
                ->first();

            if ($reverseFollow) {
                $follow->update(['is_mutual' => true]);
                $reverseFollow->update(['is_mutual' => true]);
            }

            // Create activity
            $this->createActivity($followerId, 'follow', 'user', $followingId, 'کاربر جدیدی را دنبال کرد');

            // Create social interaction
            $this->createSocialInteraction($followerId, 'follow', 'user', $followingId);

            // Update analytics
            $this->updateSocialAnalytics('follows_gained', 'user', $followingId);

            DB::commit();

            // Clear cache
            $this->clearUserCache($followerId);
            $this->clearUserCache($followingId);

            return [
                'success' => true,
                'message' => 'کاربر با موفقیت دنبال شد',
                'data' => [
                    'follow' => $follow,
                    'is_mutual' => $follow->is_mutual
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error following user: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در دنبال کردن کاربر: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unfollow a user
     */
    public function unfollowUser(int $followerId, int $followingId): array
    {
        try {
            $follow = UserFollow::where('follower_id', $followerId)
                ->where('following_id', $followingId)
                ->first();

            if (!$follow) {
                return [
                    'success' => false,
                    'message' => 'شما این کاربر را دنبال نمی‌کنید'
                ];
            }

            DB::beginTransaction();

            // Update mutual follow status
            if ($follow->is_mutual) {
                $reverseFollow = UserFollow::where('follower_id', $followingId)
                    ->where('following_id', $followerId)
                    ->first();
                
                if ($reverseFollow) {
                    $reverseFollow->update(['is_mutual' => false]);
                }
            }

            // Delete follow relationship
            $follow->delete();

            // Create activity
            $this->createActivity($followerId, 'unfollow', 'user', $followingId, 'دنبال کردن کاربر را متوقف کرد');

            // Create social interaction
            $this->createSocialInteraction($followerId, 'unfollow', 'user', $followingId);

            DB::commit();

            // Clear cache
            $this->clearUserCache($followerId);
            $this->clearUserCache($followingId);

            return [
                'success' => true,
                'message' => 'دنبال کردن کاربر متوقف شد'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error unfollowing user: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در متوقف کردن دنبال: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Share content
     */
    public function shareContent(int $userId, string $shareableType, int $shareableId, array $shareData = []): array
    {
        try {
            $share = ContentShare::create([
                'user_id' => $userId,
                'shareable_type' => $shareableType,
                'shareable_id' => $shareableId,
                'share_type' => $shareData['share_type'] ?? 'link',
                'platform' => $shareData['platform'] ?? null,
                'message' => $shareData['message'] ?? null,
                'share_url' => $this->generateShareUrl($shareableType, $shareableId, $shareData),
                'metadata' => $shareData['metadata'] ?? null,
                'shared_at' => now()
            ]);

            // Create activity
            $activityDescription = $this->getShareActivityDescription($shareableType, $shareData);
            $this->createActivity($userId, 'share', $shareableType, $shareableId, $activityDescription);

            // Create social interaction
            $this->createSocialInteraction($userId, 'share', $shareableType, $shareableId);

            // Update analytics
            $this->updateSocialAnalytics('shares_count', $shareableType, $shareableId);

            // Clear cache
            $this->clearUserCache($userId);

            return [
                'success' => true,
                'message' => 'محتوا با موفقیت به اشتراک گذاشته شد',
                'data' => [
                    'share' => $share,
                    'share_url' => $share->share_url
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Error sharing content: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در به اشتراک گذاری محتوا: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's followers
     */
    public function getUserFollowers(int $userId, int $limit = 20, int $offset = 0): array
    {
        return Cache::remember("user_followers_{$userId}_{$limit}_{$offset}", 1800, function() use ($userId, $limit, $offset) {
            $followers = UserFollow::where('following_id', $userId)
                ->with(['follower:id,name,email,avatar'])
                ->orderBy('followed_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return [
                'followers' => $followers,
                'total' => UserFollow::where('following_id', $userId)->count(),
                'has_more' => $followers->count() === $limit
            ];
        });
    }

    /**
     * Get user's following
     */
    public function getUserFollowing(int $userId, int $limit = 20, int $offset = 0): array
    {
        return Cache::remember("user_following_{$userId}_{$limit}_{$offset}", 1800, function() use ($userId, $limit, $offset) {
            $following = UserFollow::where('follower_id', $userId)
                ->with(['following:id,name,email,avatar'])
                ->orderBy('followed_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return [
                'following' => $following,
                'total' => UserFollow::where('follower_id', $userId)->count(),
                'has_more' => $following->count() === $limit
            ];
        });
    }

    /**
     * Get user's activity feed
     */
    public function getUserActivityFeed(int $userId, int $limit = 20, int $offset = 0): array
    {
        return Cache::remember("user_activity_feed_{$userId}_{$limit}_{$offset}", 900, function() use ($userId, $limit, $offset) {
            // Get activities from users that the current user follows
            $followingIds = UserFollow::where('follower_id', $userId)
                ->pluck('following_id')
                ->toArray();

            $activities = UserActivity::whereIn('user_id', $followingIds)
                ->where('is_public', true)
                ->with(['user:id,name,avatar'])
                ->orderBy('activity_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return [
                'activities' => $activities,
                'has_more' => $activities->count() === $limit
            ];
        });
    }

    /**
     * Create a playlist
     */
    public function createPlaylist(int $userId, array $playlistData): array
    {
        try {
            $playlist = UserPlaylist::create([
                'user_id' => $userId,
                'name' => $playlistData['name'],
                'description' => $playlistData['description'] ?? null,
                'is_public' => $playlistData['is_public'] ?? false,
                'is_collaborative' => $playlistData['is_collaborative'] ?? false,
                'cover_image' => $playlistData['cover_image'] ?? null,
                'metadata' => $playlistData['metadata'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create activity
            $this->createActivity($userId, 'create_playlist', 'playlist', $playlist->id, 'پلی‌لیست جدیدی ایجاد کرد');

            // Clear cache
            $this->clearUserCache($userId);

            return [
                'success' => true,
                'message' => 'پلی‌لیست با موفقیت ایجاد شد',
                'data' => ['playlist' => $playlist]
            ];

        } catch (\Exception $e) {
            Log::error("Error creating playlist: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در ایجاد پلی‌لیست: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add item to playlist
     */
    public function addToPlaylist(int $userId, int $playlistId, string $itemType, int $itemId): array
    {
        try {
            $playlist = UserPlaylist::find($playlistId);
            if (!$playlist) {
                return [
                    'success' => false,
                    'message' => 'پلی‌لیست یافت نشد'
                ];
            }

            // Check if user owns playlist or if it's collaborative
            if ($playlist->user_id !== $userId && !$playlist->is_collaborative) {
                return [
                    'success' => false,
                    'message' => 'شما دسترسی به این پلی‌لیست ندارید'
                ];
            }

            // Check if item already exists in playlist
            $existingItem = PlaylistItem::where('playlist_id', $playlistId)
                ->where('item_type', $itemType)
                ->where('item_id', $itemId)
                ->first();

            if ($existingItem) {
                return [
                    'success' => false,
                    'message' => 'این آیتم قبلاً در پلی‌لیست موجود است'
                ];
            }

            // Get next sort order
            $maxOrder = PlaylistItem::where('playlist_id', $playlistId)->max('sort_order') ?? 0;

            $playlistItem = PlaylistItem::create([
                'playlist_id' => $playlistId,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'sort_order' => $maxOrder + 1,
                'added_at' => now()
            ]);

            // Create activity
            $this->createActivity($userId, 'add_to_playlist', 'playlist', $playlistId, 'آیتم جدیدی به پلی‌لیست اضافه کرد');

            // Clear cache
            $this->clearUserCache($userId);

            return [
                'success' => true,
                'message' => 'آیتم با موفقیت به پلی‌لیست اضافه شد',
                'data' => ['playlist_item' => $playlistItem]
            ];

        } catch (\Exception $e) {
            Log::error("Error adding to playlist: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در اضافه کردن به پلی‌لیست: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add comment
     */
    public function addComment(int $userId, string $commentableType, int $commentableId, string $content, int $parentId = null): array
    {
        try {
            $comment = UserComment::create([
                'user_id' => $userId,
                'commentable_type' => $commentableType,
                'commentable_id' => $commentableId,
                'parent_id' => $parentId,
                'content' => $content,
                'commented_at' => now(),
                'updated_at' => now()
            ]);

            // Update parent comment replies count if it's a reply
            if ($parentId) {
                UserComment::where('id', $parentId)->increment('replies_count');
            }

            // Create activity
            $activityDescription = $parentId ? 'پاسخ جدیدی ارسال کرد' : 'نظر جدیدی ارسال کرد';
            $this->createActivity($userId, 'comment', $commentableType, $commentableId, $activityDescription);

            // Create social interaction
            $this->createSocialInteraction($userId, 'comment', $commentableType, $commentableId);

            // Update analytics
            $this->updateSocialAnalytics('comments_count', $commentableType, $commentableId);

            // Clear cache
            $this->clearUserCache($userId);

            return [
                'success' => true,
                'message' => 'نظر با موفقیت ارسال شد',
                'data' => ['comment' => $comment]
            ];

        } catch (\Exception $e) {
            Log::error("Error adding comment: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در ارسال نظر: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Like comment
     */
    public function likeComment(int $userId, int $commentId): array
    {
        try {
            $existingLike = CommentLike::where('user_id', $userId)
                ->where('comment_id', $commentId)
                ->first();

            if ($existingLike) {
                return [
                    'success' => false,
                    'message' => 'شما قبلاً این نظر را لایک کرده‌اید'
                ];
            }

            CommentLike::create([
                'user_id' => $userId,
                'comment_id' => $commentId,
                'liked_at' => now()
            ]);

            // Update comment likes count
            UserComment::where('id', $commentId)->increment('likes_count');

            // Create social interaction
            $this->createSocialInteraction($userId, 'like_comment', 'comment', $commentId);

            return [
                'success' => true,
                'message' => 'نظر لایک شد'
            ];

        } catch (\Exception $e) {
            Log::error("Error liking comment: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در لایک کردن نظر: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get social statistics for user
     */
    public function getUserSocialStats(int $userId): array
    {
        return Cache::remember("user_social_stats_{$userId}", 3600, function() use ($userId) {
            $followersCount = UserFollow::where('following_id', $userId)->count();
            $followingCount = UserFollow::where('follower_id', $userId)->count();
            $sharesCount = ContentShare::where('user_id', $userId)->count();
            $commentsCount = UserComment::where('user_id', $userId)->count();
            $playlistsCount = UserPlaylist::where('user_id', $userId)->count();

            return [
                'followers_count' => $followersCount,
                'following_count' => $followingCount,
                'shares_count' => $sharesCount,
                'comments_count' => $commentsCount,
                'playlists_count' => $playlistsCount,
                'mutual_follows_count' => UserFollow::where('follower_id', $userId)
                    ->where('is_mutual', true)
                    ->count()
            ];
        });
    }

    /**
     * Get trending content based on social activity
     */
    public function getTrendingContent(int $limit = 20): array
    {
        return Cache::remember("trending_social_content_{$limit}", 1800, function() use ($limit) {
            $trendingStories = Story::where('status', 'published')
                ->withCount(['shares as recent_shares' => function($query) {
                    $query->where('shared_at', '>=', now()->subDays(7));
                }])
                ->withCount(['comments as recent_comments' => function($query) {
                    $query->where('commented_at', '>=', now()->subDays(7));
                }])
                ->withCount(['playlistItems as playlist_additions' => function($query) {
                    $query->where('added_at', '>=', now()->subDays(7));
                }])
                ->orderBy('recent_shares', 'desc')
                ->orderBy('recent_comments', 'desc')
                ->orderBy('playlist_additions', 'desc')
                ->limit($limit)
                ->get();

            return $trendingStories->map(function($story) {
                return [
                    'story' => $story,
                    'social_score' => $story->recent_shares + $story->recent_comments + $story->playlist_additions,
                    'trending_reason' => 'محتوای محبوب در شبکه‌های اجتماعی'
                ];
            })->toArray();
        });
    }

    /**
     * Create user activity
     */
    private function createActivity(int $userId, string $activityType, string $targetType, int $targetId, string $description): void
    {
        UserActivity::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_target_type' => $targetType,
            'activity_target_id' => $targetId,
            'activity_description' => $description,
            'activity_at' => now()
        ]);
    }

    /**
     * Create social interaction
     */
    private function createSocialInteraction(int $userId, string $interactionType, string $targetType, int $targetId): void
    {
        SocialInteraction::create([
            'user_id' => $userId,
            'interaction_type' => $interactionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'interacted_at' => now()
        ]);
    }

    /**
     * Update social analytics
     */
    private function updateSocialAnalytics(string $metricType, string $targetType, int $targetId): void
    {
        $today = now()->toDateString();
        
        DB::table('social_analytics')
            ->updateOrInsert(
                [
                    'metric_type' => $metricType,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'metric_date' => $today
                ],
                [
                    'metric_value' => DB::raw('metric_value + 1'),
                    'calculated_at' => now()
                ]
            );
    }

    /**
     * Generate share URL
     */
    private function generateShareUrl(string $shareableType, int $shareableId, array $shareData): string
    {
        $baseUrl = config('app.url');
        $shareType = $shareData['share_type'] ?? 'link';
        
        return match($shareType) {
            'link' => "{$baseUrl}/share/{$shareableType}/{$shareableId}",
            'embed' => "{$baseUrl}/embed/{$shareableType}/{$shareableId}",
            default => "{$baseUrl}/share/{$shareableType}/{$shareableId}"
        };
    }

    /**
     * Get share activity description
     */
    private function getShareActivityDescription(string $shareableType, array $shareData): string
    {
        $platform = $shareData['platform'] ?? 'لینک';
        $type = match($shareableType) {
            'story' => 'داستان',
            'episode' => 'قسمت',
            'playlist' => 'پلی‌لیست',
            default => 'محتوا'
        };

        return "{$type} را در {$platform} به اشتراک گذاشت";
    }

    /**
     * Clear user cache
     */
    private function clearUserCache(int $userId): void
    {
        $patterns = [
            "user_followers_{$userId}_*",
            "user_following_{$userId}_*",
            "user_activity_feed_{$userId}_*",
            "user_social_stats_{$userId}",
            "trending_social_content_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
