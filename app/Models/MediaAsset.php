<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAsset extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_IMAGE = 'image';

    public const TYPE_AUDIO = 'audio';

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
        'media_type',
        'size_bytes',
        'width',
        'height',
        'duration_seconds',
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
            'duration_seconds' => 'integer',
        ];
    }

    public function isAudio(): bool
    {
        return $this->media_type === self::TYPE_AUDIO
            || str_starts_with((string) $this->mime_type, 'audio/');
    }

    public function isImage(): bool
    {
        return ! $this->isAudio();
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
        $isAudio = $this->isAudio();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'url' => $this->url,
            'thumbnail_url' => $isAudio ? null : ($this->thumbnail_url ?? $this->url),
            'original_name' => $this->original_name,
            'title' => $this->title,
            'alt_text' => $this->alt_text,
            'folder' => $this->folder,
            'media_type' => $this->media_type ?? ($isAudio ? self::TYPE_AUDIO : self::TYPE_IMAGE),
            'width' => $this->width,
            'height' => $this->height,
            'duration_seconds' => $this->duration_seconds,
            'size_bytes' => $this->size_bytes,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
