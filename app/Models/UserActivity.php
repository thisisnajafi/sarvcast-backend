<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_target_type',
        'activity_target_id',
        'activity_description',
        'activity_data',
        'is_public',
        'is_anonymous',
        'activity_at'
    ];

    protected $casts = [
        'activity_data' => 'array',
        'is_public' => 'boolean',
        'is_anonymous' => 'boolean',
        'activity_at' => 'datetime'
    ];

    /**
     * Get the user that performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activity target model
     */
    public function activityTarget(): MorphTo
    {
        return $this->morphTo('activity_target');
    }

    /**
     * Scope a query to only include public activities
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include activities of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope a query to only include recent activities
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('activity_at', '>=', now()->subDays($days));
    }
}