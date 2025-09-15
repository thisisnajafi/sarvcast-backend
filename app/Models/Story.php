<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Story extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image_url',
        'cover_image_url',
        'category_id',
        'director_id',
        'writer_id',
        'author_id',
        'narrator_id',
        'age_group',
        'language',
        'duration',
        'total_episodes',
        'free_episodes',
        'is_premium',
        'is_completely_free',
        'play_count',
        'rating',
        'tags',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'tags' => 'array',
            'is_premium' => 'boolean',
            'is_completely_free' => 'boolean',
        ];
    }

    /**
     * Get the category that owns the story.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the director of the story.
     */
    public function director()
    {
        return $this->belongsTo(Person::class, 'director_id');
    }

    /**
     * Get the writer of the story.
     */
    public function writer()
    {
        return $this->belongsTo(Person::class, 'writer_id');
    }

    /**
     * Get the author of the story.
     */
    public function author()
    {
        return $this->belongsTo(Person::class, 'author_id');
    }

    /**
     * Get the narrator of the story.
     */
    public function narrator()
    {
        return $this->belongsTo(Person::class, 'narrator_id');
    }

    /**
     * Get the episodes for the story.
     */
    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    /**
     * Get the published episodes for the story.
     */
    public function publishedEpisodes()
    {
        return $this->hasMany(Episode::class)->where('status', 'published');
    }

    /**
     * Get the free episodes for the story.
     */
    public function freeEpisodes()
    {
        return $this->hasMany(Episode::class)->where('is_premium', false);
    }

    /**
     * Get the premium episodes for the story.
     */
    public function premiumEpisodes()
    {
        return $this->hasMany(Episode::class)->where('is_premium', true);
    }

    /**
     * Get the people associated with the story.
     */
    public function people()
    {
        return $this->belongsToMany(Person::class, 'story_people')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the favorites for the story.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the ratings for the story.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the play histories for the story.
     */
    public function playHistories()
    {
        return $this->hasMany(PlayHistory::class);
    }

    /**
     * Scope a query to only include published stories.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include premium stories.
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope a query to only include free stories.
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope a query to filter by age group.
     */
    public function scopeForAgeGroup($query, $ageGroup)
    {
        return $query->where('age_group', $ageGroup);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Check if story is published.
     */
    public function isPublished()
    {
        return $this->status === 'published';
    }

    /**
     * Check if story is premium.
     */
    public function isPremium()
    {
        return $this->is_premium;
    }

    /**
     * Check if story is completely free.
     */
    public function isCompletelyFree()
    {
        return $this->is_completely_free;
    }
}
