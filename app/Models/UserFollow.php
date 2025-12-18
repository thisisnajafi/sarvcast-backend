<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFollow extends Model
{
    protected $fillable = [
        'follower_id',
        'following_id',
        'followed_at',
        'is_mutual',
        'metadata'
    ];

    protected $casts = [
        'followed_at' => 'datetime',
        'is_mutual' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get the follower user
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Get the following user
     */
    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'following_id');
    }

    /**
     * Scope a query to only include mutual follows
     */
    public function scopeMutual($query)
    {
        return $query->where('is_mutual', true);
    }

    /**
     * Scope a query to only include recent follows
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('followed_at', '>=', now()->subDays($days));
    }
}