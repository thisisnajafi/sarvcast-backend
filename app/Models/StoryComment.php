<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryComment extends Model
{
    protected $fillable = [
        'story_id',
        'user_id',
        'comment',
        'is_approved',
        'is_visible',
        'approved_at',
        'approved_by'
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_visible' => 'boolean',
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
     * Get comment for API response
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'is_approved' => $this->is_approved,
            'is_visible' => $this->is_visible,
            'created_at' => $this->created_at->toISOString(),
            'time_since_created' => $this->time_since_created,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar
            ],
            'approved_at' => $this->approved_at?->toISOString(),
            'time_since_approved' => $this->time_since_approved
        ];
    }
}