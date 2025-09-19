<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'title',
        'description',
        'audio_url',
        'local_audio_path',
        'duration',
        'episode_number',
        'is_premium',
        'image_urls',
        'play_count',
        'rating',
        'tags',
        'status',
        'published_at',
        'use_image_timeline',
        'has_multiple_voice_actors',
        'voice_actor_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'image_urls' => 'array',
            'tags' => 'array',
            'is_premium' => 'boolean',
            'use_image_timeline' => 'boolean',
            'has_multiple_voice_actors' => 'boolean',
            'voice_actor_count' => 'integer',
        ];
    }

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function people()
    {
        return $this->belongsToMany(Person::class, 'episode_people')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function playHistories()
    {
        return $this->hasMany(PlayHistory::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function imageTimelines()
    {
        return $this->hasMany(ImageTimeline::class)->orderBy('image_order');
    }

    /**
     * Get the voice actors for the episode
     */
    public function voiceActors()
    {
        return $this->hasMany(EpisodeVoiceActor::class)->orderBy('start_time');
    }

    /**
     * Get the primary voice actor for the episode
     */
    public function primaryVoiceActor()
    {
        return $this->hasOne(EpisodeVoiceActor::class)->where('is_primary', true);
    }

    /**
     * Get voice actors in a specific time range
     */
    public function voiceActorsInTimeRange(int $startTime, int $endTime)
    {
        return $this->voiceActors()->inTimeRange($startTime, $endTime);
    }

    /**
     * Get voice actor for a specific time
     */
    public function getVoiceActorForTime(int $timeInSeconds)
    {
        return $this->voiceActors()
            ->where('start_time', '<=', $timeInSeconds)
            ->where('end_time', '>=', $timeInSeconds)
            ->first();
    }

    /**
     * Get all voice actors at a specific time
     */
    public function getVoiceActorsAtTime(int $timeInSeconds)
    {
        return $this->voiceActors()
            ->where('start_time', '<=', $timeInSeconds)
            ->where('end_time', '>=', $timeInSeconds)
            ->get();
    }

    /**
     * Check if episode has multiple voice actors
     */
    public function hasMultipleVoiceActors(): bool
    {
        return $this->has_multiple_voice_actors ?? false;
    }

    /**
     * Get voice actor count
     */
    public function getVoiceActorCount(): int
    {
        return $this->voice_actor_count ?? 0;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }

    public function isPremium()
    {
        return $this->is_premium;
    }
}
