<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'achievement_id',
        'unlocked_at',
        'progress_data',
        'is_notified',
        'metadata'
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'progress_data' => 'array',
        'is_notified' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get the user that owns the achievement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Scope a query to only include recent achievements
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('unlocked_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include notified achievements
     */
    public function scopeNotified($query)
    {
        return $query->where('is_notified', true);
    }

    /**
     * Scope a query to only include unnotified achievements
     */
    public function scopeUnnotified($query)
    {
        return $query->where('is_notified', false);
    }

    /**
     * Mark achievement as notified
     */
    public function markAsNotified(): void
    {
        $this->update(['is_notified' => true]);
    }

    /**
     * Get the time since achievement was unlocked
     */
    public function getTimeSinceUnlockedAttribute(): string
    {
        return $this->unlocked_at->diffForHumans();
    }
}