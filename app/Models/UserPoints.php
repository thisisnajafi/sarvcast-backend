<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPoints extends Model
{
    protected $fillable = [
        'user_id',
        'total_points',
        'available_points',
        'spent_points',
        'level',
        'experience',
        'level_progress',
        'last_activity_at'
    ];

    protected $casts = [
        'total_points' => 'integer',
        'available_points' => 'integer',
        'spent_points' => 'integer',
        'level' => 'integer',
        'experience' => 'integer',
        'level_progress' => 'array',
        'last_activity_at' => 'datetime'
    ];

    /**
     * Get the user that owns the points
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the point transactions for this user
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Scope a query to order users by total points
     */
    public function scopeOrderByPoints($query)
    {
        return $query->orderBy('total_points', 'desc');
    }

    /**
     * Scope a query to order users by level
     */
    public function scopeOrderByLevel($query)
    {
        return $query->orderBy('level', 'desc')->orderBy('experience', 'desc');
    }

    /**
     * Scope a query to only include active users
     */
    public function scopeActive($query, int $days = 7)
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    /**
     * Add points to user
     */
    public function addPoints(int $points): void
    {
        $this->increment('total_points', $points);
        $this->increment('available_points', $points);
        $this->increment('experience', $points);
        $this->last_activity_at = now();
        $this->save();
    }

    /**
     * Spend points
     */
    public function spendPoints(int $points): bool
    {
        if ($this->available_points < $points) {
            return false;
        }

        $this->decrement('available_points', $points);
        $this->increment('spent_points', $points);
        $this->save();

        return true;
    }

    /**
     * Get the next level experience requirement
     */
    public function getNextLevelXpAttribute(): int
    {
        return $this->level * 100;
    }

    /**
     * Get the current level progress percentage
     */
    public function getLevelProgressPercentageAttribute(): float
    {
        $currentLevelXp = ($this->level - 1) * 100;
        $nextLevelXp = $this->level * 100;
        $progressXp = $this->experience - $currentLevelXp;
        $requiredXp = $nextLevelXp - $currentLevelXp;

        return $requiredXp > 0 ? ($progressXp / $requiredXp) * 100 : 0;
    }

    /**
     * Get the experience needed for next level
     */
    public function getXpNeededForNextLevelAttribute(): int
    {
        $nextLevelXp = $this->level * 100;
        return max(0, $nextLevelXp - $this->experience);
    }

    /**
     * Check if user can level up
     */
    public function canLevelUp(): bool
    {
        return $this->experience >= $this->next_level_xp;
    }

    /**
     * Level up user
     */
    public function levelUp(): int
    {
        $newLevel = floor($this->experience / 100) + 1;
        $this->level = $newLevel;
        $this->save();

        return $newLevel;
    }
}