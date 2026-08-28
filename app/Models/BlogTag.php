<?php

namespace App\Models;

use App\Support\PersianSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'seo_title',
        'meta_description',
        'focus_keyword',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $tag): void {
            if (filled($tag->slug)) {
                return;
            }

            $tag->slug = PersianSlug::unique(
                $tag->name,
                fn (string $slug) => static::query()
                    ->where('slug', $slug)
                    ->when($tag->exists, fn ($q) => $q->whereKeyNot($tag->getKey()))
                    ->exists(),
                100
            );
        });
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }
}
