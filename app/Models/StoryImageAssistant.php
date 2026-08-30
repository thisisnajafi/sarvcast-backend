<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryImageAssistant extends Model
{
    protected $table = 'story_image_assistants';

    protected $fillable = [
        'story_id',
        'user_id',
        'assigned_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'story_id' => 'integer',
            'user_id' => 'integer',
            'assigned_by' => 'integer',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
