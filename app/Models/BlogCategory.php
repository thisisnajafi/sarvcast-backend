<?php

namespace App\Models;

use App\Support\PersianSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'seo_title',
        'meta_description',
        'focus_keyword',
        'og_image_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (filled($category->slug)) {
                return;
            }

            $category->slug = PersianSlug::unique(
                $category->name,
                fn (string $slug) => static::query()
                    ->where('slug', $slug)
                    ->when($category->exists, fn ($q) => $q->whereKeyNot($category->getKey()))
                    ->exists(),
                140
            );
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }
}
