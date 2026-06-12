<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContentModeration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_type',
        'content_id',
        'story_id',
        'episode_id',
        'reason',
        'severity',
        'status',
        'notes',
        'evidence_files',
        'moderator_id',
        'moderated_at',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
