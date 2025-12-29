<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;
use App\Traits\HasImageUrl;

// Missing model imports
use App\Models\Category;
use App\Models\Person;
use App\Models\Episode;
use App\Models\StoryComment;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\StoryRating;
use App\Models\PlayHistory;
use App\Models\User;
use App\Models\Character;

class Story extends Model
{
    use HasFactory, HasImageUrl;

    /**
     * Workflow status constants
     */
    const WORKFLOW_WRITTEN = 'written';
    const WORKFLOW_CHARACTERS_MADE = 'characters_made';
    const WORKFLOW_RECORDED = 'recorded';
    const WORKFLOW_TIMELINE_CREATED = 'timeline_created';
    const WORKFLOW_PUBLISHED = 'published';

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
        'use_image_timeline',
        'workflow_status',
        'script_file_url',
    ];

    /**
     * Get validation rules for story creation
     */
    public static function getValidationRules($storyId = null)
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'description' => ['required', 'string', 'max:5000'],
            'image_url' => ['required', 'string', 'max:500'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'director_id' => ['nullable', 'integer', 'exists:people,id'],
            'writer_id' => ['nullable', 'integer', 'exists:people,id'],
            'author_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'narrator_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = User::find($value);
                        if ($user && !in_array($user->role, [
                            User::ROLE_VOICE_ACTOR,
                            User::ROLE_ADMIN,
                            User::ROLE_SUPER_ADMIN
                        ])) {
                            $fail('راوی باید نقش صداپیشه، ادمین یا ادمین کل داشته باشد.');
                        }
                    }
                },
            ],
            'age_group' => ['required', 'string', 'max:20'],
            'language' => ['required', 'string', 'max:10'],
            'duration' => ['required', 'integer', 'min:1', 'max:10080'], // 1 minute to 1 week
            'total_episodes' => ['integer', 'min:0', 'max:1000'],
            'free_episodes' => ['integer', 'min:0', 'max:1000'],
            'is_premium' => ['boolean'],
            'is_completely_free' => ['boolean'],
            'play_count' => ['integer', 'min:0'],
            'rating' => ['numeric', 'min:0', 'max:5'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['required', 'in:draft,pending,approved,rejected,published'],
            'published_at' => ['nullable', 'date'],
            'age_rating' => ['nullable', 'string', 'max:20'],
            'content_warnings' => ['nullable', 'array'],
            'content_warnings.*' => ['string', 'max:100'],
            'use_image_timeline' => ['boolean'],
            'workflow_status' => ['nullable', 'in:written,characters_made,recorded,timeline_created,published'],
            'script_file_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get validation messages for story validation
     */
    public static function getValidationMessages()
    {
        return [
            'title.required' => 'عنوان داستان الزامی است',
            'title.max' => 'عنوان داستان نمی‌تواند بیشتر از 200 کاراکتر باشد',
            'subtitle.max' => 'زیرعنوان نمی‌تواند بیشتر از 300 کاراکتر باشد',
            'description.required' => 'توضیحات داستان الزامی است',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 5000 کاراکتر باشد',
            'image_url.required' => 'تصویر داستان الزامی است',
            'image_url.max' => 'آدرس تصویر نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'cover_image_url.max' => 'آدرس تصویر جلد نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'category_id.required' => 'انتخاب دسته‌بندی الزامی است',
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست',
            'director_id.exists' => 'کارگردان انتخاب شده معتبر نیست',
            'writer_id.exists' => 'نویسنده انتخاب شده معتبر نیست',
            'author_id.exists' => 'نویسنده انتخاب شده معتبر نیست',
            'narrator_id.exists' => 'راوی انتخاب شده معتبر نیست',
            'age_group.required' => 'گروه سنی الزامی است',
            'age_group.max' => 'گروه سنی نمی‌تواند بیشتر از 20 کاراکتر باشد',
            'language.required' => 'زبان الزامی است',
            'language.max' => 'زبان نمی‌تواند بیشتر از 10 کاراکتر باشد',
            'duration.required' => 'مدت زمان داستان الزامی است',
            'duration.min' => 'مدت زمان داستان باید حداقل 1 دقیقه باشد',
            'duration.max' => 'مدت زمان داستان نمی‌تواند بیشتر از 1 هفته باشد',
            'total_episodes.max' => 'تعداد کل اپیزودها نمی‌تواند بیشتر از 1000 باشد',
            'free_episodes.max' => 'تعداد اپیزودهای رایگان نمی‌تواند بیشتر از 1000 باشد',
            'rating.min' => 'امتیاز نمی‌تواند کمتر از 0 باشد',
            'rating.max' => 'امتیاز نمی‌تواند بیشتر از 5 باشد',
            'status.required' => 'وضعیت داستان الزامی است',
            'status.in' => 'وضعیت انتخاب شده معتبر نیست',
            'published_at.date' => 'تاریخ انتشار باید معتبر باشد',
            'age_rating.max' => 'رده سنی نمی‌تواند بیشتر از 20 کاراکتر باشد',
            'content_warnings.*.max' => 'هشدار محتوا نمی‌تواند بیشتر از 100 کاراکتر باشد',
        ];
    }

    /**
     * Validate story data
     */
    public static function validateStoryData($data, $storyId = null)
    {
        $rules = self::getValidationRules($storyId);
        $messages = self::getValidationMessages();
        
        return validator($data, $rules, $messages);
    }

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
            'use_image_timeline' => 'boolean',
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
     * Get the author of the story (user).
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the narrator of the story (user).
     */
    public function narrator()
    {
        return $this->belongsTo(User::class, 'narrator_id');
    }

    /**
     * Get the characters for the story.
     */
    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    /**
     * Get the characters with their voice actors for the story.
     */
    public function charactersWithVoiceActors()
    {
        return $this->hasMany(Character::class)->with('voiceActor');
    }

    /**
     * Get the episodes for the story.
     */
    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    /**
     * Get the image timelines for the story.
     */
    public function imageTimelines()
    {
        return $this->hasMany(ImageTimeline::class);
    }

    /**
     * Get the comments for the story.
     */
    public function comments()
    {
        return $this->hasMany(StoryComment::class);
    }

    /**
     * Get the approved comments for the story.
     */
    public function approvedComments()
    {
        return $this->hasMany(StoryComment::class)->approved()->visible()->latest();
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
     * Get the story ratings for the story.
     */
    public function storyRatings()
    {
        return $this->hasMany(StoryRating::class);
    }

    /**
     * Get the play histories for the story.
     */
    public function playHistories()
    {
        return $this->hasMany(PlayHistory::class);
    }

    /**
     * Get the scenes for the story.
     */
    public function scenes()
    {
        return $this->hasMany(StoryScene::class);
    }

    /**
     * Get the image generation jobs for the story.
     */
    public function imageGenerationJobs()
    {
        return $this->hasMany(ImageGenerationJob::class);
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
     * Scope a query to get stories where a user is the author.
     */
    public function scopeWhereAuthor($query, $userId)
    {
        return $query->where('author_id', $userId);
    }

    /**
     * Scope a query to get stories where a user is the narrator.
     */
    public function scopeWhereNarrator($query, $userId)
    {
        return $query->where('narrator_id', $userId);
    }

    /**
     * Scope a query to get stories where a user is a voice actor (through characters).
     */
    public function scopeWhereVoiceActor($query, $userId)
    {
        return $query->whereHas('characters', function ($q) use ($userId) {
            $q->where('voice_actor_id', $userId);
        });
    }

    /**
     * Get all stories where a user has a role (author, narrator, or voice actor).
     */
    public static function getStoriesByUserRole(int $userId)
    {
        return [
            'as_author' => self::whereAuthor($userId)->get(),
            'as_narrator' => self::whereNarrator($userId)->get(),
            'as_voice_actor' => self::whereVoiceActor($userId)->get(),
        ];
    }

    /**
     * Scope a query to filter by workflow status.
     */
    public function scopeWorkflowStatus($query, $status)
    {
        return $query->where('workflow_status', $status);
    }

    /**
     * Scope a query to only include written stories.
     */
    public function scopeWritten($query)
    {
        return $query->where('workflow_status', self::WORKFLOW_WRITTEN);
    }

    /**
     * Scope a query to only include stories with characters made.
     */
    public function scopeCharactersMade($query)
    {
        return $query->where('workflow_status', self::WORKFLOW_CHARACTERS_MADE);
    }

    /**
     * Scope a query to only include recorded stories.
     */
    public function scopeRecorded($query)
    {
        return $query->where('workflow_status', self::WORKFLOW_RECORDED);
    }

    /**
     * Scope a query to only include stories with timeline created.
     */
    public function scopeTimelineCreated($query)
    {
        return $query->where('workflow_status', self::WORKFLOW_TIMELINE_CREATED);
    }

    /**
     * Scope a query to only include published stories (workflow).
     */
    public function scopeWorkflowPublished($query)
    {
        return $query->where('workflow_status', self::WORKFLOW_PUBLISHED);
    }

    /**
     * Transition workflow status to the next stage.
     * 
     * @param string $newStatus
     * @return bool
     */
    public function transitionWorkflowStatus(string $newStatus): bool
    {
        $validTransitions = [
            self::WORKFLOW_WRITTEN => [self::WORKFLOW_CHARACTERS_MADE],
            self::WORKFLOW_CHARACTERS_MADE => [self::WORKFLOW_RECORDED],
            self::WORKFLOW_RECORDED => [self::WORKFLOW_TIMELINE_CREATED],
            self::WORKFLOW_TIMELINE_CREATED => [self::WORKFLOW_PUBLISHED],
            self::WORKFLOW_PUBLISHED => [], // Published is final
        ];

        $currentStatus = $this->workflow_status ?? self::WORKFLOW_WRITTEN;

        if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
            return false;
        }

        $this->workflow_status = $newStatus;
        return $this->save();
    }

    /**
     * Check if story is at a specific workflow stage.
     */
    public function isWorkflowStatus(string $status): bool
    {
        return $this->workflow_status === $status;
    }

    /**
     * Get workflow status label in Persian.
     */
    public function getWorkflowStatusLabelAttribute(): string
    {
        return match($this->workflow_status) {
            self::WORKFLOW_WRITTEN => 'نوشته شده',
            self::WORKFLOW_CHARACTERS_MADE => 'شخصیت‌ها ساخته شده',
            self::WORKFLOW_RECORDED => 'ضبط شده',
            self::WORKFLOW_TIMELINE_CREATED => 'تایم‌لاین ایجاد شده',
            self::WORKFLOW_PUBLISHED => 'منتشر شده',
            default => 'نامشخص',
        };
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
     * Get the total duration of all published episodes in seconds
     */
    public function getTotalDurationAttribute(): int
    {
        return $this->episodes()
            ->where('status', 'published')
            ->sum('duration') ?? 0;
    }

    /**
     * Get the total number of episodes
     */
    public function getTotalEpisodesCountAttribute(): int
    {
        return $this->episodes()->count();
    }

    /**
     * Get the number of published episodes
     */
    public function getPublishedEpisodesCountAttribute(): int
    {
        return $this->episodes()->where('status', 'published')->count();
    }

    /**
     * Get the number of free episodes
     */
    public function getFreeEpisodesCountAttribute(): int
    {
        return $this->episodes()
            ->where('status', 'published')
            ->where('is_premium', false)
            ->count();
    }

    /**
     * Get the number of premium episodes
     */
    public function getPremiumEpisodesCountAttribute(): int
    {
        return $this->episodes()
            ->where('status', 'published')
            ->where('is_premium', true)
            ->count();
    }

    /**
     * Get the number of draft episodes
     */
    public function getDraftEpisodesCountAttribute(): int
    {
        return $this->episodes()->where('status', 'draft')->count();
    }

    /**
     * Get the number of pending episodes
     */
    public function getPendingEpisodesCountAttribute(): int
    {
        return $this->episodes()->where('status', 'pending')->count();
    }

    /**
     * Get formatted duration string
     */
    public function getFormattedDurationAttribute(): string
    {
        $totalSeconds = $this->total_duration;
        
        if ($totalSeconds == 0) {
            return '0:00';
        }
        
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Check if story has any episodes
     */
    public function hasEpisodes(): bool
    {
        return $this->episodes()->exists();
    }

    /**
     * Check if story has published episodes
     */
    public function hasPublishedEpisodes(): bool
    {
        return $this->episodes()->where('status', 'published')->exists();
    }

    /**
     * Get the first published episode
     */
    public function getFirstEpisodeAttribute(): ?Episode
    {
        return $this->episodes()
            ->where('status', 'published')
            ->orderBy('episode_number')
            ->first();
    }

    /**
     * Get the last published episode
     */
    public function getLastEpisodeAttribute(): ?Episode
    {
        return $this->episodes()
            ->where('status', 'published')
            ->orderBy('episode_number', 'desc')
            ->first();
    }

    /**
     * Update story statistics based on episodes
     */
    public function updateStatistics(): void
    {
        $this->update([
            'duration' => $this->total_duration,
            'total_episodes' => $this->total_episodes_count,
            'free_episodes' => $this->free_episodes_count,
        ]);
    }

    /**
     * Recalculate and update play count from episodes
     */
    public function recalculatePlayCount(): void
    {
        $totalPlayCount = $this->episodes()->sum('play_count') ?? 0;
        $this->update(['play_count' => $totalPlayCount]);
    }

    /**
     * Get the image URL for the story
     */
    public function getImageUrlAttribute()
    {
        return $this->getImageUrlFromPath($this->attributes['image_url'] ?? null);
    }

    /**
     * Get the cover image URL for the story
     */
    public function getCoverImageUrlAttribute()
    {
        return $this->getImageUrlFromPath($this->attributes['cover_image_url'] ?? null);
    }
}
