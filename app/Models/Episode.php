<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasImageUrl;

class Episode extends Model
{
    use HasFactory, HasImageUrl;

    protected $fillable = [
        'story_id',
        'narrator_id',
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

    /**
     * Get validation rules for episode creation
     */
    public static function getValidationRules($episodeId = null)
    {
        return [
            'story_id' => ['required', 'integer', 'exists:stories,id'],
            'narrator_id' => ['nullable', 'integer', 'exists:people,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'audio_url' => ['required', 'string', 'max:500'],
            'local_audio_path' => ['nullable', 'string', 'max:500'],
            'duration' => ['required', 'integer', 'min:1', 'max:1440'], // 1 minute to 24 hours
            'episode_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('episodes')->where(function ($query) {
                    return $query->where('story_id', request('story_id'));
                })->ignore($episodeId)
            ],
            'is_premium' => ['boolean'],
            'image_urls' => ['nullable', 'array'],
            'image_urls.*' => ['string', 'max:500'],
            'play_count' => ['integer', 'min:0'],
            'rating' => ['numeric', 'min:0', 'max:5'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['required', 'in:draft,pending,approved,rejected,published'],
            'published_at' => ['nullable', 'date'],
            'use_image_timeline' => ['boolean'],
            'has_multiple_voice_actors' => ['boolean'],
            'voice_actor_count' => ['integer', 'min:0', 'max:20'],
        ];
    }

    /**
     * Get validation messages for episode validation
     */
    public static function getValidationMessages()
    {
        return [
            'story_id.required' => 'انتخاب داستان الزامی است',
            'story_id.exists' => 'داستان انتخاب شده معتبر نیست',
            'narrator_id.exists' => 'راوی انتخاب شده معتبر نیست',
            'title.required' => 'عنوان اپیزود الزامی است',
            'title.max' => 'عنوان اپیزود نمی‌تواند بیشتر از 200 کاراکتر باشد',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 2000 کاراکتر باشد',
            'audio_url.required' => 'فایل صوتی الزامی است',
            'audio_url.max' => 'آدرس فایل صوتی نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'duration.required' => 'مدت زمان اپیزود الزامی است',
            'duration.min' => 'مدت زمان اپیزود باید حداقل 1 دقیقه باشد',
            'duration.max' => 'مدت زمان اپیزود نمی‌تواند بیشتر از 24 ساعت باشد',
            'episode_number.required' => 'شماره اپیزود الزامی است',
            'episode_number.min' => 'شماره اپیزود باید حداقل 1 باشد',
            'episode_number.unique' => 'این شماره اپیزود قبلاً برای این داستان استفاده شده است',
            'rating.min' => 'امتیاز نمی‌تواند کمتر از 0 باشد',
            'rating.max' => 'امتیاز نمی‌تواند بیشتر از 5 باشد',
            'status.required' => 'وضعیت اپیزود الزامی است',
            'status.in' => 'وضعیت انتخاب شده معتبر نیست',
            'published_at.date' => 'تاریخ انتشار باید معتبر باشد',
            'voice_actor_count.max' => 'تعداد صداپیشه‌ها نمی‌تواند بیشتر از 20 باشد',
        ];
    }

    /**
     * Validate episode data
     */
    public static function validateEpisodeData($data, $episodeId = null)
    {
        $rules = self::getValidationRules($episodeId);
        $messages = self::getValidationMessages();

        return validator($data, $rules, $messages);
    }

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

    public function narrator()
    {
        return $this->belongsTo(Person::class, 'narrator_id');
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

    public function imageTimeline()
    {
        return $this->hasOne(ImageTimeline::class);
    }

    // Keep the old method for backward compatibility but mark as deprecated
    /**
     * @deprecated Use imageTimeline() instead. Each episode should have only one timeline.
     */
    public function imageTimelines()
    {
        return $this->hasMany(ImageTimeline::class)->orderBy('image_order');
    }

    /**
     * Get the full audio URL from a relative path
     */
    public function getAudioUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Audio files are stored in public/audio/episodes/
        // They should be accessible directly via /audio/episodes/filename.mp3

        // Extract filename from path (value is like "audio/episodes/filename.mp3")
        $filename = basename($value);
        if (str_contains($value, 'audio/episodes/')) {
            // Extract just the filename after "audio/episodes/"
            $parts = explode('audio/episodes/', $value);
            $filename = end($parts);
        }

        // Generate URL pointing to public/audio/episodes/
        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . '/audio/episodes/' . $filename;
    }

    /**
     * Get the scenes for the episode
     */
    public function scenes()
    {
        return $this->hasMany(StoryScene::class);
    }

    /**
     * Get the image generation jobs for the episode
     */
    public function imageGenerationJobs()
    {
        return $this->hasMany(ImageGenerationJob::class);
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

    /**
     * Get the image URLs for the episode
     */
    public function getImageUrlsAttribute()
    {
        $imageUrls = $this->attributes['image_urls'] ?? [];
        if (is_array($imageUrls)) {
            return array_map(function($url) {
                return $this->getImageUrlFromPath($url);
            }, $imageUrls);
        }
        return [];
    }
}
