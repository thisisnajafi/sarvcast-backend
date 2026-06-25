<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAsset extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'uuid',
        'disk',
        'path',
        'url',
        'thumbnail_path',
        'thumbnail_url',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'width',
        'height',
        'folder',
        'alt_text',
        'title',
        'tags',
        'uploaded_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url ?? $this->url,
            'original_name' => $this->original_name,
            'title' => $this->title,
            'alt_text' => $this->alt_text,
            'folder' => $this->folder,
            'width' => $this->width,
            'height' => $this->height,
            'size_bytes' => $this->size_bytes,
            'mime_type' => $this->mime_type,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
