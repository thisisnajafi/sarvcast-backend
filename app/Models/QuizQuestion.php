<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    protected $fillable = [
        'question_text',
        'question_type',
        'difficulty_level',
        'points',
        'time_limit',
        'explanation',
        'story_id',
        'episode_id',
        'is_active',
        'correct_answer',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'points' => 'integer',
            'time_limit' => 'integer',
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

    public function options(): HasMany
    {
        return $this->hasMany(QuizQuestionOption::class);
    }
}
