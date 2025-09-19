<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EpisodeVoiceActor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'episode_id',
        'person_id',
        'role',
        'character_name',
        'start_time',
        'end_time',
        'voice_description',
        'is_primary'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'integer',
            'end_time' => 'integer',
            'is_primary' => 'boolean'
        ];
    }

    /**
     * Get the episode that owns the voice actor
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Get the person (voice actor)
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the image timelines associated with this voice actor
     */
    public function imageTimelines()
    {
        return $this->hasMany(ImageTimeline::class);
    }

    /**
     * Scope for primary voice actors
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for voice actors in a time range
     */
    public function scopeInTimeRange($query, int $startTime, int $endTime)
    {
        return $query->where(function($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
              ->orWhereBetween('end_time', [$startTime, $endTime])
              ->orWhere(function($q2) use ($startTime, $endTime) {
                  $q2->where('start_time', '<=', $startTime)
                     ->where('end_time', '>=', $endTime);
              });
        });
    }

    /**
     * Scope for voice actors at a specific time
     */
    public function scopeAtTime($query, int $timeInSeconds)
    {
        return $query->where('start_time', '<=', $timeInSeconds)
                    ->where('end_time', '>=', $timeInSeconds);
    }

    /**
     * Scope for specific role
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get voice actor data for API response
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'person' => [
                'id' => $this->person->id,
                'name' => $this->person->name,
                'image_url' => $this->person->image_url,
                'bio' => $this->person->bio
            ],
            'role' => $this->role,
            'character_name' => $this->character_name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'voice_description' => $this->voice_description,
            'is_primary' => $this->is_primary,
            'duration' => $this->end_time - $this->start_time,
            'start_time_formatted' => $this->formatTime($this->start_time),
            'end_time_formatted' => $this->formatTime($this->end_time)
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

    /**
     * Check if voice actor is active at given time
     */
    public function isActiveAt(int $timeInSeconds): bool
    {
        return $timeInSeconds >= $this->start_time && $timeInSeconds <= $this->end_time;
    }

    /**
     * Get duration of voice actor's part
     */
    public function getDurationAttribute(): int
    {
        return $this->end_time - $this->start_time;
    }

    /**
     * Validate voice actor data
     */
    public static function validateVoiceActorData(array $data, int $episodeDuration): array
    {
        $errors = [];

        if (!isset($data['start_time']) || $data['start_time'] < 0) {
            $errors[] = 'زمان شروع باید عدد مثبت باشد';
        }

        if (!isset($data['end_time']) || $data['end_time'] > $episodeDuration) {
            $errors[] = 'زمان پایان نمی‌تواند بیشتر از مدت زمان قسمت باشد';
        }

        if (isset($data['start_time']) && isset($data['end_time']) && $data['start_time'] >= $data['end_time']) {
            $errors[] = 'زمان شروع باید کمتر از زمان پایان باشد';
        }

        if (!isset($data['role']) || empty($data['role'])) {
            $errors[] = 'نقش صداپیشه الزامی است';
        }

        if (!isset($data['person_id']) || empty($data['person_id'])) {
            $errors[] = 'انتخاب صداپیشه الزامی است';
        }

        return $errors;
    }

    /**
     * Get Jalali formatted created_at date
     */
    public function getJalaliCreatedAtAttribute()
    {
        return \App\Helpers\JalaliHelper::formatForDisplay($this->created_at, 'Y/m/d');
    }

    /**
     * Get Jalali formatted created_at date with Persian month
     */
    public function getJalaliCreatedAtWithMonthAttribute()
    {
        return \App\Helpers\JalaliHelper::formatWithPersianMonth($this->created_at);
    }

    /**
     * Get Jalali formatted created_at date with Persian month and time
     */
    public function getJalaliCreatedAtWithMonthAndTimeAttribute()
    {
        return \App\Helpers\JalaliHelper::formatWithPersianMonthAndTime($this->created_at);
    }

    /**
     * Get Jalali relative time for created_at
     */
    public function getJalaliCreatedAtRelativeAttribute()
    {
        return \App\Helpers\JalaliHelper::getRelativeTime($this->created_at);
    }
}
