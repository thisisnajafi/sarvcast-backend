<?php

namespace App\Models;

use App\Support\PersianSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogPost extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';

    public const SCHEMA_TYPES = ['Article', 'FAQPage', 'HowTo', 'NewsArticle'];

    protected $fillable = [
        'blog_category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
        'seo_title',
        'meta_description',
        'focus_keyword',
        'secondary_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_url',
        'twitter_card',
        'noindex',
        'schema_type',
        'faqs',
        'how_to_steps',
        'word_count',
        'reading_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'secondary_keywords' => 'array',
            'faqs' => 'array',
            'how_to_steps' => 'array',
            'noindex' => 'boolean',
            'word_count' => 'integer',
            'reading_time_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            if (! filled($post->slug)) {
                $post->slug = PersianSlug::unique(
                    $post->title,
                    fn (string $slug) => static::query()
                        ->where('slug', $slug)
                        ->when($post->exists, fn ($q) => $q->whereKeyNot($post->getKey()))
                        ->exists(),
                    150
                );
            }

            [$words, $minutes] = self::measureContent((string) $post->content);
            $post->word_count = $words;
            $post->reading_time_minutes = $minutes;

            if ($post->status === self::STATUS_PUBLISHED && ! $post->published_at) {
                $post->published_at = now();
            }
        });
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function measureContent(string $html): array
    {
        $text = trim(preg_replace(
            '/\s+/u',
            ' ',
            html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        ) ?? '');

        if ($text === '') {
            return [0, 0];
        }

        $words = count(preg_split('/\s+/u', $text) ?: []);

        return [$words, max(1, (int) ceil($words / 200))];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
