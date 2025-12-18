<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasImageUrl;

class Person extends Model
{
    use HasImageUrl;
    protected $fillable = [
        'name',
        'bio',
        'image_url',
        'roles',
        'total_stories',
        'total_episodes',
        'average_rating',
        'is_verified',
        'last_active_at',
    ];

    protected $casts = [
        'roles' => 'array',
        'is_verified' => 'boolean',
        'average_rating' => 'decimal:2',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the stories associated with this person.
     */
    public function stories(): BelongsToMany
    {
        return $this->belongsToMany(Story::class, 'story_people')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the episodes associated with this person.
     */
    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(Episode::class, 'episode_people')
                    ->withPivot('role')
                    ->withTimestamps();
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
     * Get API response format for Person
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bio' => $this->bio,
            'image_url' => $this->getImageUrlFromPath($this->image_url),
            'roles' => $this->roles ?? [],
            'total_stories' => $this->total_stories ?? 0,
            'total_episodes' => $this->total_episodes ?? 0,
            'average_rating' => $this->average_rating ?? 0.0,
            'is_verified' => $this->is_verified ?? false,
            'last_active_at' => $this->last_active_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
    public function getJalaliUpdatedAtAttribute()
    {
        return \App\Helpers\JalaliHelper::formatForDisplay($this->updated_at, 'Y/m/d');
    }

    /**
     * Get Jalali formatted updated_at date with Persian month
     */
    public function getJalaliUpdatedAtWithMonthAttribute()
    {
        return \App\Helpers\JalaliHelper::formatWithPersianMonth($this->updated_at);
    }

    /**
     * Get Jalali formatted updated_at date with Persian month and time
     */
    public function getJalaliUpdatedAtWithMonthAndTimeAttribute()
    {
        return \App\Helpers\JalaliHelper::formatWithPersianMonthAndTime($this->updated_at);
    }

    /**
     * Get Jalali formatted last_active_at date
     */
    public function getJalaliLastActiveAtAttribute()
    {
        return $this->last_active_at ? \App\Helpers\JalaliHelper::formatForDisplay($this->last_active_at, 'Y/m/d H:i') : null;
    }

    /**
     * Get Jalali formatted last_active_at date with Persian month
     */
    public function getJalaliLastActiveAtWithMonthAttribute()
    {
        return $this->last_active_at ? \App\Helpers\JalaliHelper::formatWithPersianMonth($this->last_active_at) : null;
    }

    /**
     * Get Jalali formatted last_active_at date with Persian month and time
     */
    public function getJalaliLastActiveAtWithMonthAndTimeAttribute()
    {
        return $this->last_active_at ? \App\Helpers\JalaliHelper::formatWithPersianMonthAndTime($this->last_active_at) : null;
    }

    /**
     * Get Jalali relative time for created_at
     */
    public function getJalaliCreatedAtRelativeAttribute()
    {
        return \App\Helpers\JalaliHelper::getRelativeTime($this->created_at);
    }

    /**
     * Get Jalali relative time for updated_at
     */
    public function getJalaliUpdatedAtRelativeAttribute()
    {
        return \App\Helpers\JalaliHelper::getRelativeTime($this->updated_at);
    }

    /**
     * Get Jalali relative time for last_active_at
     */
    public function getJalaliLastActiveAtRelativeAttribute()
    {
        return $this->last_active_at ? \App\Helpers\JalaliHelper::getRelativeTime($this->last_active_at) : null;
    }

    /**
     * Get the image URL for the person
     */
    public function getImageUrlAttribute()
    {
        return $this->getImageUrlFromPath($this->attributes['image_url'] ?? null);
    }
}
