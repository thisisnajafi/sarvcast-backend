<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
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
     * Get Jalali formatted updated_at date
     */
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
}
