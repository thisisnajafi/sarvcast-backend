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
        'moderation_status',
        'moderator_id',
        'moderated_at',
        'moderation_notes',
        'moderation_rating',
        'age_rating',
        'content_warnings',
        'rejection_code',
        'rejection_suggestions',
        'allow_resubmission',
        'moderation_priority',
        'flag_type',
        'moderation_history',
        'total_plays',
        'total_favorites',
        'total_ratings',
        'avg_rating',
        'total_duration_played',
        'unique_listeners',
        'completion_count',
        'completion_rate',
        'share_count',
        'download_count',
        'last_played_at',
        'trending_since',
        'analytics_data',
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
            'moderated_at' => 'datetime',
            'tags' => 'array',
            'content_warnings' => 'array',
            'moderation_history' => 'array',
            'analytics_data' => 'array',
            'last_played_at' => 'datetime',
            'trending_since' => 'datetime',
            'avg_rating' => 'decimal:2',
            'completion_rate' => 'decimal:2',
            'is_premium' => 'boolean',
            'is_completely_free' => 'boolean',
            'allow_resubmission' => 'boolean',
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
     * Get the moderator who reviewed this story.
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Get the reports for this story.
     */
    public function reports()
    {
        return $this->morphMany(Report::class, 'content', 'content_type', 'content_id');
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

    /**
     * Scope a query to only include pending moderation stories.
     */
    public function scopePendingModeration($query)
    {
        return $query->where('moderation_status', 'pending');
    }

    /**
     * Scope a query to only include approved stories.
     */
    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }

    /**
     * Scope a query to only include rejected stories.
     */
    public function scopeRejected($query)
    {
        return $query->where('moderation_status', 'rejected');
    }

    /**
     * Scope a query to only include flagged stories.
     */
    public function scopeFlagged($query)
    {
        return $query->where('moderation_status', 'flagged');
    }

    /**
     * Scope a query to only include high priority moderation stories.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('moderation_priority', 'high');
    }

    /**
     * Check if story is pending moderation
     */
    public function isPendingModeration(): bool
    {
        return $this->moderation_status === 'pending';
    }

    /**
     * Check if story is approved
     */
    public function isApproved(): bool
    {
        return $this->moderation_status === 'approved';
    }

    /**
     * Check if story is rejected
     */
    public function isRejected(): bool
    {
        return $this->moderation_status === 'rejected';
    }

    /**
     * Check if story is flagged
     */
    public function isFlagged(): bool
    {
        return $this->moderation_status === 'flagged';
    }

    /**
     * Check if story is high priority
     */
    public function isHighPriority(): bool
    {
        return $this->moderation_priority === 'high';
    }

    /**
     * Get moderation status label
     */
    public function getModerationStatusLabelAttribute(): string
    {
        return match($this->moderation_status) {
            'pending' => 'در انتظار',
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
            'flagged' => 'پرچم‌گذاری شده',
            default => 'نامشخص'
        };
    }

    /**
     * Get moderation priority label
     */
    public function getModerationPriorityLabelAttribute(): string
    {
        return match($this->moderation_priority) {
            'low' => 'کم',
            'medium' => 'متوسط',
            'high' => 'بالا',
            default => 'نامشخص'
        };
    }

    /**
     * Get age rating label
     */
    public function getAgeRatingLabelAttribute(): string
    {
        return match($this->age_rating) {
            'G' => 'همه سنین',
            'PG' => 'با راهنمایی والدین',
            'PG-13' => 'بالای 13 سال',
            'R' => 'بالای 17 سال',
            default => 'نامشخص'
        };
    }
}
