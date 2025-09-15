<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlaylistItem extends Model
{
    protected $fillable = [
        'playlist_id',
        'item_type',
        'item_id',
        'sort_order',
        'added_at'
    ];

    protected $casts = [
        'added_at' => 'datetime'
    ];

    /**
     * Get the playlist that owns the item
     */
    public function playlist(): BelongsTo
    {
        return $this->belongsTo(UserPlaylist::class, 'playlist_id');
    }

    /**
     * Get the item model
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include items of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope a query to only include recent items
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('added_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to order items by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}