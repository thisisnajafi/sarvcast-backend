<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'badge_color',
        'category',
        'type',
        'criteria',
        'points',
        'is_active',
        'is_hidden',
        'sort_order',
        'metadata'
    ];

    protected $casts = [
        'criteria' => 'array',
        'points' => 'integer',
        'is_active' => 'boolean',
        'is_hidden' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array'
    ];

    /**
     * Get the user achievements for this achievement
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Scope a query to only include active achievements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include visible achievements
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope a query to only include achievements of a specific category
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include achievements of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order achievements by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('points', 'desc');
    }

    /**
     * Get the display name for the achievement category
     */
    public function getCategoryDisplayNameAttribute(): string
    {
        return match($this->category) {
            'listening' => 'گوش دادن',
            'social' => 'اجتماعی',
            'content' => 'محتوا',
            'streak' => 'استریک',
            'special' => 'ویژه',
            default => 'عمومی'
        };
    }

    /**
     * Get the display name for the achievement type
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return match($this->type) {
            'single' => 'تک',
            'cumulative' => 'تجمعی',
            'streak' => 'استریک',
            'special' => 'ویژه',
            default => 'عمومی'
        };
    }

    /**
     * Check if achievement is unlocked by user
     */
    public function isUnlockedBy(int $userId): bool
    {
        return $this->userAchievements()
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get unlock count for this achievement
     */
    public function getUnlockCountAttribute(): int
    {
        return $this->userAchievements()->count();
    }
}