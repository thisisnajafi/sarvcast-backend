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
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'image_urls' => 'array',
            'tags' => 'array',
            'is_premium' => 'boolean',
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
