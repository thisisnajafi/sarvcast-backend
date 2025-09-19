<?php

namespace App\Services;

use App\Models\ReferralCode;
use App\Models\Referral;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReferralAnalyticsService
{
    public function getReferralSystemOverview(): array
    {
        try {
            $cacheKey = 'referral_system_overview_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                return [
                    'total_referral_codes' => ReferralCode::count(),
                    'active_referral_codes' => ReferralCode::where('is_active', true)->count(),
                    'total_referrals' => Referral::count(),
                    'completed_referrals' => Referral::where('status', 'completed')->count(),
                    'pending_referrals' => Referral::where('status', 'pending')->count(),
                    'total_referral_revenue' => $this->calculateTotalReferralRevenue(),
                    'average_referrals_per_code' => $this->calculateAverageReferralsPerCode(),
                    'conversion_rate' => $this->calculateConversionRate(),
                    'top_referrer_count' => $this->getTopReferrerCount(),
                ];
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral system overview: " . $e->getMessage());
            return [
                'total_referral_codes' => 0,
                'active_referral_codes' => 0,
                'total_referrals' => 0,
                'completed_referrals' => 0,
                'pending_referrals' => 0,
                'total_referral_revenue' => 0,
                'average_referrals_per_code' => 0,
                'conversion_rate' => 0,
                'top_referrer_count' => 0,
            ];
        }
    }

    public function getReferralTrends(int $days = 30): array
    {
        try {
            $cacheKey = "referral_trends_{$days}_" . date('Y-m-d');
            
            return Cache::remember($cacheKey, 1800, function () use ($days) {
                $startDate = Carbon::now()->subDays($days);
                
                $referralTrends = Referral::where('created_at', '>=', $startDate)
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as referral_count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                $completionTrends = Referral::where('status', 'completed')
                    ->where('created_at', '>=', $startDate)
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as completion_count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                return [
                    'referral_trends' => $referralTrends->toArray(),
                    'completion_trends' => $completionTrends->toArray(),
                    'conversion_trends' => $this->calculateConversionTrends($referralTrends, $completionTrends),
                ];
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral trends: " . $e->getMessage());
            return [
                'referral_trends' => [],
                'completion_trends' => [],
                'conversion_trends' => [],
            ];
        }
    }

    public function getTopReferrers(int $limit = 10): array
    {
        try {
            $cacheKey = "top_referrers_{$limit}_" . date('Y-m-d');
            
            return Cache::remember($cacheKey, 1800, function () use ($limit) {
                return ReferralCode::with(['user', 'referrals'])
                    ->select('user_id', DB::raw('COUNT(referrals.id) as total_referrals'))
                    ->leftJoin('referrals', 'referral_codes.id', '=', 'referrals.referral_code_id')
                    ->groupBy('referral_codes.user_id')
                    ->orderBy('total_referrals', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($referralCode) {
                        $completedReferrals = $referralCode->referrals->where('status', 'completed')->count();
                        $totalReferrals = $referralCode->referrals->count();
                        
                        return [
                            'user_id' => $referralCode->user_id,
                            'user_name' => $referralCode->user->name ?? 'Unknown',
                            'user_email' => $referralCode->user->email ?? '',
                            'referral_code' => $referralCode->code,
                            'total_referrals' => $totalReferrals,
                            'completed_referrals' => $completedReferrals,
                            'conversion_rate' => $totalReferrals > 0 ? round(($completedReferrals / $totalReferrals) * 100, 2) : 0,
                            'total_coins_earned' => $this->getCoinsEarnedByReferrer($referralCode->user_id),
                        ];
                    })
                    ->toArray();
            });
        } catch (\Exception $e) {
            \Log::error("Error getting top referrers: " . $e->getMessage());
            return [];
        }
    }

    public function getReferralSources(): array
    {
        try {
            $cacheKey = 'referral_sources_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $sources = Referral::select('source', DB::raw('COUNT(*) as referral_count'))
                    ->groupBy('source')
                    ->get();

                $totalReferrals = Referral::count();

                return $sources->map(function ($source) use ($totalReferrals) {
                    return [
                        'source' => $source->source,
                        'referral_count' => $source->referral_count,
                        'percentage' => $totalReferrals > 0 ? round(($source->referral_count / $totalReferrals) * 100, 2) : 0,
                    ];
                })->toArray();
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral sources: " . $e->getMessage());
            return [];
        }
    }

    public function getReferralPerformanceByTimeframe(): array
    {
        try {
            $cacheKey = 'referral_performance_timeframe_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $timeframes = [
                    'today' => Carbon::today(),
                    'this_week' => Carbon::now()->startOfWeek(),
                    'this_month' => Carbon::now()->startOfMonth(),
                    'last_30_days' => Carbon::now()->subDays(30),
                ];

                $performance = [];

                foreach ($timeframes as $period => $startDate) {
                    $referrals = Referral::where('created_at', '>=', $startDate)->count();
                    $completed = Referral::where('status', 'completed')
                        ->where('created_at', '>=', $startDate)
                        ->count();
                    
                    $performance[$period] = [
                        'referrals' => $referrals,
                        'completed' => $completed,
                        'conversion_rate' => $referrals > 0 ? round(($completed / $referrals) * 100, 2) : 0,
                    ];
                }

                return $performance;
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral performance by timeframe: " . $e->getMessage());
            return [];
        }
    }

    public function getReferralFunnelAnalysis(): array
    {
        try {
            $cacheKey = 'referral_funnel_analysis_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $funnel = [
                    'referral_codes_created' => ReferralCode::count(),
                    'referral_codes_used' => ReferralCode::whereHas('referrals')->count(),
                    'referrals_initiated' => Referral::count(),
                    'referrals_completed' => Referral::where('status', 'completed')->count(),
                    'referrals_converted_to_paid' => $this->getPaidConversions(),
                ];

                // Calculate conversion rates
                $funnel['code_usage_rate'] = $funnel['referral_codes_created'] > 0 ? 
                    round(($funnel['referral_codes_used'] / $funnel['referral_codes_created']) * 100, 2) : 0;
                
                $funnel['completion_rate'] = $funnel['referrals_initiated'] > 0 ? 
                    round(($funnel['referrals_completed'] / $funnel['referrals_initiated']) * 100, 2) : 0;
                
                $funnel['paid_conversion_rate'] = $funnel['referrals_completed'] > 0 ? 
                    round(($funnel['referrals_converted_to_paid'] / $funnel['referrals_completed']) * 100, 2) : 0;

                return $funnel;
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral funnel analysis: " . $e->getMessage());
            return [];
        }
    }

    public function getReferralGeographicDistribution(): array
    {
        try {
            $cacheKey = 'referral_geographic_distribution_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                return Referral::join('users', 'referrals.referred_user_id', '=', 'users.id')
                    ->select('users.country', DB::raw('COUNT(*) as referral_count'))
                    ->whereNotNull('users.country')
                    ->groupBy('users.country')
                    ->orderBy('referral_count', 'desc')
                    ->get()
                    ->toArray();
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral geographic distribution: " . $e->getMessage());
            return [];
        }
    }

    public function getReferralRevenueAnalysis(): array
    {
        try {
            $cacheKey = 'referral_revenue_analysis_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $revenue = [
                    'total_referral_revenue' => $this->calculateTotalReferralRevenue(),
                    'average_revenue_per_referral' => $this->calculateAverageRevenuePerReferral(),
                    'revenue_by_status' => $this->getRevenueByStatus(),
                    'monthly_revenue_trend' => $this->getMonthlyRevenueTrend(),
                    'top_revenue_generating_codes' => $this->getTopRevenueGeneratingCodes(),
                ];

                return $revenue;
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral revenue analysis: " . $e->getMessage());
            return [];
        }
    }

    public function getReferralSystemHealth(): array
    {
        try {
            $cacheKey = 'referral_system_health_' . date('Y-m-d');
            
            return Cache::remember($cacheKey, 3600, function () {
                $health = [
                    'active_referral_codes' => ReferralCode::where('is_active', true)->count(),
                    'referral_velocity' => $this->calculateReferralVelocity(),
                    'conversion_rate' => $this->calculateConversionRate(),
                    'revenue_per_referral' => $this->calculateAverageRevenuePerReferral(),
                    'system_health_score' => $this->calculateSystemHealthScore(),
                ];

                return $health;
            });
        } catch (\Exception $e) {
            \Log::error("Error getting referral system health: " . $e->getMessage());
            return [];
        }
    }

    private function calculateTotalReferralRevenue(): float
    {
        // This would need to be implemented based on your subscription/payment system
        // For now, returning a placeholder calculation
        return Referral::where('status', 'completed')->count() * 10; // Assuming 10 coins per completed referral
    }

    private function calculateAverageReferralsPerCode(): float
    {
        $totalCodes = ReferralCode::count();
        $totalReferrals = Referral::count();
        
        return $totalCodes > 0 ? round($totalReferrals / $totalCodes, 2) : 0;
    }

    private function calculateConversionRate(): float
    {
        $totalReferrals = Referral::count();
        $completedReferrals = Referral::where('status', 'completed')->count();
        
        return $totalReferrals > 0 ? round(($completedReferrals / $totalReferrals) * 100, 2) : 0;
    }

    private function getTopReferrerCount(): int
    {
        return ReferralCode::whereHas('referrals')
            ->withCount('referrals')
            ->orderBy('referrals_count', 'desc')
            ->limit(1)
            ->value('referrals_count') ?? 0;
    }

    private function calculateConversionTrends($referralTrends, $completionTrends): array
    {
        $conversionTrends = [];
        $referralByDate = $referralTrends->keyBy('date');
        $completionByDate = $completionTrends->keyBy('date');
        
        $allDates = collect($referralByDate->keys())->merge($completionByDate->keys())->unique()->sort();
        
        foreach ($allDates as $date) {
            $referrals = $referralByDate->get($date, ['referral_count' => 0])['referral_count'];
            $completions = $completionByDate->get($date, ['completion_count' => 0])['completion_count'];
            
            $conversionTrends[] = [
                'date' => $date,
                'conversion_rate' => $referrals > 0 ? round(($completions / $referrals) * 100, 2) : 0,
                'referrals' => $referrals,
                'completions' => $completions,
            ];
        }
        
        return $conversionTrends;
    }

    private function getCoinsEarnedByReferrer(int $userId): int
    {
        return CoinTransaction::where('user_id', $userId)
            ->where('source_type', 'referral')
            ->sum('amount');
    }

    private function getPaidConversions(): int
    {
        // This would need to be implemented based on your subscription system
        // For now, returning a placeholder
        return Referral::where('status', 'completed')->count();
    }

    private function calculateAverageRevenuePerReferral(): float
    {
        $totalRevenue = $this->calculateTotalReferralRevenue();
        $completedReferrals = Referral::where('status', 'completed')->count();
        
        return $completedReferrals > 0 ? round($totalRevenue / $completedReferrals, 2) : 0;
    }

    private function getRevenueByStatus(): array
    {
        return [
            'pending' => Referral::where('status', 'pending')->count() * 5, // Placeholder
            'completed' => Referral::where('status', 'completed')->count() * 10, // Placeholder
        ];
    }

    private function getMonthlyRevenueTrend(): array
    {
        return Referral::where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'revenue' => $item->count * 10, // Placeholder calculation
                ];
            })
            ->toArray();
    }

    private function getTopRevenueGeneratingCodes(): array
    {
        return ReferralCode::withCount(['referrals' => function ($query) {
            $query->where('status', 'completed');
        }])
        ->orderBy('referrals_count', 'desc')
        ->limit(5)
        ->get()
        ->map(function ($code) {
            return [
                'code' => $code->code,
                'user_name' => $code->user->name ?? 'Unknown',
                'completed_referrals' => $code->referrals_count,
                'estimated_revenue' => $code->referrals_count * 10, // Placeholder
            ];
        })
        ->toArray();
    }

    private function calculateReferralVelocity(): float
    {
        $last30Days = Referral::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $previous30Days = Referral::whereBetween('created_at', [
            Carbon::now()->subDays(60),
            Carbon::now()->subDays(30)
        ])->count();
        
        return $previous30Days > 0 ? round((($last30Days - $previous30Days) / $previous30Days) * 100, 2) : 0;
    }

    private function calculateSystemHealthScore(): int
    {
        $conversionRate = $this->calculateConversionRate();
        $velocity = $this->calculateReferralVelocity();
        $activeCodes = ReferralCode::where('is_active', true)->count();
        
        $score = ($conversionRate * 0.4) + (min(100, max(0, $velocity + 50)) * 0.3) + (min(100, $activeCodes * 2) * 0.3);
        
        return round($score);
    }
}
