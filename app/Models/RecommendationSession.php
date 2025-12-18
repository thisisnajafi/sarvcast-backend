<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecommendationSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_type',
        'recommendations',
        'metadata'
    ];

    protected $casts = [
        'recommendations' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Get the user that owns the session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the interactions for this session
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(RecommendationInteraction::class, 'session_id');
    }

    /**
     * Scope a query to only include sessions of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('session_type', $type);
    }

    /**
     * Scope a query to only include recent sessions
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get session performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $totalRecommendations = count($this->recommendations);
        $interactions = $this->interactions()->count();
        $clicks = $this->interactions()->where('interaction_type', 'click')->count();
        $plays = $this->interactions()->where('interaction_type', 'play')->count();
        $favorites = $this->interactions()->where('interaction_type', 'favorite')->count();

        return [
            'total_recommendations' => $totalRecommendations,
            'total_interactions' => $interactions,
            'clicks' => $clicks,
            'plays' => $plays,
            'favorites' => $favorites,
            'click_through_rate' => $totalRecommendations > 0 ? round(($clicks / $totalRecommendations) * 100, 2) : 0,
            'conversion_rate' => $totalRecommendations > 0 ? round(($plays / $totalRecommendations) * 100, 2) : 0,
            'favorite_rate' => $totalRecommendations > 0 ? round(($favorites / $totalRecommendations) * 100, 2) : 0
        ];
    }

    /**
     * Add a recommendation to the session
     */
    public function addRecommendation(int $storyId, string $type, float $score): void
    {
        $recommendations = $this->recommendations ?? [];
        $recommendations[] = [
            'story_id' => $storyId,
            'type' => $type,
            'score' => $score,
            'added_at' => now()->toISOString()
        ];
        
        $this->recommendations = $recommendations;
        $this->save();
    }

    /**
     * Get recommendations by type
     */
    public function getRecommendationsByType(string $type): array
    {
        return array_filter($this->recommendations ?? [], function($rec) use ($type) {
            return $rec['type'] === $type;
        });
    }
}