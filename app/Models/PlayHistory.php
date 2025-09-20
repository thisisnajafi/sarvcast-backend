<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PlayHistory extends Model
{
    use HasFactory;

    /**
     * Disable automatic timestamps since we use played_at
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'episode_id',
        'story_id',
        'played_at',
        'duration_played',
        'total_duration',
        'completed',
        'device_info'
    ];

    protected $casts = [
        'played_at' => 'datetime',
        'completed' => 'boolean',
        'device_info' => 'array',
        'duration_played' => 'integer',
        'total_duration' => 'integer'
    ];

    /**
     * Get the user that owns the play history
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the episode that was played
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Get the story that was played
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Scope to get play history for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get play history for a specific episode
     */
    public function scopeForEpisode($query, $episodeId)
    {
        return $query->where('episode_id', $episodeId);
    }

    /**
     * Scope to get play history for a specific story
     */
    public function scopeForStory($query, $storyId)
    {
        return $query->where('story_id', $storyId);
    }

    /**
     * Scope to get recent play history
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('played_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get completed episodes
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope to get incomplete episodes
     */
    public function scopeIncomplete($query)
    {
        return $query->where('completed', false);
    }

    /**
     * Record a play session
     */
    public static function recordPlay($userId, $episodeId, $durationPlayed, $totalDuration, $deviceInfo = null): self
    {
        $episode = Episode::find($episodeId);
        if (!$episode) {
            throw new \InvalidArgumentException('Episode not found');
        }

        $completed = $durationPlayed >= ($totalDuration * 0.9); // 90% completion threshold

        return self::create([
            'user_id' => $userId,
            'episode_id' => $episodeId,
            'story_id' => $episode->story_id,
            'played_at' => now(),
            'duration_played' => $durationPlayed,
            'total_duration' => $totalDuration,
            'completed' => $completed,
            'device_info' => $deviceInfo
        ]);
    }

    /**
     * Update play progress
     */
    public function updateProgress($durationPlayed, $totalDuration = null): bool
    {
        if ($totalDuration) {
            $this->total_duration = $totalDuration;
        }

        $this->duration_played = $durationPlayed;
        $this->completed = $durationPlayed >= ($this->total_duration * 0.9);
        $this->played_at = now();

        return $this->save();
    }

    /**
     * Get user's play history with pagination
     */
    public static function getUserPlayHistory($userId, $perPage = 20)
    {
        return self::with(['episode', 'story.category'])
                   ->forUser($userId)
                   ->orderBy('played_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Get user's recent play history
     */
    public static function getUserRecentHistory($userId, $limit = 10)
    {
        return self::with(['episode', 'story.category'])
                   ->forUser($userId)
                   ->recent(7)
                   ->orderBy('played_at', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get user's completed episodes
     */
    public static function getUserCompletedEpisodes($userId, $perPage = 20)
    {
        return self::with(['episode', 'story.category'])
                   ->forUser($userId)
                   ->completed()
                   ->orderBy('played_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Get user's incomplete episodes (in progress)
     */
    public static function getUserIncompleteEpisodes($userId, $perPage = 20)
    {
        return self::with(['episode', 'story.category'])
                   ->forUser($userId)
                   ->incomplete()
                   ->orderBy('played_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Get episode play statistics
     */
    public static function getEpisodeStats($episodeId): array
    {
        $totalPlays = self::forEpisode($episodeId)->count();
        $completedPlays = self::forEpisode($episodeId)->completed()->count();
        $uniqueUsers = self::forEpisode($episodeId)->distinct('user_id')->count();
        $averageCompletion = self::forEpisode($episodeId)->avg('duration_played');

        return [
            'total_plays' => $totalPlays,
            'completed_plays' => $completedPlays,
            'unique_users' => $uniqueUsers,
            'completion_rate' => $totalPlays > 0 ? round(($completedPlays / $totalPlays) * 100, 2) : 0,
            'average_completion_time' => round($averageCompletion ?? 0, 2)
        ];
    }

    /**
     * Get story play statistics
     */
    public static function getStoryStats($storyId): array
    {
        $totalPlays = self::forStory($storyId)->count();
        $completedPlays = self::forStory($storyId)->completed()->count();
        $uniqueUsers = self::forStory($storyId)->distinct('user_id')->count();
        $averageCompletion = self::forStory($storyId)->avg('duration_played');

        return [
            'total_plays' => $totalPlays,
            'completed_plays' => $completedPlays,
            'unique_users' => $uniqueUsers,
            'completion_rate' => $totalPlays > 0 ? round(($completedPlays / $totalPlays) * 100, 2) : 0,
            'average_completion_time' => round($averageCompletion ?? 0, 2)
        ];
    }

    /**
     * Get user's play statistics
     */
    public static function getUserStats($userId): array
    {
        $totalPlays = self::forUser($userId)->count();
        $completedPlays = self::forUser($userId)->completed()->count();
        $uniqueStories = self::forUser($userId)->distinct('story_id')->count();
        $uniqueEpisodes = self::forUser($userId)->distinct('episode_id')->count();
        $totalPlayTime = self::forUser($userId)->sum('duration_played');
        $recentPlays = self::forUser($userId)->recent(7)->count();

        return [
            'total_plays' => $totalPlays,
            'completed_plays' => $completedPlays,
            'unique_stories' => $uniqueStories,
            'unique_episodes' => $uniqueEpisodes,
            'total_play_time' => $totalPlayTime,
            'total_play_time_hours' => round($totalPlayTime / 3600, 2),
            'recent_plays' => $recentPlays,
            'completion_rate' => $totalPlays > 0 ? round(($completedPlays / $totalPlays) * 100, 2) : 0
        ];
    }

    /**
     * Get most played episodes
     */
    public static function getMostPlayedEpisodes($limit = 10)
    {
        return self::with(['episode', 'story.category'])
                   ->selectRaw('episode_id, COUNT(*) as play_count')
                   ->groupBy('episode_id')
                   ->orderBy('play_count', 'desc')
                   ->limit($limit)
                   ->get()
                   ->map(function ($item) {
                       return [
                           'episode' => $item->episode,
                           'play_count' => $item->play_count
                       ];
                   });
    }

    /**
     * Get most played stories
     */
    public static function getMostPlayedStories($limit = 10)
    {
        return self::with(['story.category'])
                   ->selectRaw('story_id, COUNT(*) as play_count')
                   ->groupBy('story_id')
                   ->orderBy('play_count', 'desc')
                   ->limit($limit)
                   ->get()
                   ->map(function ($item) {
                       return [
                           'story' => $item->story,
                           'play_count' => $item->play_count
                       ];
                   });
    }

    /**
     * Get play history analytics
     */
    public static function getPlayAnalytics($days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $dailyPlays = self::where('played_at', '>=', $startDate)
                         ->selectRaw('DATE(played_at) as date, COUNT(*) as plays')
                         ->groupBy('date')
                         ->orderBy('date')
                         ->get();

        $totalPlays = self::where('played_at', '>=', $startDate)->count();
        $completedPlays = self::where('played_at', '>=', $startDate)->completed()->count();
        $uniqueUsers = self::where('played_at', '>=', $startDate)->distinct('user_id')->count();
        $totalPlayTime = self::where('played_at', '>=', $startDate)->sum('duration_played');

        return [
            'period_days' => $days,
            'total_plays' => $totalPlays,
            'completed_plays' => $completedPlays,
            'unique_users' => $uniqueUsers,
            'total_play_time' => $totalPlayTime,
            'total_play_time_hours' => round($totalPlayTime / 3600, 2),
            'completion_rate' => $totalPlays > 0 ? round(($completedPlays / $totalPlays) * 100, 2) : 0,
            'daily_plays' => $dailyPlays
        ];
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_duration <= 0) {
            return 0;
        }
        
        return round(($this->duration_played / $this->total_duration) * 100, 2);
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTimeAttribute(): int
    {
        return max(0, $this->total_duration - $this->duration_played);
    }

    /**
     * Check if episode is nearly completed
     */
    public function isNearlyCompleted(): bool
    {
        return $this->completion_percentage >= 90;
    }

    /**
     * Check if episode is just started
     */
    public function isJustStarted(): bool
    {
        return $this->completion_percentage <= 10;
    }
}
