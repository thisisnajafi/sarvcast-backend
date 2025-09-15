<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRecommendationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'preference_type',
        'preference_value',
        'weight',
        'interaction_count',
        'last_updated'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'last_updated' => 'datetime'
    ];

    /**
     * Get the user that owns the preference
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include preferences of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('preference_type', $type);
    }

    /**
     * Scope a query to only include preferences with high weight
     */
    public function scopeHighWeight($query, float $threshold = 0.7)
    {
        return $query->where('weight', '>=', $threshold);
    }

    /**
     * Update preference weight based on interaction
     */
    public function updateWeight(string $interactionType): void
    {
        $weightChange = match($interactionType) {
            'favorite' => 0.1,
            'play' => 0.05,
            'rating_high' => 0.15,
            'rating_low' => -0.1,
            'dismiss' => -0.05,
            default => 0.01
        };

        $this->weight = max(0, min(1, $this->weight + $weightChange));
        $this->interaction_count++;
        $this->last_updated = now();
        $this->save();
    }

    /**
     * Get preference strength based on weight and interaction count
     */
    public function getStrengthAttribute(): float
    {
        return $this->weight * log($this->interaction_count + 1);
    }
}