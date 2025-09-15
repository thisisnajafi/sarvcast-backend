<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
}
