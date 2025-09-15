<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPlaylist extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
        'is_collaborative',
        'cover_image',
        'metadata',
        'sort_order'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_collaborative' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get the user that owns the playlist
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the playlist items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class, 'playlist_id');
    }

    /**
     * Scope a query to only include public playlists
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include collaborative playlists
     */
    public function scopeCollaborative($query)
    {
        return $query->where('is_collaborative', true);
    }

    /**
     * Scope a query to only include recent playlists
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get the total duration of the playlist
     */
    public function getTotalDurationAttribute(): int
    {
        return $this->items()
            ->join('episodes', function($join) {
                $join->on('playlist_items.item_id', '=', 'episodes.id')
                     ->where('playlist_items.item_type', '=', 'episode');
            })
            ->sum('episodes.duration');
    }

    /**
     * Get the total number of items in the playlist
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }
}