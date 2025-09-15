<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationInteraction extends Model
{
    protected $fillable = [
        'user_id',
        'story_id',
        'interaction_type',
        'recommendation_type',
        'session_id',
        'context',
        'interacted_at'
    ];

    protected $casts = [
        'context' => 'array',
        'interacted_at' => 'datetime'
    ];

    /**
     * Get the user that owns the interaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the story that was interacted with
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the recommendation session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(RecommendationSession::class, 'session_id');
    }

    /**
     * Scope a query to only include interactions of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope a query to only include interactions from a specific recommendation type
     */
    public function scopeFromRecommendationType($query, string $type)
    {
        return $query->where('recommendation_type', $type);
    }

    /**
     * Scope a query to only include recent interactions
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('interacted_at', '>=', now()->subDays($days));
    }

    /**
     * Get interaction value for scoring
     */
    public function getInteractionValue(): float
    {
        return match($this->interaction_type) {
            'favorite' => 1.0,
            'play' => 0.8,
            'click' => 0.3,
            'rating_high' => 0.9,
            'rating_low' => -0.5,
            'dismiss' => -0.2,
            'skip' => -0.1,
            default => 0.1
        };
    }

    /**
     * Check if interaction is positive
     */
    public function isPositive(): bool
    {
        return in_array($this->interaction_type, ['favorite', 'play', 'rating_high', 'click']);
    }

    /**
     * Check if interaction is negative
     */
    public function isNegative(): bool
    {
        return in_array($this->interaction_type, ['dismiss', 'skip', 'rating_low']);
    }

    /**
     * Get interaction category
     */
    public function getCategory(): string
    {
        if ($this->isPositive()) {
            return 'positive';
        } elseif ($this->isNegative()) {
            return 'negative';
        }
        return 'neutral';
    }
}