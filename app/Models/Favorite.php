<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_id',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Disable updated_at timestamp since we only track creation time
    const UPDATED_AT = null;

    /**
     * Get the user that owns the favorite
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the story that is favorited
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Scope to get favorites for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get favorites for a specific story
     */
    public function scopeForStory($query, $storyId)
    {
        return $query->where('story_id', $storyId);
    }

    /**
     * Scope to get recent favorites
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Only favorites whose story is published (visible in the app).
     */
    public function scopeForPublishedStory($query)
    {
        return $query->whereHas('story', function ($storyQuery) {
            $storyQuery->published();
        });
    }

    /**
     * Check if a user has favorited a story (includes unpublished/archived rows).
     */
    public static function isFavorited($userId, $storyId): bool
    {
        return self::where('user_id', $userId)
                   ->where('story_id', $storyId)
                   ->exists();
    }

    /**
     * Check if a favorited story is still visible (published) in the app.
     */
    public static function isVisibleFavorite($userId, $storyId): bool
    {
        return self::where('user_id', $userId)
                   ->where('story_id', $storyId)
                   ->forPublishedStory()
                   ->exists();
    }

    /**
     * Add a story to favorites
     */
    public static function addToFavorites($userId, $storyId): bool
    {
        if (self::isFavorited($userId, $storyId)) {
            return false; // Already favorited
        }

        return self::create([
            'user_id' => $userId,
            'story_id' => $storyId
        ]) !== null;
    }

    /**
     * Remove a story from favorites
     */
    public static function removeFromFavorites($userId, $storyId): bool
    {
        return self::where('user_id', $userId)
                   ->where('story_id', $storyId)
                   ->delete() > 0;
    }

    /**
     * Toggle favorite status
     */
    public static function toggleFavorite($userId, $storyId): array
    {
        $isFavorited = self::isFavorited($userId, $storyId);
        
        if ($isFavorited) {
            $removed = self::removeFromFavorites($userId, $storyId);
            return [
                'action' => 'removed',
                'is_favorited' => false,
                'success' => $removed
            ];
        } else {
            $added = self::addToFavorites($userId, $storyId);
            return [
                'action' => 'added',
                'is_favorited' => true,
                'success' => $added
            ];
        }
    }

    /**
     * Get user's favorite stories with pagination
     */
    public static function getUserFavorites($userId, $perPage = 20)
    {
        return self::with([
                   'story' => function ($query) {
                       $query->published()->with('category');
                   },
               ])
                   ->forUser($userId)
                   ->forPublishedStory()
                   ->orderBy('created_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Get story's favorite count
     */
    public static function getStoryFavoriteCount($storyId): int
    {
        return self::forStory($storyId)->count();
    }

    /**
     * Get most favorited stories
     */
    public static function getMostFavorited($limit = 10)
    {
        return self::with(['story' => fn ($query) => $query->published()->with('category')])
                   ->forPublishedStory()
                   ->selectRaw('story_id, COUNT(*) as favorite_count')
                   ->groupBy('story_id')
                   ->orderBy('favorite_count', 'desc')
                   ->limit($limit)
                   ->get()
                   ->map(function ($item) {
                       return [
                           'story' => $item->story,
                           'favorite_count' => $item->favorite_count
                       ];
                   });
    }

    /**
     * Get favorite statistics
     */
    public static function getFavoriteStats($userId = null)
    {
        $query = self::query();
        
        if ($userId) {
            $query->forUser($userId)->forPublishedStory();
        }

        return [
            'total_favorites' => $query->count(),
            'recent_favorites' => (clone $query)->recent(7)->count(),
            'monthly_favorites' => (clone $query)->recent(30)->count(),
            'most_favorited_story' => self::query()
                                           ->when($userId, fn ($q) => $q->forUser($userId))
                                           ->forPublishedStory()
                                           ->with(['story' => fn ($q) => $q->published()])
                                           ->selectRaw('story_id, COUNT(*) as count')
                                           ->groupBy('story_id')
                                           ->orderBy('count', 'desc')
                                           ->first()
        ];
    }
}
