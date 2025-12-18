<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoryRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_id',
        'rating',
        'review',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Scope to get ratings for a specific story
     */
    public function scopeForStory($query, $storyId)
    {
        return $query->where('story_id', $storyId);
    }

    /**
     * Scope to get ratings by a specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get ratings with reviews
     */
    public function scopeWithReviews($query)
    {
        return $query->whereNotNull('review');
    }

    /**
     * Get average rating for a story
     */
    public static function getAverageRating($storyId)
    {
        return static::where('story_id', $storyId)->avg('rating') ?? 0.0;
    }

    /**
     * Get rating count for a story
     */
    public static function getRatingCount($storyId)
    {
        return static::where('story_id', $storyId)->count();
    }

    /**
     * Get user's rating for a story
     */
    public static function getUserRating($userId, $storyId)
    {
        return static::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->first();
    }

    /**
     * Check if user has rated a story
     */
    public static function hasUserRated($userId, $storyId)
    {
        return static::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->exists();
    }
}