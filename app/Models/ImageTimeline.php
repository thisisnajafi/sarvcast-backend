<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageTimeline extends Model
{
    protected $fillable = [
        'episode_id',
        'voice_actor_id',
        'start_time',
        'end_time',
        'image_url',
        'image_order',
        'scene_description',
        'transition_type',
        'is_key_frame'
    ];

    protected $casts = [
        'start_time' => 'integer',
        'end_time' => 'integer',
        'image_order' => 'integer',
        'is_key_frame' => 'boolean'
    ];

    /**
     * Get the episode that owns the image timeline
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Get the voice actor associated with this image timeline
     */
    public function voiceActor(): BelongsTo
    {
        return $this->belongsTo(EpisodeVoiceActor::class);
    }

    /**
     * Scope a query to only include timelines for a specific episode
     */
    public function scopeForEpisode($query, int $episodeId)
    {
        return $query->where('episode_id', $episodeId);
    }

    /**
     * Scope a query to order timelines by image order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('image_order');
    }

    /**
     * Scope a query to get timeline for a specific time
     */
    public function scopeForTime($query, int $timeInSeconds)
    {
        return $query->where('start_time', '<=', $timeInSeconds)
                    ->where('end_time', '>=', $timeInSeconds);
    }

    /**
     * Scope for key frames
     */
    public function scopeKeyFrames($query)
    {
        return $query->where('is_key_frame', true);
    }

    /**
     * Scope for specific transition type
     */
    public function scopeWithTransitionType($query, string $transitionType)
    {
        return $query->where('transition_type', $transitionType);
    }

    /**
     * Scope for timelines with voice actors
     */
    public function scopeWithVoiceActors($query)
    {
        return $query->whereNotNull('voice_actor_id');
    }

    /**
     * Validate timeline data
     */
    public static function validateTimeline(array $timeline, int $episodeDuration): array
    {
        $errors = [];
        
        // Check for gaps and overlaps
        $sortedTimeline = collect($timeline)->sortBy('start_time')->values();
        
        for ($i = 0; $i < $sortedTimeline->count(); $i++) {
            $current = $sortedTimeline[$i];
            
            // Validate individual entry
            if ($current['start_time'] < 0) {
                $errors[] = "Start time cannot be negative";
            }
            
            if ($current['end_time'] > $episodeDuration) {
                $errors[] = "End time cannot exceed episode duration";
            }
            
            if ($current['start_time'] >= $current['end_time']) {
                $errors[] = "Start time must be less than end time";
            }
            
            // Check for overlaps with next entry
            if ($i < $sortedTimeline->count() - 1) {
                $next = $sortedTimeline[$i + 1];
                
                if ($current['end_time'] > $next['start_time']) {
                    $errors[] = "Timeline entries cannot overlap";
                }
            }
        }
        
        // Check if timeline covers entire duration
        if ($sortedTimeline->isNotEmpty()) {
            $first = $sortedTimeline->first();
            $last = $sortedTimeline->last();
            
            if ($first['start_time'] > 0) {
                $errors[] = "Timeline must start from 0 seconds";
            }
            
            if ($last['end_time'] < $episodeDuration) {
                $errors[] = "Timeline must cover entire episode duration";
            }
        }
        
        return $errors;
    }

    /**
     * Get timeline data for API response
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'image_url' => $this->image_url,
            'image_order' => $this->image_order,
            'scene_description' => $this->scene_description,
            'transition_type' => $this->transition_type,
            'is_key_frame' => $this->is_key_frame,
            'voice_actor' => $this->voiceActor ? $this->voiceActor->toApiResponse() : null,
            'start_time_formatted' => $this->formatTime($this->start_time),
            'end_time_formatted' => $this->formatTime($this->end_time),
            'duration' => $this->end_time - $this->start_time
        ];
    }

    /**
     * Format time in seconds to MM:SS format
     */
    private function formatTime(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
}