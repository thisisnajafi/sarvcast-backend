<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryProductionFile extends Model
{
    public const TYPE_CHARACTERS = 'characters_and_objects';
    public const TYPE_IMAGE_PROMPTS = 'image_prompts';
    public const TYPE_STORY_SCRIPT = 'story_script';

    protected $fillable = [
        'story_slug',
        'episode_slug',
        'file_type',
        'original_filename',
        'storage_path',
        'source_path',
        'story_id',
        'episode_id',
        'episode_number',
        'parsed_summary',
        'content_hash',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_summary' => 'array',
            'imported_at' => 'datetime',
            'episode_number' => 'integer',
            'story_id' => 'integer',
            'episode_id' => 'integer',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
