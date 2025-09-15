<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentLike extends Model
{
    protected $fillable = [
        'user_id',
        'comment_id',
        'liked_at'
    ];

    protected $casts = [
        'liked_at' => 'datetime'
    ];

    /**
     * Get the user that liked the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comment that was liked
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(UserComment::class, 'comment_id');
    }

    /**
     * Scope a query to only include recent likes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('liked_at', '>=', now()->subDays($days));
    }
}