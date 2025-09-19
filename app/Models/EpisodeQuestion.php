<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpisodeQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'episode_id',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'explanation',
        'coins_reward',
        'difficulty_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the episode that owns the question
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Get the quiz attempts for this question
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(UserQuizAttempt::class, 'question_id');
    }

    /**
     * Check if user has already attempted this question
     */
    public function hasUserAttempted(int $userId): bool
    {
        return $this->attempts()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's attempt for this question
     */
    public function getUserAttempt(int $userId): ?UserQuizAttempt
    {
        return $this->attempts()->where('user_id', $userId)->first();
    }

    /**
     * Check if answer is correct
     */
    public function isCorrectAnswer(string $answer): bool
    {
        return $this->correct_answer === $answer;
    }

    /**
     * Get all options as array
     */
    public function getOptions(): array
    {
        return [
            'a' => $this->option_a,
            'b' => $this->option_b,
            'c' => $this->option_c,
            'd' => $this->option_d,
        ];
    }

    /**
     * Get correct option text
     */
    public function getCorrectOptionText(): string
    {
        return $this->getOptions()[$this->correct_answer];
    }

    /**
     * Scope to get active questions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get questions by difficulty
     */
    public function scopeByDifficulty($query, int $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope to get questions for episode
     */
    public function scopeForEpisode($query, int $episodeId)
    {
        return $query->where('episode_id', $episodeId);
    }

    /**
     * Get question statistics
     */
    public function getStatistics(): array
    {
        $attempts = $this->attempts();
        $totalAttempts = $attempts->count();
        $correctAttempts = $attempts->where('is_correct', true)->count();

        return [
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $correctAttempts,
            'accuracy_rate' => $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 2) : 0,
            'coins_distributed' => $attempts->where('is_correct', true)->sum('coins_earned'),
        ];
    }
}
