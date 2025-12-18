<?php

namespace App\Services;

use App\Models\UserCoin;
use App\Models\CoinTransaction;
use App\Models\EpisodeQuestion;
use App\Models\UserQuizAttempt;
use App\Models\ReferralCode;
use App\Models\Referral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CoinAnalyticsService
{
    public function getCoinSystemOverview(): array
    {
        try {
            $cacheKey = 'coin_system_overview_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                return [
                    'total_users_with_coins' => UserCoin::count(),
                    'total_coins_in_circulation' => UserCoin::sum('available_coins'),
                    'total_coins_earned' => CoinTransaction::where('transaction_type', 'earned')->sum('amount'),
                    'total_coins_spent' => CoinTransaction::where('transaction_type', 'spent')->sum('amount'),
                    'average_coins_per_user' => UserCoin::avg('available_coins'),
                    'total_transactions' => CoinTransaction::count(),
                    'active_users_last_30_days' => $this->getActiveUsersLast30Days(),
                    'coin_velocity' => $this->calculateCoinVelocity(),
                ];
            });
        } catch (\Exception $e) {
            \Log::error("Error getting coin system overview: " . $e->getMessage());
            return [
                'total_users_with_coins' => 0,
                'total_coins_in_circulation' => 0,
                'total_coins_earned' => 0,
                'total_coins_spent' => 0,
                'average_coins_per_user' => 0,
                'total_transactions' => 0,
                'active_users_last_30_days' => 0,
                'coin_velocity' => 0,
            ];
        }
    }

    public function getCoinEarningSources(): array
    {
        try {
            $cacheKey = 'coin_earning_sources_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $sources = CoinTransaction::where('transaction_type', 'earned')
                    ->select('source_type', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as transaction_count'))
                    ->groupBy('source_type')
                    ->get();

                $totalEarned = CoinTransaction::where('transaction_type', 'earned')->sum('amount');

                return $sources->map(function ($source) use ($totalEarned) {
                    return [
                        'source_type' => $source->source_type,
                        'total_amount' => $source->total_amount,
                        'transaction_count' => $source->transaction_count,
                        'percentage' => $totalEarned > 0 ? round(($source->total_amount / $totalEarned) * 100, 2) : 0,
                        'average_per_transaction' => $source->transaction_count > 0 ? round($source->total_amount / $source->transaction_count, 2) : 0,
                    ];
                })->toArray();
            });
        } catch (\Exception $e) {
            \Log::error("Error getting coin earning sources: " . $e->getMessage());
            return [];
        }
    }

    public function getCoinSpendingPatterns(): array
    {
        try {
            $cacheKey = 'coin_spending_patterns_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $patterns = CoinTransaction::where('transaction_type', 'spent')
                    ->select('redemption_option_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as transaction_count'))
                    ->groupBy('redemption_option_id')
                    ->get();

                $totalSpent = CoinTransaction::where('transaction_type', 'spent')->sum('amount');

                return $patterns->map(function ($pattern) use ($totalSpent) {
                    return [
                        'redemption_option_id' => $pattern->redemption_option_id,
                        'total_amount' => $pattern->total_amount,
                        'transaction_count' => $pattern->transaction_count,
                        'percentage' => $totalSpent > 0 ? round(($pattern->total_amount / $totalSpent) * 100, 2) : 0,
                        'average_per_transaction' => $pattern->transaction_count > 0 ? round($pattern->total_amount / $pattern->transaction_count, 2) : 0,
                    ];
                })->toArray();
            });
        } catch (\Exception $e) {
            \Log::error("Error getting coin spending patterns: " . $e->getMessage());
            return [];
        }
    }

    public function getCoinTransactionTrends(int $days = 30): array
    {
        try {
            $cacheKey = "coin_transaction_trends_{$days}_" . date('Y-m-d');
            
            return Cache::remember($cacheKey, 1800, function () use ($days) {
                $startDate = Carbon::now()->subDays($days);
                
                $earnedTrends = CoinTransaction::where('transaction_type', 'earned')
                    ->where('created_at', '>=', $startDate)
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as transaction_count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                $spentTrends = CoinTransaction::where('transaction_type', 'spent')
                    ->where('created_at', '>=', $startDate)
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as transaction_count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                return [
                    'earned_trends' => $earnedTrends->toArray(),
                    'spent_trends' => $spentTrends->toArray(),
                    'net_trends' => $this->calculateNetTrends($earnedTrends, $spentTrends),
                ];
            });
        } catch (\Exception $e) {
            \Log::error("Error getting coin transaction trends: " . $e->getMessage());
            return [
                'earned_trends' => [],
                'spent_trends' => [],
                'net_trends' => [],
            ];
        }
    }

    public function getUserCoinDistribution(): array
    {
        try {
            $cacheKey = 'user_coin_distribution_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $distribution = UserCoin::select(
                    DB::raw('CASE 
                        WHEN available_coins = 0 THEN "0"
                        WHEN available_coins BETWEEN 1 AND 10 THEN "1-10"
                        WHEN available_coins BETWEEN 11 AND 50 THEN "11-50"
                        WHEN available_coins BETWEEN 51 AND 100 THEN "51-100"
                        WHEN available_coins BETWEEN 101 AND 500 THEN "101-500"
                        WHEN available_coins BETWEEN 501 AND 1000 THEN "501-1000"
                        ELSE "1000+"
                    END as coin_range'),
                    DB::raw('COUNT(*) as user_count')
                )
                ->groupBy('coin_range')
                ->orderByRaw('CASE 
                    WHEN coin_range = "0" THEN 1
                    WHEN coin_range = "1-10" THEN 2
                    WHEN coin_range = "11-50" THEN 3
                    WHEN coin_range = "51-100" THEN 4
                    WHEN coin_range = "101-500" THEN 5
                    WHEN coin_range = "501-1000" THEN 6
                    ELSE 7
                END')
                ->get();

                return $distribution->toArray();
            });
        } catch (\Exception $e) {
            \Log::error("Error getting user coin distribution: " . $e->getMessage());
            return [];
        }
    }

    public function getQuizCoinPerformance(): array
    {
        try {
            $cacheKey = 'quiz_coin_performance_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $quizStats = UserQuizAttempt::select(
                    DB::raw('COUNT(*) as total_attempts'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_attempts'),
                    DB::raw('AVG(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy_rate')
                )->first();

                $coinRewards = CoinTransaction::where('source_type', 'quiz_reward')
                    ->select(
                        DB::raw('SUM(amount) as total_coins_awarded'),
                        DB::raw('COUNT(*) as total_rewards')
                    )->first();

                return [
                    'total_quiz_attempts' => $quizStats->total_attempts ?? 0,
                    'correct_attempts' => $quizStats->correct_attempts ?? 0,
                    'accuracy_rate' => round($quizStats->accuracy_rate ?? 0, 2),
                    'total_coins_awarded' => $coinRewards->total_coins_awarded ?? 0,
                    'total_rewards_given' => $coinRewards->total_rewards ?? 0,
                    'average_coins_per_correct_answer' => $quizStats->correct_attempts > 0 ? 
                        round(($coinRewards->total_coins_awarded ?? 0) / $quizStats->correct_attempts, 2) : 0,
                ];
            });
        } catch (\Exception $e) {
            \Log::error("Error getting quiz coin performance: " . $e->getMessage());
            return [
                'total_quiz_attempts' => 0,
                'correct_attempts' => 0,
                'accuracy_rate' => 0,
                'total_coins_awarded' => 0,
                'total_rewards_given' => 0,
                'average_coins_per_correct_answer' => 0,
            ];
        }
    }

    public function getReferralCoinPerformance(): array
    {
        try {
            $cacheKey = 'referral_coin_performance_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $referralStats = Referral::select(
                    DB::raw('COUNT(*) as total_referrals'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_referrals')
                )->first();

                $coinRewards = CoinTransaction::where('source_type', 'referral')
                    ->select(
                        DB::raw('SUM(amount) as total_coins_awarded'),
                        DB::raw('COUNT(*) as total_rewards')
                    )->first();

                return [
                    'total_referrals' => $referralStats->total_referrals ?? 0,
                    'completed_referrals' => $referralStats->completed_referrals ?? 0,
                    'completion_rate' => $referralStats->total_referrals > 0 ? 
                        round(($referralStats->completed_referrals / $referralStats->total_referrals) * 100, 2) : 0,
                    'total_coins_awarded' => $coinRewards->total_coins_awarded ?? 0,
                    'total_rewards_given' => $coinRewards->total_rewards ?? 0,
                    'average_coins_per_referral' => $referralStats->completed_referrals > 0 ? 
                        round(($coinRewards->total_coins_awarded ?? 0) / $referralStats->completed_referrals, 2) : 0,
                ];
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral coin performance: " . $e->getMessage());
            return [
                'total_referrals' => 0,
                'completed_referrals' => 0,
                'completion_rate' => 0,
                'total_coins_awarded' => 0,
                'total_rewards_given' => 0,
                'average_coins_per_referral' => 0,
            ];
        }
    }

    public function getTopCoinEarners(int $limit = 10): array
    {
        try {
            $cacheKey = "top_coin_earners_{$limit}_" . date('Y-m-d');
            
            return Cache::remember($cacheKey, 1800, function () use ($limit) {
                return UserCoin::with('user')
                    ->orderBy('earned_coins', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($userCoin) {
                        return [
                            'user_id' => $userCoin->user_id,
                            'user_name' => $userCoin->user->name ?? 'Unknown',
                            'user_email' => $userCoin->user->email ?? '',
                            'earned_coins' => $userCoin->earned_coins,
                            'available_coins' => $userCoin->available_coins,
                            'spent_coins' => $userCoin->spent_coins,
                        ];
                    })
                    ->toArray();
            });
        } catch (\Exception $e) {
            \Log::error("Error getting top coin earners: " . $e->getMessage());
            return [];
        }
    }

    public function getCoinSystemHealth(): array
    {
        try {
            $cacheKey = 'coin_system_health_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $totalCoinsEarned = CoinTransaction::where('transaction_type', 'earned')->sum('amount');
                $totalCoinsSpent = CoinTransaction::where('transaction_type', 'spent')->sum('amount');
                $totalCoinsInCirculation = UserCoin::sum('available_coins');
                
                $coinVelocity = $this->calculateCoinVelocity();
                $userEngagement = $this->getActiveUsersLast30Days();
                $totalUsers = UserCoin::count();
                
                return [
                    'coin_velocity' => $coinVelocity,
                    'user_engagement_rate' => $totalUsers > 0 ? round(($userEngagement / $totalUsers) * 100, 2) : 0,
                    'coin_circulation_ratio' => $totalCoinsEarned > 0 ? round(($totalCoinsInCirculation / $totalCoinsEarned) * 100, 2) : 0,
                    'spending_ratio' => $totalCoinsEarned > 0 ? round(($totalCoinsSpent / $totalCoinsEarned) * 100, 2) : 0,
                    'system_health_score' => $this->calculateSystemHealthScore($coinVelocity, $userEngagement, $totalUsers),
                ];
            });
        } catch (\Exception $e) {
            \Log::error("Error getting coin system health: " . $e->getMessage());
            return [
                'coin_velocity' => 0,
                'user_engagement_rate' => 0,
                'coin_circulation_ratio' => 0,
                'spending_ratio' => 0,
                'system_health_score' => 0,
            ];
        }
    }

    private function getActiveUsersLast30Days(): int
    {
        $startDate = Carbon::now()->subDays(30);
        return CoinTransaction::where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');
    }

    private function calculateCoinVelocity(): float
    {
        $totalCoinsEarned = CoinTransaction::where('transaction_type', 'earned')->sum('amount');
        $totalCoinsSpent = CoinTransaction::where('transaction_type', 'spent')->sum('amount');
        
        if ($totalCoinsEarned == 0) {
            return 0;
        }
        
        return round(($totalCoinsSpent / $totalCoinsEarned) * 100, 2);
    }

    private function calculateNetTrends($earnedTrends, $spentTrends): array
    {
        $netTrends = [];
        $earnedByDate = $earnedTrends->keyBy('date');
        $spentByDate = $spentTrends->keyBy('date');
        
        $allDates = collect($earnedByDate->keys())->merge($spentByDate->keys())->unique()->sort();
        
        foreach ($allDates as $date) {
            $earned = $earnedByDate->get($date, ['total_amount' => 0])['total_amount'];
            $spent = $spentByDate->get($date, ['total_amount' => 0])['total_amount'];
            
            $netTrends[] = [
                'date' => $date,
                'net_amount' => $earned - $spent,
                'earned_amount' => $earned,
                'spent_amount' => $spent,
            ];
        }
        
        return $netTrends;
    }

    private function calculateSystemHealthScore(float $coinVelocity, int $activeUsers, int $totalUsers): int
    {
        $velocityScore = min(100, max(0, $coinVelocity * 2)); // 0-100 based on velocity
        $engagementScore = $totalUsers > 0 ? min(100, ($activeUsers / $totalUsers) * 100) : 0;
        
        return round(($velocityScore + $engagementScore) / 2);
    }
}
