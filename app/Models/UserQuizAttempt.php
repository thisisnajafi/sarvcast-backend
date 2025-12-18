<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'selected_answer',
        'is_correct',
        'coins_earned',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    /**
     * Get the user that made the attempt
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the question that was attempted
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(EpisodeQuestion::class, 'question_id');
    }

    /**
     * Scope to get correct attempts
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    /**
     * Scope to get incorrect attempts
     */
    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    /**
     * Scope to get attempts by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get attempts by question
     */
    public function scopeByQuestion($query, int $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    /**
     * Scope to get recent attempts
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('attempted_at', '>=', now()->subDays($days));
    }

    /**
     * Get user's quiz statistics
     */
    public static function getUserStatistics(int $userId, int $days = 30): array
    {
        $attempts = self::byUser($userId)->recent($days)->get();
        
        $totalAttempts = $attempts->count();
        $correctAttempts = $attempts->where('is_correct', true)->count();
        $totalCoinsEarned = $attempts->sum('coins_earned');

        return [
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $correctAttempts,
            'accuracy_rate' => $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 2) : 0,
            'total_coins_earned' => $totalCoinsEarned,
            'average_coins_per_attempt' => $totalAttempts > 0 ? round($totalCoinsEarned / $totalAttempts, 2) : 0,
        ];
    }

    /**
     * Get question difficulty statistics
     */
    public static function getDifficultyStatistics(int $days = 30): array
    {
        $attempts = self::recent($days)->with('question')->get();
        
        $byDifficulty = $attempts->groupBy('question.difficulty_level')->map(function ($group) {
            $total = $group->count();
            $correct = $group->where('is_correct', true)->count();
            
            return [
                'total_attempts' => $total,
                'correct_attempts' => $correct,
                'accuracy_rate' => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            ];
        });

        return $byDifficulty->toArray();
    }
}
