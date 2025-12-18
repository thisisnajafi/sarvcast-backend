<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserAnalyticsService
{
    /**
     * Get comprehensive user analytics
     */
    public function getUserAnalytics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();
        $userType = $filters['user_type'] ?? null; // parent, child, admin
        $status = $filters['status'] ?? null; // active, inactive, suspended

        return [
            'overview' => $this->getOverviewMetrics($dateFrom, $dateTo, $userType, $status),
            'registration' => $this->getRegistrationMetrics($dateFrom, $dateTo),
            'engagement' => $this->getEngagementMetrics($dateFrom, $dateTo),
            'subscription' => $this->getSubscriptionMetrics($dateFrom, $dateTo),
            'activity' => $this->getActivityMetrics($dateFrom, $dateTo),
            'retention' => $this->getRetentionMetrics($dateFrom, $dateTo),
            'demographics' => $this->getDemographicsMetrics(),
            'behavior' => $this->getBehaviorMetrics($dateFrom, $dateTo),
            'trends' => $this->getTrendMetrics($dateFrom, $dateTo),
            'segments' => $this->getUserSegments($dateFrom, $dateTo)
        ];
    }

    /**
     * Get overview metrics
     */
    public function getOverviewMetrics($dateFrom, $dateTo, $userType = null, $status = null): array
    {
        $query = User::query();

        if ($userType) {
            $query->where('role', $userType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $totalUsers = $query->count();
        $activeUsers = $query->where('status', 'active')->count();
        $newUsers = $query->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $suspendedUsers = $query->where('status', 'suspended')->count();

        // Users with subscriptions
        $usersWithSubscriptions = $query->whereHas('subscriptions', function($q) {
            $q->where('status', 'active')->where('end_date', '>', now());
        })->count();

        // Users with payments
        $usersWithPayments = $query->whereHas('payments', function($q) {
            $q->where('status', 'completed');
        })->count();

        // Average session duration
        $avgSessionDuration = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_duration')
            ->value('avg_duration') ?? 0;

        // Average play time per user
        $avgPlayTimePerUser = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('AVG(duration) as avg_duration')
            ->value('avg_duration') ?? 0;

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'new_users' => $newUsers,
            'suspended_users' => $suspendedUsers,
            'users_with_subscriptions' => $usersWithSubscriptions,
            'users_with_payments' => $usersWithPayments,
            'subscription_rate' => $totalUsers > 0 ? round(($usersWithSubscriptions / $totalUsers) * 100, 2) : 0,
            'payment_rate' => $totalUsers > 0 ? round(($usersWithPayments / $totalUsers) * 100, 2) : 0,
            'avg_session_duration' => round($avgSessionDuration, 2),
            'avg_play_time_per_user' => round($avgPlayTimePerUser, 2)
        ];
    }

    /**
     * Get registration metrics
     */
    public function getRegistrationMetrics($dateFrom, $dateTo): array
    {
        $registrations = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRegistrations = $registrations->sum('count');
        $avgDailyRegistrations = $totalRegistrations > 0 ? round($totalRegistrations / $dateFrom->diffInDays($dateTo), 2) : 0;

        // Registration by source (if you have source tracking)
        $registrationsBySource = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COALESCE(registration_source, "unknown") as source, COUNT(*) as count')
            ->groupBy('source')
            ->get();

        // Registration by role
        $registrationsByRole = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get();

        // Registration by hour of day
        $registrationsByHour = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'total_registrations' => $totalRegistrations,
            'avg_daily_registrations' => $avgDailyRegistrations,
            'registrations_timeline' => $registrations,
            'registrations_by_source' => $registrationsBySource,
            'registrations_by_role' => $registrationsByRole,
            'registrations_by_hour' => $registrationsByHour
        ];
    }

    /**
     * Get engagement metrics
     */
    public function getEngagementMetrics($dateFrom, $dateTo): array
    {
        // Daily active users
        $dailyActiveUsers = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Weekly active users
        $weeklyActiveUsers = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('YEARWEEK(created_at) as week, COUNT(DISTINCT user_id) as count')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Monthly active users
        $monthlyActiveUsers = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(DISTINCT user_id) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Engagement rate (users who played content vs total users)
        $totalUsers = User::count();
        $engagedUsers = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->distinct('user_id')
            ->count();
        $engagementRate = $totalUsers > 0 ? round(($engagedUsers / $totalUsers) * 100, 2) : 0;

        // Average sessions per user
        $avgSessionsPerUser = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) / COUNT(DISTINCT user_id) as avg_sessions')
            ->value('avg_sessions') ?? 0;

        // Average play time per session
        $avgPlayTimePerSession = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('AVG(duration) as avg_duration')
            ->value('avg_duration') ?? 0;

        return [
            'daily_active_users' => $dailyActiveUsers,
            'weekly_active_users' => $weeklyActiveUsers,
            'monthly_active_users' => $monthlyActiveUsers,
            'engagement_rate' => $engagementRate,
            'avg_sessions_per_user' => round($avgSessionsPerUser, 2),
            'avg_play_time_per_session' => round($avgPlayTimePerSession, 2),
            'total_engaged_users' => $engagedUsers
        ];
    }

    /**
     * Get subscription metrics
     */
    public function getSubscriptionMetrics($dateFrom, $dateTo): array
    {
        // New subscriptions
        $newSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Subscription types
        $subscriptionsByType = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        // Active subscriptions
        $activeSubscriptions = Subscription::where('status', 'active')
            ->where('end_date', '>', now())
            ->count();

        // Subscription churn rate
        $totalSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $cancelledSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'cancelled')
            ->count();
        $churnRate = $totalSubscriptions > 0 ? round(($cancelledSubscriptions / $totalSubscriptions) * 100, 2) : 0;

        // Average subscription duration
        $avgSubscriptionDuration = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('end_date')
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            ->value('avg_duration') ?? 0;

        return [
            'new_subscriptions' => $newSubscriptions,
            'subscriptions_by_type' => $subscriptionsByType,
            'active_subscriptions' => $activeSubscriptions,
            'churn_rate' => $churnRate,
            'avg_subscription_duration' => round($avgSubscriptionDuration, 2),
            'total_new_subscriptions' => $newSubscriptions->sum('count'),
            'cancelled_subscriptions' => $cancelledSubscriptions
        ];
    }

    /**
     * Get activity metrics
     */
    public function getActivityMetrics($dateFrom, $dateTo): array
    {
        // Play history trends
        $playHistoryTrends = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(duration) as total_duration')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Favorites trends
        $favoritesTrends = Favorite::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ratings trends
        $ratingsTrends = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(rating) as avg_rating')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Most active users
        $mostActiveUsers = User::withSum('playHistories', 'duration')
            ->withCount('playHistories')
            ->orderBy('play_histories_sum_duration', 'desc')
            ->limit(10)
            ->get();

        // Most favorited content
        $mostFavoritedContent = Favorite::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('story_id, COUNT(*) as count')
            ->groupBy('story_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->with('story')
            ->get();

        return [
            'play_history_trends' => $playHistoryTrends,
            'favorites_trends' => $favoritesTrends,
            'ratings_trends' => $ratingsTrends,
            'most_active_users' => $mostActiveUsers,
            'most_favorited_content' => $mostFavoritedContent,
            'total_play_sessions' => $playHistoryTrends->sum('count'),
            'total_favorites' => $favoritesTrends->sum('count'),
            'total_ratings' => $ratingsTrends->sum('count')
        ];
    }

    /**
     * Get retention metrics
     */
    public function getRetentionMetrics($dateFrom, $dateTo): array
    {
        // User retention by cohort
        $cohorts = [];
        $startDate = $dateFrom->copy();
        
        while ($startDate->lte($dateTo)) {
            $cohortDate = $startDate->copy();
            $cohortUsers = User::whereDate('created_at', $cohortDate)->pluck('id');
            
            if ($cohortUsers->count() > 0) {
                $retention = [];
                
                // Check retention for 7, 14, 30 days
                foreach ([7, 14, 30] as $days) {
                    $retainedUsers = PlayHistory::whereIn('user_id', $cohortUsers)
                        ->whereBetween('created_at', [
                            $cohortDate->copy()->addDays($days),
                            $cohortDate->copy()->addDays($days + 1)
                        ])
                        ->distinct('user_id')
                        ->count();
                    
                    $retention[$days] = round(($retainedUsers / $cohortUsers->count()) * 100, 2);
                }
                
                $cohorts[] = [
                    'date' => $cohortDate->format('Y-m-d'),
                    'users' => $cohortUsers->count(),
                    'retention' => $retention
                ];
            }
            
            $startDate->addDay();
        }

        return [
            'cohort_retention' => $cohorts,
            'avg_7_day_retention' => collect($cohorts)->avg('retention.7') ?? 0,
            'avg_14_day_retention' => collect($cohorts)->avg('retention.14') ?? 0,
            'avg_30_day_retention' => collect($cohorts)->avg('retention.30') ?? 0
        ];
    }

    /**
     * Get demographics metrics
     */
    public function getDemographicsMetrics(): array
    {
        // Users by role
        $usersByRole = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get();

        // Users by status
        $usersByStatus = User::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Users by language
        $usersByLanguage = User::selectRaw('COALESCE(language, "unknown") as language, COUNT(*) as count')
            ->groupBy('language')
            ->get();

        // Users by timezone
        $usersByTimezone = User::selectRaw('COALESCE(timezone, "unknown") as timezone, COUNT(*) as count')
            ->groupBy('timezone')
            ->get();

        // Age distribution (if you have age data)
        $ageDistribution = UserProfile::whereNotNull('age')
            ->selectRaw('
                CASE 
                    WHEN age < 18 THEN "under_18"
                    WHEN age BETWEEN 18 AND 24 THEN "18_24"
                    WHEN age BETWEEN 25 AND 34 THEN "25_34"
                    WHEN age BETWEEN 35 AND 44 THEN "35_44"
                    WHEN age BETWEEN 45 AND 54 THEN "45_54"
                    WHEN age >= 55 THEN "55_plus"
                    ELSE "unknown"
                END as age_group,
                COUNT(*) as count
            ')
            ->groupBy('age_group')
            ->get();

        return [
            'users_by_role' => $usersByRole,
            'users_by_status' => $usersByStatus,
            'users_by_language' => $usersByLanguage,
            'users_by_timezone' => $usersByTimezone,
            'age_distribution' => $ageDistribution
        ];
    }

    /**
     * Get behavior metrics
     */
    public function getBehaviorMetrics($dateFrom, $dateTo): array
    {
        // Peak usage hours
        $peakUsageHours = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();

        // Peak usage days
        $peakUsageDays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('count', 'desc')
            ->get();

        // Average session length
        $avgSessionLength = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('AVG(duration) as avg_duration')
            ->value('avg_duration') ?? 0;

        // Content completion rate
        $totalPlays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $completedPlays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('progress', '>=', 90) // 90% completion
            ->count();
        $completionRate = $totalPlays > 0 ? round(($completedPlays / $totalPlays) * 100, 2) : 0;

        // User journey analysis
        $userJourney = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('user_id, COUNT(*) as sessions, SUM(duration) as total_duration')
            ->groupBy('user_id')
            ->selectRaw('
                CASE 
                    WHEN sessions = 1 THEN "single_session"
                    WHEN sessions BETWEEN 2 AND 5 THEN "light_user"
                    WHEN sessions BETWEEN 6 AND 20 THEN "regular_user"
                    WHEN sessions > 20 THEN "heavy_user"
                END as user_type,
                COUNT(*) as count
            ')
            ->groupBy('user_type')
            ->get();

        return [
            'peak_usage_hours' => $peakUsageHours,
            'peak_usage_days' => $peakUsageDays,
            'avg_session_length' => round($avgSessionLength, 2),
            'completion_rate' => $completionRate,
            'user_journey' => $userJourney,
            'total_sessions' => $totalPlays,
            'completed_sessions' => $completedPlays
        ];
    }

    /**
     * Get trend metrics
     */
    public function getTrendMetrics($dateFrom, $dateTo): array
    {
        // User growth trend
        $userGrowthTrend = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Engagement trend
        $engagementTrend = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue trend
        $revenueTrend = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate growth rates
        $userGrowthRate = $this->calculateGrowthRate($userGrowthTrend);
        $engagementGrowthRate = $this->calculateGrowthRate($engagementTrend);
        $revenueGrowthRate = $this->calculateGrowthRate($revenueTrend);

        return [
            'user_growth_trend' => $userGrowthTrend,
            'engagement_trend' => $engagementTrend,
            'revenue_trend' => $revenueTrend,
            'user_growth_rate' => $userGrowthRate,
            'engagement_growth_rate' => $engagementGrowthRate,
            'revenue_growth_rate' => $revenueGrowthRate
        ];
    }

    /**
     * Get user segments
     */
    public function getUserSegments($dateFrom, $dateTo): array
    {
        // High-value users (users with high spending)
        $highValueUsers = User::withSum(['payments as total_spent' => function($q) {
            $q->where('status', 'completed');
        }], 'amount')
        ->having('total_spent', '>', 100000) // 1000 Toman
        ->count();

        // Active users (users with high engagement)
        $activeUsers = User::withCount('playHistories')
            ->having('play_histories_count', '>', 10)
            ->count();

        // New users (registered in the last 30 days)
        $newUsers = User::where('created_at', '>=', now()->subDays(30))->count();

        // Churned users (users who haven't been active in the last 30 days)
        $churnedUsers = User::whereDoesntHave('playHistories', function($q) {
            $q->where('created_at', '>=', now()->subDays(30));
        })->count();

        // Premium users (users with active subscriptions)
        $premiumUsers = User::whereHas('subscriptions', function($q) {
            $q->where('status', 'active')->where('end_date', '>', now());
        })->count();

        return [
            'high_value_users' => $highValueUsers,
            'active_users' => $activeUsers,
            'new_users' => $newUsers,
            'churned_users' => $churnedUsers,
            'premium_users' => $premiumUsers,
            'free_users' => User::count() - $premiumUsers
        ];
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($data): float
    {
        if ($data->count() < 2) {
            return 0;
        }

        $firstValue = $data->first()->count ?? $data->first()->total ?? 0;
        $lastValue = $data->last()->count ?? $data->last()->total ?? 0;

        if ($firstValue == 0) {
            return $lastValue > 0 ? 100 : 0;
        }

        return round((($lastValue - $firstValue) / $firstValue) * 100, 2);
    }

    /**
     * Get real-time user metrics
     */
    public function getRealTimeMetrics(): array
    {
        $now = now();
        $lastHour = $now->copy()->subHour();
        $last24Hours = $now->copy()->subDay();

        return [
            'users_online_last_hour' => PlayHistory::where('created_at', '>=', $lastHour)
                ->distinct('user_id')
                ->count(),
            'users_online_last_24h' => PlayHistory::where('created_at', '>=', $last24Hours)
                ->distinct('user_id')
                ->count(),
            'sessions_last_hour' => PlayHistory::where('created_at', '>=', $lastHour)->count(),
            'sessions_last_24h' => PlayHistory::where('created_at', '>=', $last24Hours)->count(),
            'new_registrations_last_hour' => User::where('created_at', '>=', $lastHour)->count(),
            'new_registrations_last_24h' => User::where('created_at', '>=', $last24Hours)->count()
        ];
    }
}
