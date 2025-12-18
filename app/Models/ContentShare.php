<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentShare extends Model
{
    protected $fillable = [
        'user_id',
        'shareable_type',
        'shareable_id',
        'share_type',
        'platform',
        'message',
        'share_url',
        'metadata',
        'shared_at'
    ];

    protected $casts = [
        'shared_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get the user that made the share
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shareable model
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include shares of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('share_type', $type);
    }

    /**
     * Scope a query to only include shares from a specific platform
     */
    public function scopeFromPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope a query to only include recent shares
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('shared_at', '>=', now()->subDays($days));
    }
}