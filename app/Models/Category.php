<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasImageUrl;

class Category extends Model
{
    use HasFactory, HasImageUrl;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_path',
        'color',
        'story_count',
        'total_episodes',
        'total_duration',
        'average_rating',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function publishedStories()
    {
        return $this->hasMany(Story::class)->where('status', 'published');
    }

    public function userProfiles()
    {
        return $this->hasMany(UserProfile::class, 'favorite_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
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
     * Get the image URL for the category
     */
    public function getImageUrlAttribute()
    {
        return $this->getImageUrlFromPath($this->icon_path);
    }

    /**
     * Get the slug for the category (auto-generated from name if not set)
     */
    public function getSlugAttribute($value)
    {
        if ($value) {
            return $value;
        }
        
        // Generate slug from name if not set
        return \Illuminate\Support\Str::slug($this->name);
    }

    /**
     * Get the status field (alias for is_active)
     */
    public function getStatusAttribute()
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Get the order field (alias for sort_order)
     */
    public function getOrderAttribute()
    {
        return $this->sort_order;
    }

    /**
     * Get the icon_path field (alias for icon_path)
     */
    public function getIconPathAttribute($value)
    {
        return $value ?: 'assets/icons/default-category.svg';
    }
}
