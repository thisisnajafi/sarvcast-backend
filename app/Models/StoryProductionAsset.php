<?php

namespace App\Models;

use App\Traits\HasImageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryProductionAsset extends Model
{
    use HasImageUrl;

    public const TYPE_CHARACTER = 'character';
    public const TYPE_OBJECT = 'object';
    public const TYPE_SETTING = 'setting';
    public const TYPE_SCENE = 'scene';
    public const TYPE_COVER = 'cover';

    protected $fillable = [
        'story_slug',
        'episode_slug',
        'asset_type',
        'asset_key',
        'name_persian',
        'name_english',
        'prompt',
        'image_url',
        'storage_path',
        'story_id',
        'episode_id',
        'character_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'story_id' => 'integer',
            'episode_id' => 'integer',
            'character_id' => 'integer',
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

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
