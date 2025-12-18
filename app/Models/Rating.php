<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_id',
        'episode_id',
        'rating',
        'review'
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the rating
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the story that was rated
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the episode that was rated
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Scope to get ratings for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get ratings for a specific story
     */
    public function scopeForStory($query, $storyId)
    {
        return $query->where('story_id', $storyId);
    }

    /**
     * Scope to get ratings for a specific episode
     */
    public function scopeForEpisode($query, $episodeId)
    {
        return $query->where('episode_id', $episodeId);
    }

    /**
     * Scope to get ratings with reviews
     */
    public function scopeWithReviews($query)
    {
        return $query->whereNotNull('review')->where('review', '!=', '');
    }

    /**
     * Scope to get ratings without reviews
     */
    public function scopeWithoutReviews($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('review')->orWhere('review', '');
        });
    }

    /**
     * Scope to get ratings by rating value
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to get recent ratings
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
