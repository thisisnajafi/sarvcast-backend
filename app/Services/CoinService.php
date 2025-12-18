<?php

namespace App\Services;

use App\Models\UserCoin;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CoinService
{
    /**
     * Award coins to user
     */
    public function awardCoins(int $userId, int $amount, string $sourceType, int $sourceId = null, string $description = '', array $metadata = []): array
    {
        try {
            DB::beginTransaction();

            $userCoins = UserCoin::firstOrCreate(['user_id' => $userId]);
            $userCoins->addCoins($amount, $sourceType, $sourceId, $description);

            DB::commit();

            // Clear cache
            $this->clearUserCache($userId);

            return [
                'success' => true,
                'message' => 'سکه‌ها با موفقیت اعطا شد',
                'data' => [
                    'amount' => $amount,
                    'new_balance' => $userCoins->available_coins,
                    'total_coins' => $userCoins->total_coins,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Coin award failed', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اعطای سکه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Spend coins from user's balance
     */
    public function spendCoins(int $userId, int $amount, string $sourceType, int $sourceId = null, string $description = '', array $metadata = []): array
    {
        try {
            DB::beginTransaction();

            $userCoins = UserCoin::firstOrCreate(['user_id' => $userId]);
            
            if (!$userCoins->spendCoins($amount, $sourceType, $sourceId, $description)) {
                return [
                    'success' => false,
                    'message' => 'موجودی سکه کافی نیست',
                    'data' => [
                        'required' => $amount,
                        'available' => $userCoins->available_coins,
                    ]
                ];
            }

            DB::commit();

            // Clear cache
            $this->clearUserCache($userId);

            return [
                'success' => true,
                'message' => 'سکه‌ها با موفقیت کسر شد',
                'data' => [
                    'amount' => $amount,
                    'new_balance' => $userCoins->available_coins,
                    'total_coins' => $userCoins->total_coins,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Coin spend failed', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در کسر سکه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's coin balance
     */
    public function getUserBalance(int $userId): array
    {
        $cacheKey = "user_coins_{$userId}";
        
        $balance = Cache::remember($cacheKey, 3600, function() use ($userId) {
            $userCoins = UserCoin::firstOrCreate(['user_id' => $userId]);
            return $userCoins->getBalanceSummary();
        });

        return [
            'success' => true,
            'message' => 'موجودی سکه دریافت شد',
            'data' => $balance
        ];
    }

    /**
     * Get user's coin transactions
     */
    public function getUserTransactions(int $userId, int $limit = 50, int $offset = 0): array
    {
        $transactions = CoinTransaction::where('user_id', $userId)
            ->orderBy('transacted_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'source_type' => $transaction->source_type,
                    'source_id' => $transaction->source_id,
                    'transacted_at' => $transaction->transacted_at,
                    'metadata' => $transaction->metadata,
                ];
            });

        return [
            'success' => true,
            'message' => 'تراکنش‌های سکه دریافت شد',
            'data' => [
                'transactions' => $transactions,
                'total' => CoinTransaction::where('user_id', $userId)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get coin statistics for user
     */
    public function getUserStatistics(int $userId, int $days = 30): array
    {
        $stats = CoinTransaction::getSummaryForUser($userId, $days);
        
        return [
            'success' => true,
            'message' => 'آمار سکه دریافت شد',
            'data' => [
                'period_days' => $days,
                'total_earned' => $stats['total_earned'],
                'total_spent' => $stats['total_spent'],
                'net_gain' => $stats['net_gain'],
                'transaction_count' => $stats['transaction_count'],
                'by_type' => $stats['by_type'],
            ]
        ];
    }

    /**
     * Get global coin statistics
     */
    public function getGlobalStatistics(int $days = 30): array
    {
        $cacheKey = "global_coin_stats_{$days}";
        
        $stats = Cache::remember($cacheKey, 1800, function() use ($days) {
            $transactions = CoinTransaction::recent($days)->get();
            
            return [
                'total_users_with_coins' => UserCoin::withCoins()->count(),
                'total_coins_in_circulation' => UserCoin::sum('available_coins'),
                'total_coins_earned' => $transactions->where('transaction_type', 'earned')->sum('amount'),
                'total_coins_spent' => $transactions->where('transaction_type', 'spent')->sum('amount'),
                'average_coins_per_user' => UserCoin::avg('available_coins'),
                'top_earners' => UserCoin::topEarners(10)->get()->map(function($userCoin) {
                    return [
                        'user_id' => $userCoin->user_id,
                        'user_name' => $userCoin->user->name ?? 'Unknown',
                        'total_coins' => $userCoin->total_coins,
                        'earned_coins' => $userCoin->earned_coins,
                    ];
                }),
                'transactions_by_type' => $transactions->groupBy('transaction_type')->map->count(),
            ];
        });

        return [
            'success' => true,
            'message' => 'آمار کلی سکه دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Award referral coins
     */
    public function awardReferralCoins(int $referrerId, int $referredId): array
    {
        return $this->awardCoins(
            $referrerId,
            10, // 10 coins for referral
            'referral',
            $referredId,
            'پاداش معرفی کاربر جدید'
        );
    }

    /**
     * Award quiz coins
     */
    public function awardQuizCoins(int $userId, int $questionId, bool $isCorrect): array
    {
        if (!$isCorrect) {
            return [
                'success' => false,
                'message' => 'پاسخ نادرست - سکه اعطا نمی‌شود'
            ];
        }

        return $this->awardCoins(
            $userId,
            5, // 5 coins for correct answer
            'quiz',
            $questionId,
            'پاداش پاسخ صحیح به سوال'
        );
    }

    /**
     * Award story completion coins
     */
    public function awardStoryCompletionCoins(int $userId, int $episodeId): array
    {
        return $this->awardCoins(
            $userId,
            2, // 2 coins for story completion
            'episode',
            $episodeId,
            'پاداش تکمیل داستان'
        );
    }

    /**
     * Award daily listening coins
     */
    public function awardDailyListeningCoins(int $userId): array
    {
        return $this->awardCoins(
            $userId,
            1, // 1 coin for daily listening
            'daily_listening',
            null,
            'پاداش گوش دادن روزانه'
        );
    }

    /**
     * Award streak bonus coins
     */
    public function awardStreakBonusCoins(int $userId, int $streakDays): array
    {
        $bonusCoins = min($streakDays * 2, 50); // Max 50 coins for long streaks
        
        return $this->awardCoins(
            $userId,
            $bonusCoins,
            'streak',
            $streakDays,
            "پاداش استریک {$streakDays} روزه"
        );
    }

    /**
     * Clear user cache
     */
    private function clearUserCache(int $userId): void
    {
        Cache::forget("user_coins_{$userId}");
        Cache::forget("user_transactions_{$userId}");
    }

    /**
     * Get coin redemption options
     */
    public function getRedemptionOptions(): array
    {
        return [
            'subscription_extensions' => [
                '1_day' => ['coins' => 20, 'description' => 'تمدید 1 روزه اشتراک'],
                '1_week' => ['coins' => 100, 'description' => 'تمدید 1 هفته اشتراک'],
                '1_month' => ['coins' => 300, 'description' => 'تمدید 1 ماهه اشتراک'],
            ],
            'premium_content' => [
                'premium_story' => ['coins' => 50, 'description' => 'داستان ویژه'],
                'exclusive_episode' => ['coins' => 30, 'description' => 'اپیزود انحصاری'],
                'behind_scenes' => ['coins' => 25, 'description' => 'پشت صحنه'],
            ],
            'special_privileges' => [
                'priority_support' => ['coins' => 100, 'description' => 'پشتیبانی اولویت'],
                'early_access' => ['coins' => 150, 'description' => 'دسترسی زودهنگام'],
                'custom_content' => ['coins' => 1000, 'description' => 'محتوای سفارشی'],
            ],
        ];
    }
}
