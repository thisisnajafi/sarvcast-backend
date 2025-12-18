<?php

namespace App\Services;

use App\Models\EpisodeQuestion;
use App\Models\UserQuizAttempt;
use App\Models\Episode;
use App\Services\CoinService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuizService
{
    protected $coinService;

    public function __construct(CoinService $coinService)
    {
        $this->coinService = $coinService;
    }

    /**
     * Get questions for an episode
     */
    public function getEpisodeQuestions(int $episodeId, int $userId = null): array
    {
        $cacheKey = "episode_questions_{$episodeId}";
        
        $questions = Cache::remember($cacheKey, 3600, function() use ($episodeId) {
            return EpisodeQuestion::forEpisode($episodeId)
                ->active()
                ->get()
                ->map(function($question) {
                    return [
                        'id' => $question->id,
                        'question' => $question->question,
                        'options' => $question->getOptions(),
                        'difficulty_level' => $question->difficulty_level,
                        'coins_reward' => $question->coins_reward,
                    ];
                });
        });

        // If user is provided, check which questions they've already attempted
        if ($userId) {
            $attemptedQuestions = UserQuizAttempt::byUser($userId)
                ->whereIn('question_id', $questions->pluck('id'))
                ->pluck('question_id')
                ->toArray();

            $questions = $questions->map(function($question) use ($attemptedQuestions) {
                $question['attempted'] = in_array($question['id'], $attemptedQuestions);
                return $question;
            });
        }

        return [
            'success' => true,
            'message' => 'سوالات اپیزود دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'questions' => $questions,
                'total_questions' => $questions->count(),
                'total_coins_available' => $questions->sum('coins_reward'),
            ]
        ];
    }

    /**
     * Submit quiz answer
     */
    public function submitAnswer(int $userId, int $questionId, string $selectedAnswer): array
    {
        try {
            DB::beginTransaction();

            $question = EpisodeQuestion::findOrFail($questionId);
            
            // Check if user has already attempted this question
            if ($question->hasUserAttempted($userId)) {
                return [
                    'success' => false,
                    'message' => 'شما قبلاً به این سوال پاسخ داده‌اید'
                ];
            }

            // Check if answer is correct
            $isCorrect = $question->isCorrectAnswer($selectedAnswer);
            $coinsEarned = $isCorrect ? $question->coins_reward : 0;

            // Create quiz attempt record
            $attempt = UserQuizAttempt::create([
                'user_id' => $userId,
                'question_id' => $questionId,
                'selected_answer' => $selectedAnswer,
                'is_correct' => $isCorrect,
                'coins_earned' => $coinsEarned,
                'attempted_at' => now(),
            ]);

            // Award coins if answer is correct
            if ($isCorrect) {
                $coinResult = $this->coinService->awardQuizCoins($userId, $questionId, true);
                if (!$coinResult['success']) {
                    Log::warning('Failed to award quiz coins', [
                        'user_id' => $userId,
                        'question_id' => $questionId,
                        'error' => $coinResult['message']
                    ]);
                }
            }

            DB::commit();

            // Clear cache
            $this->clearQuizCache($questionId);

            return [
                'success' => true,
                'message' => $isCorrect ? 'پاسخ صحیح! سکه اعطا شد' : 'پاسخ نادرست',
                'data' => [
                    'is_correct' => $isCorrect,
                    'correct_answer' => $question->correct_answer,
                    'correct_option_text' => $question->getCorrectOptionText(),
                    'explanation' => $question->explanation,
                    'coins_earned' => $coinsEarned,
                    'attempt_id' => $attempt->id,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quiz answer submission failed', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'selected_answer' => $selectedAnswer,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت پاسخ: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's quiz statistics
     */
    public function getUserQuizStatistics(int $userId, int $days = 30): array
    {
        $stats = UserQuizAttempt::getUserStatistics($userId, $days);
        
        return [
            'success' => true,
            'message' => 'آمار کویز کاربر دریافت شد',
            'data' => [
                'period_days' => $days,
                'total_attempts' => $stats['total_attempts'],
                'correct_attempts' => $stats['correct_attempts'],
                'accuracy_rate' => $stats['accuracy_rate'],
                'total_coins_earned' => $stats['total_coins_earned'],
                'average_coins_per_attempt' => $stats['average_coins_per_attempt'],
            ]
        ];
    }

    /**
     * Get quiz statistics for an episode
     */
    public function getEpisodeQuizStatistics(int $episodeId): array
    {
        $questions = EpisodeQuestion::forEpisode($episodeId)->active()->get();
        
        $statistics = $questions->map(function($question) {
            return [
                'question_id' => $question->id,
                'question_text' => $question->question,
                'difficulty_level' => $question->difficulty_level,
                'coins_reward' => $question->coins_reward,
                'statistics' => $question->getStatistics(),
            ];
        });

        $totalAttempts = $statistics->sum('statistics.total_attempts');
        $totalCorrect = $statistics->sum('statistics.correct_attempts');
        $totalCoinsDistributed = $statistics->sum('statistics.coins_distributed');

        return [
            'success' => true,
            'message' => 'آمار کویز اپیزود دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'total_questions' => $questions->count(),
                'total_attempts' => $totalAttempts,
                'total_correct_attempts' => $totalCorrect,
                'overall_accuracy_rate' => $totalAttempts > 0 ? round(($totalCorrect / $totalAttempts) * 100, 2) : 0,
                'total_coins_distributed' => $totalCoinsDistributed,
                'question_statistics' => $statistics,
            ]
        ];
    }

    /**
     * Get global quiz statistics
     */
    public function getGlobalQuizStatistics(int $days = 30): array
    {
        $cacheKey = "global_quiz_stats_{$days}";
        
        $stats = Cache::remember($cacheKey, 1800, function() use ($days) {
            $attempts = UserQuizAttempt::recent($days)->get();
            $difficultyStats = UserQuizAttempt::getDifficultyStatistics($days);
            
            return [
                'total_attempts' => $attempts->count(),
                'correct_attempts' => $attempts->where('is_correct', true)->count(),
                'overall_accuracy_rate' => $attempts->count() > 0 ? 
                    round(($attempts->where('is_correct', true)->count() / $attempts->count()) * 100, 2) : 0,
                'total_coins_distributed' => $attempts->where('is_correct', true)->sum('coins_earned'),
                'average_coins_per_correct_answer' => $attempts->where('is_correct', true)->avg('coins_earned'),
                'difficulty_statistics' => $difficultyStats,
                'attempts_by_day' => $attempts->groupBy(function($attempt) {
                    return $attempt->attempted_at->format('Y-m-d');
                })->map->count(),
            ];
        });

        return [
            'success' => true,
            'message' => 'آمار کلی کویز دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Create a new question for an episode
     */
    public function createQuestion(int $episodeId, array $questionData): array
    {
        try {
            $question = EpisodeQuestion::create([
                'episode_id' => $episodeId,
                'question' => $questionData['question'],
                'option_a' => $questionData['option_a'],
                'option_b' => $questionData['option_b'],
                'option_c' => $questionData['option_c'],
                'option_d' => $questionData['option_d'],
                'correct_answer' => $questionData['correct_answer'],
                'explanation' => $questionData['explanation'] ?? null,
                'coins_reward' => $questionData['coins_reward'] ?? 5,
                'difficulty_level' => $questionData['difficulty_level'] ?? 1,
                'is_active' => $questionData['is_active'] ?? true,
            ]);

            // Clear cache
            $this->clearQuizCache($episodeId);

            return [
                'success' => true,
                'message' => 'سوال با موفقیت ایجاد شد',
                'data' => [
                    'question_id' => $question->id,
                    'question' => $question->question,
                    'options' => $question->getOptions(),
                    'correct_answer' => $question->correct_answer,
                    'coins_reward' => $question->coins_reward,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Question creation failed', [
                'episode_id' => $episodeId,
                'question_data' => $questionData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد سوال: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing question
     */
    public function updateQuestion(int $questionId, array $questionData): array
    {
        try {
            $question = EpisodeQuestion::findOrFail($questionId);
            
            $question->update($questionData);

            // Clear cache
            $this->clearQuizCache($question->episode_id);

            return [
                'success' => true,
                'message' => 'سوال با موفقیت به‌روزرسانی شد',
                'data' => [
                    'question_id' => $question->id,
                    'question' => $question->question,
                    'options' => $question->getOptions(),
                    'correct_answer' => $question->correct_answer,
                    'coins_reward' => $question->coins_reward,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Question update failed', [
                'question_id' => $questionId,
                'question_data' => $questionData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی سوال: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a question
     */
    public function deleteQuestion(int $questionId): array
    {
        try {
            $question = EpisodeQuestion::findOrFail($questionId);
            $episodeId = $question->episode_id;
            
            $question->delete();

            // Clear cache
            $this->clearQuizCache($episodeId);

            return [
                'success' => true,
                'message' => 'سوال با موفقیت حذف شد'
            ];
        } catch (\Exception $e) {
            Log::error('Question deletion failed', [
                'question_id' => $questionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در حذف سوال: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clear quiz cache
     */
    private function clearQuizCache(int $episodeId): void
    {
        Cache::forget("episode_questions_{$episodeId}");
        Cache::forget("global_quiz_stats_30");
        Cache::forget("global_quiz_stats_7");
    }
}
