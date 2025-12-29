<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StoryComment extends Model
{
    protected $fillable = [
        'story_id',
        'user_id',
        'parent_id',
        'content',
        'rating',
        'is_approved',
        'is_visible',
        'is_pinned',
        'likes_count',
        'replies_count',
        'metadata',
        'approved_at',
        'approved_by'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'is_visible' => 'boolean',
        'is_pinned' => 'boolean',
        'likes_count' => 'integer',
        'replies_count' => 'integer',
        'metadata' => 'array',
        'approved_at' => 'datetime'
    ];

    /**
     * Get the story that owns the comment
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the user that wrote the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved the comment
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the parent comment (for nested comments)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(StoryComment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment
     */
    public function replies(): HasMany
    {
        return $this->hasMany(StoryComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get users who liked this comment
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'story_comment_likes', 'comment_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include approved comments
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include visible comments
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope a query to only include pending comments
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope a query to only include pinned comments
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to only include top-level comments (no parent)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include comments for a specific story
     */
    public function scopeForStory($query, int $storyId)
    {
        return $query->where('story_id', $storyId);
    }

    /**
     * Scope a query to order comments by creation date
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to order comments by creation date (oldest first)
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to order comments by likes count
     */
    public function scopeMostLiked($query)
    {
        return $query->orderBy('likes_count', 'desc');
    }

    /**
     * Approve the comment
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy
        ]);
    }

    /**
     * Reject the comment
     */
    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'is_visible' => false
        ]);
    }

    /**
     * Pin the comment
     */
    public function pin(): void
    {
        $this->update(['is_pinned' => true]);
    }

    /**
     * Unpin the comment
     */
    public function unpin(): void
    {
        $this->update(['is_pinned' => false]);
    }

    /**
     * Like the comment
     */
    public function like(int $userId): bool
    {
        if ($this->likes()->where('user_id', $userId)->exists()) {
            return false; // Already liked
        }

        $this->likes()->attach($userId);
        $this->increment('likes_count');
        
        return true;
    }

    /**
     * Unlike the comment
     */
    public function unlike(int $userId): bool
    {
        if (!$this->likes()->where('user_id', $userId)->exists()) {
            return false; // Not liked
        }

        $this->likes()->detach($userId);
        $this->decrement('likes_count');
        
        return true;
    }

    /**
     * Check if user has liked this comment
     */
    public function isLikedBy(int $userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Increment replies count
     */
    public function incrementRepliesCount(): void
    {
        $this->increment('replies_count');
    }

    /**
     * Decrement replies count
     */
    public function decrementRepliesCount(): void
    {
        $this->decrement('replies_count');
    }

    /**
     * Get the time since comment was created
     */
    public function getTimeSinceCreatedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the time since comment was approved
     */
    public function getTimeSinceApprovedAttribute(): ?string
    {
        return $this->approved_at ? $this->approved_at->diffForHumans() : null;
    }

    /**
     * Check if comment is pending approval
     */
    public function isPending(): bool
    {
        return !$this->is_approved;
    }

    /**
     * Check if comment is approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * Check if comment is visible
     */
    public function isVisible(): bool
    {
        return $this->is_visible;
    }

    /**
     * Check if comment is pinned
     */
    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    /**
     * Check if comment is a reply
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if comment is top-level
     */
    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get comment for API response
     */
    public function toApiResponse(int $userId = null): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'rating' => $this->rating,
            'is_approved' => $this->is_approved,
            'is_visible' => $this->is_visible,
            'is_pinned' => $this->is_pinned,
            'likes_count' => $this->likes_count,
            'replies_count' => $this->replies_count,
            'is_liked' => $userId ? $this->isLikedBy($userId) : false,
            'is_reply' => $this->isReply(),
            'parent_id' => $this->parent_id,
            'created_at' => $this->created_at->toISOString(),
            'time_since_created' => $this->time_since_created,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar ?? null
            ],
            'approved_at' => $this->approved_at?->toISOString(),
            'time_since_approved' => $this->time_since_approved,
            'replies' => $this->replies->map(function ($reply) use ($userId) {
                return $reply->toApiResponse($userId);
            })
        ];
    }
}