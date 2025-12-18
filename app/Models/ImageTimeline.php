<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;
use App\Traits\HasImageUrl;

class ImageTimeline extends Model
{
    use HasImageUrl;
    protected $fillable = [
        'story_id',
        'episode_id',
        'voice_actor_id',
        'character_id',
        'scene_id',
        'start_time',
        'end_time',
        'image_url',
        'image_order',
        'scene_description',
        'transition_type',
        'is_key_frame'
    ];

    /**
     * Get validation rules for image timeline creation
     */
    public static function getValidationRules($timelineId = null)
    {
        return [
            'story_id' => ['nullable', 'integer', 'exists:stories,id'],
            'episode_id' => ['nullable', 'integer', 'exists:episodes,id'],
            'voice_actor_id' => ['nullable', 'integer', 'exists:people,id'],
            'character_id' => ['nullable', 'integer', 'exists:people,id'],
            'scene_id' => ['nullable', 'integer'],
            'start_time' => ['required', 'integer', 'min:0'],
            'end_time' => ['required', 'integer', 'min:0'],
            'image_url' => ['required', 'string', 'max:500'],
            'image_order' => ['required', 'integer', 'min:1'],
            'scene_description' => ['nullable', 'string', 'max:1000'],
            'transition_type' => ['required', 'in:fade,slide,cut'],
            'is_key_frame' => ['boolean'],
        ];
    }

    /**
     * Get validation messages for image timeline validation
     */
    public static function getValidationMessages()
    {
        return [
            'story_id.exists' => 'داستان انتخاب شده معتبر نیست',
            'episode_id.exists' => 'اپیزود انتخاب شده معتبر نیست',
            'voice_actor_id.exists' => 'صداپیشه انتخاب شده معتبر نیست',
            'character_id.exists' => 'شخصیت انتخاب شده معتبر نیست',
            'start_time.required' => 'زمان شروع الزامی است',
            'start_time.min' => 'زمان شروع نمی‌تواند منفی باشد',
            'end_time.required' => 'زمان پایان الزامی است',
            'end_time.min' => 'زمان پایان نمی‌تواند منفی باشد',
            // Removed: end_time.gt validation
            'image_url.required' => 'آدرس تصویر الزامی است',
            'image_url.max' => 'آدرس تصویر نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'image_order.required' => 'ترتیب تصویر الزامی است',
            'image_order.min' => 'ترتیب تصویر باید حداقل 1 باشد',
            'scene_description.max' => 'توضیح صحنه نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'transition_type.required' => 'نوع انتقال الزامی است',
            'transition_type.in' => 'نوع انتقال نامعتبر است',
        ];
    }

    /**
     * Validate image timeline data
     */
    public static function validateTimelineData($data, $timelineId = null)
    {
        $rules = self::getValidationRules($timelineId);
        $messages = self::getValidationMessages();
        
        return validator($data, $rules, $messages);
    }

    /**
     * Validate timeline data for bulk operations
     */
    public static function validateBulkTimelineData($data)
    {
        $rules = [];
        $messages = self::getValidationMessages();
        
        foreach ($data as $index => $timeline) {
            $rules["timeline.{$index}.start_time"] = ['required', 'integer', 'min:0'];
            $rules["timeline.{$index}.end_time"] = ['required', 'integer', 'min:0'];
            $rules["timeline.{$index}.image_url"] = ['required', 'string', 'max:500'];
            $rules["timeline.{$index}.image_order"] = ['required', 'integer', 'min:1'];
            $rules["timeline.{$index}.transition_type"] = ['required', 'in:fade,slide,cut'];
            $rules["timeline.{$index}.scene_description"] = ['nullable', 'string', 'max:1000'];
            $rules["timeline.{$index}.is_key_frame"] = ['boolean'];
        }
        
        return validator($data, $rules, $messages);
    }

    protected $casts = [
        'start_time' => 'integer',
        'end_time' => 'integer',
        'image_order' => 'integer',
        'is_key_frame' => 'boolean'
    ];

    /**
     * Get the story that owns the image timeline
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the episode that owns the image timeline (for backward compatibility)
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
     * Get the character associated with this image timeline
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(StoryCharacter::class);
    }

    /**
     * Get the scene associated with this image timeline
     */
    public function scene(): BelongsTo
    {
        return $this->belongsTo(StoryScene::class);
    }

    /**
     * Scope a query to only include timelines for a specific story
     */
    public function scopeForStory($query, int $storyId)
    {
        return $query->where('story_id', $storyId);
    }

    /**
     * Scope a query to only include timelines for a specific episode (for backward compatibility)
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
            'image_url' => $this->getImageUrlFromPath($this->image_url),
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