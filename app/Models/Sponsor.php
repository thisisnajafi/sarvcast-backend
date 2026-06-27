<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Sponsor extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'logo_path',
        'tagline',
        'description',
        'phone',
        'website_url',
        'instagram_handle',
        'address',
        'latitude',
        'longitude',
        'map_label',
        'is_active',
        'display_order',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'display_order' => 'integer',
        ];
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (filter_var($this->logo_path, FILTER_VALIDATE_URL)) {
            return $this->logo_path;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Lightweight sponsor payload for story detail API.
     *
     * @return array<string, mixed>
     */
    public function toStorySummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => $this->logo_url,
            'tagline' => $this->tagline,
        ];
    }

    /**
     * Full sponsor profile for public detail API.
     *
     * @return array<string, mixed>
     */
    public function toPublicProfile(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => $this->logo_url,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'instagram_handle' => $this->instagram_handle,
            'address' => $this->address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'map_label' => $this->map_label,
        ];

        if ($this->relationLoaded('stories')) {
            $data['stories'] = $this->stories->map(function (Story $story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'subtitle' => $story->subtitle,
                    'image_url' => $story->image_url,
                    'cover_image_url' => $story->cover_image_url,
                    'category_id' => $story->category_id,
                ];
            })->values()->all();
        }

        return $data;
    }
}
