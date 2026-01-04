<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Commission;
use App\Models\CommissionPayment;
use App\Models\StoryComment;
use App\Models\UserComment;
use App\Models\Rating;
use App\Models\Favorite;
use App\Models\PlayHistory;
use App\Models\AffiliatePartner;
use App\Models\CorporateSponsorship;
use App\Models\SchoolPartnership;
use App\Models\CouponCode;
use App\Models\CouponUsage;
use App\Models\Notification;
use App\Models\SmsLog;
use App\Models\UserActivity;
use App\Models\Report;
use App\Models\Referral;
use App\Models\SubscriptionPlan;
use App\Models\ContentShare;
use App\Models\InfluencerCampaign;
use App\Models\SponsoredContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get date range from request
            $dateRange = $request->get('date_range', '30days');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            // Calculate date range
            $startDate = $this->getStartDate($dateRange, $dateFrom);
            $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now();

        // Generate cache key based on parameters
        $cacheKey = 'dashboard_' . md5(json_encode([
            'date_range' => $dateRange,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'compare' => $request->get('compare', false),
            'user_id' => auth()->id()
        ]));

        // Cache duration: 5 minutes for dashboard data
        $cacheDuration = 300;

        // Try to get cached data
        $cachedData = Cache::get($cacheKey);

        if ($cachedData && !$request->has('refresh')) {
            // Return cached data
            return view('admin.dashboard', $cachedData);
        }

        // Comparison period (if enabled)
        $compareMode = $request->get('compare', false);
        $comparePeriod = $request->get('compare_period', 'previous');
        $compareDateFrom = $request->get('compare_date_from');
        $compareDateTo = $request->get('compare_date_to');

        $comparisonData = null;
        if ($compareMode) {
            $comparisonData = $this->getComparisonData($startDate, $endDate, $comparePeriod, $compareDateFrom, $compareDateTo);
        }

        // Get alerts and notifications (not cached - always fresh)
        $alerts = $this->getDashboardAlerts();

        // Get system health status (not cached - always fresh)
        $systemHealth = $this->getSystemHealth();

        // Get recent activity feed (cached separately)
        $recentActivity = Cache::remember(
            'dashboard_recent_activity_' . md5($startDate . $endDate),
            60, // 1 minute cache
            function() use ($startDate, $endDate) {
                return $this->getRecentActivity($startDate, $endDate);
            }
        );

        // Basic Statistics (cached)
        $stats = Cache::remember(
            'dashboard_stats_' . md5($startDate . $endDate),
            $cacheDuration,
            function() use ($startDate, $endDate) {
                return $this->getBasicStatistics($startDate, $endDate);
            }
        );

        // Get chart data (cached separately)
        $monthlyRevenue = Cache::remember(
            'dashboard_monthly_revenue_' . md5($startDate . $endDate),
            $cacheDuration,
            function() use ($startDate, $endDate) {
                return $this->getMonthlyRevenue($startDate, $endDate);
            }
        );

        $dailyUserRegistrations = Cache::remember(
            'dashboard_daily_users_' . md5($startDate . $endDate),
            $cacheDuration,
            function() use ($startDate, $endDate) {
                return $this->getDailyUserRegistrations($startDate, $endDate);
            }
        );

        $dailyPlayHistory = Cache::remember(
            'dashboard_daily_plays_' . md5($startDate . $endDate),
            $cacheDuration,
            function() use ($startDate, $endDate) {
                return $this->getDailyPlayHistory($startDate, $endDate);
            }
        );

        // Recent Activity (with eager loading to prevent N+1)
        $recentStories = Story::with(['category', 'director', 'narrator'])
            ->latest()
            ->limit(5)
            ->get();

        $recentUsers = User::latest()
            ->limit(5)
            ->get();

        $recentPayments = Payment::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $recentComments = StoryComment::with(['story', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // Top Categories by story count (with eager loading)
        $topCategories = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(5)
            ->get();

        // Top Stories by ratings (with eager loading)
        $topRatedStories = Story::withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->having('ratings_avg_rating', '>', 0)
            ->orderBy('ratings_avg_rating', 'desc')
            ->limit(5)
            ->get();

        // Most Popular Stories (by favorites)
        $mostPopularStories = Story::withCount('favorites')
            ->orderBy('favorites_count', 'desc')
            ->limit(5)
            ->get();

        // Top Stories Listened (optimized queries)
        $topStoriesListenedToday = Cache::remember(
            'dashboard_top_stories_today',
            60, // 1 minute cache
            function() {
                return Story::whereHas('playHistories', function($q) {
                        $q->whereDate('created_at', today());
                    })
                    ->withCount(['playHistories' => function($q) {
                        $q->whereDate('created_at', today());
                    }])
                    ->orderBy('play_histories_count', 'desc')
                    ->limit(10)
                    ->get();
            }
        );

        $topStoriesListenedThisWeek = Cache::remember(
            'dashboard_top_stories_week',
            300, // 5 minutes cache
            function() {
                return Story::whereHas('playHistories', function($q) {
                        $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    })
                    ->withCount(['playHistories' => function($q) {
                        $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    }])
                    ->orderBy('play_histories_count', 'desc')
                    ->limit(10)
                    ->get();
            }
        );

        $topStoriesListenedThisMonth = Cache::remember(
            'dashboard_top_stories_month',
            600, // 10 minutes cache
            function() {
                return Story::whereHas('playHistories', function($q) {
                        $q->whereMonth('created_at', now()->month);
                    })
                    ->withCount(['playHistories' => function($q) {
                        $q->whereMonth('created_at', now()->month);
                    }])
                    ->orderBy('play_histories_count', 'desc')
                    ->limit(10)
                    ->get();
            }
        );

        $topStoriesListenedThisYear = Cache::remember(
            'dashboard_top_stories_year',
            1800, // 30 minutes cache
            function() {
                return Story::whereHas('playHistories', function($q) {
                        $q->whereYear('created_at', now()->year);
                    })
                    ->withCount(['playHistories' => function($q) {
                        $q->whereYear('created_at', now()->year);
                    }])
                    ->orderBy('play_histories_count', 'desc')
                    ->limit(10)
                    ->get();
            }
        );

        // Get Voice Actors Analytics (cached)
        $voiceActorsAnalytics = Cache::remember(
            'dashboard_voice_actors_' . md5($startDate . $endDate),
            $cacheDuration,
            function() use ($startDate, $endDate) {
                return $this->getVoiceActorsAnalytics($startDate, $endDate);
            }
        );

        // Get Content Moderation Analytics (cached - shorter cache for real-time data)
        $moderationAnalytics = Cache::remember(
            'dashboard_moderation_' . md5($startDate . $endDate),
            60, // 1 minute cache for moderation data
            function() use ($startDate, $endDate) {
                return $this->getModerationAnalytics($startDate, $endDate);
            }
        );

        // Get Device & Platform Analytics (cached)
        try {
            $devicePlatformAnalytics = Cache::remember(
                'dashboard_device_platform_' . md5($startDate . $endDate),
                $cacheDuration,
                function() use ($startDate, $endDate) {
                    return $this->getDevicePlatformAnalytics($startDate, $endDate);
                }
            );
        } catch (\Exception $e) {
            // Log error and return empty analytics
            \Log::error('Error getting device platform analytics: ' . $e->getMessage());
            $devicePlatformAnalytics = [
                'device_types' => [],
                'platforms' => [],
                'os_versions' => [],
                'browsers' => [],
                'device_models' => [],
                'app_versions' => [],
                'active_devices' => 0,
                'engagement' => [],
                'revenue' => [],
                'retention_rates' => [],
                'growth' => []
            ];
        }

        // Prepare view data
        $viewData = [
            'stats' => $stats,
            'recentStories' => $recentStories,
            'recentUsers' => $recentUsers,
            'recentPayments' => $recentPayments,
            'recentComments' => $recentComments,
            'topCategories' => $topCategories,
            'topRatedStories' => $topRatedStories,
            'mostPopularStories' => $mostPopularStories,
            'monthlyRevenue' => $monthlyRevenue,
            'dailyUserRegistrations' => $dailyUserRegistrations,
            'dailyPlayHistory' => $dailyPlayHistory,
            'topStoriesListenedToday' => $topStoriesListenedToday,
            'topStoriesListenedThisWeek' => $topStoriesListenedThisWeek,
            'topStoriesListenedThisMonth' => $topStoriesListenedThisMonth,
            'topStoriesListenedThisYear' => $topStoriesListenedThisYear,
            'comparisonData' => $comparisonData,
            'compareMode' => $compareMode,
            'alerts' => $alerts,
            'systemHealth' => $systemHealth,
            'recentActivity' => $recentActivity,
            'voiceActorsAnalytics' => $voiceActorsAnalytics,
            'moderationAnalytics' => $moderationAnalytics,
            'devicePlatformAnalytics' => $devicePlatformAnalytics
        ];

        // Cache the view data
        Cache::put($cacheKey, $viewData, $cacheDuration);

        return view('admin.dashboard', $viewData);
        } catch (\Exception $e) {
            // Log the error with full context
            \Log::error('Dashboard Error: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // Return a simple error response to avoid cascading errors
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در بارگذاری داشبورد. لطفاً دوباره تلاش کنید.',
                    'error' => 'DASHBOARD_ERROR'
                ], 500);
            }

            // For web requests, return a simple error response
            // Use a simple HTML response to avoid view rendering issues
            $errorMessage = 'خطا در بارگذاری داشبورد. لطفاً دوباره تلاش کنید.';
            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [
                    'message' => $errorMessage,
                    'error_code' => 'DASHBOARD_ERROR'
                ], 500);
            }

            // Fallback to simple HTML if error view doesn't exist
            return response('<html><body><h1>خطا</h1><p>' . htmlspecialchars($errorMessage) . '</p></body></html>', 500)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }
    }

    /**
     * Get basic statistics (extracted for caching)
     */
    private function getBasicStatistics($startDate, $endDate)
    {
        return [
            // User Statistics (optimized with single query where possible)
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
            'blocked_users' => User::where('status', 'blocked')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),

            // Content Statistics
            'total_stories' => Story::count(),
            'published_stories' => Story::where('status', 'published')->count(),
            'pending_stories' => Story::where('status', 'pending')->count(),
            'draft_stories' => Story::where('status', 'draft')->count(),
            'total_episodes' => Episode::count(),
            'published_episodes' => Episode::where('status', 'published')->count(),
            'pending_episodes' => Episode::where('status', 'pending')->count(),
            'draft_episodes' => Episode::where('status', 'draft')->count(),

            // Category Statistics
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'inactive_categories' => Category::where('is_active', false)->count(),

            // Subscription Statistics
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'expired_subscriptions' => Subscription::where('status', 'expired')->count(),
            'cancelled_subscriptions' => Subscription::where('status', 'cancelled')->count(),

            // Financial Statistics
            'total_payments' => Payment::count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'failed_payments' => Payment::where('status', 'failed')->count(),
            'revenue_today' => Payment::where('status', 'completed')->whereDate('created_at', today())->sum('amount'),
            'revenue_this_week' => Payment::where('status', 'completed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
            'revenue_this_month' => Payment::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('amount'),
            'revenue_this_year' => Payment::where('status', 'completed')->whereYear('created_at', now()->year)->sum('amount'),

            // Engagement Statistics
            'total_comments' => StoryComment::count() + UserComment::count(),
            'approved_comments' => StoryComment::where('is_approved', true)->count() + UserComment::where('is_approved', true)->count(),
            'pending_comments' => StoryComment::where('is_approved', false)->count() + UserComment::where('is_approved', false)->count(),
            'total_ratings' => Rating::count(),
            'average_rating' => Rating::avg('rating') ?? 0,
            'total_favorites' => Favorite::count(),

            // Play History Statistics (Story Listens)
            'total_play_history' => PlayHistory::count(),
            'play_history_today' => PlayHistory::whereDate('created_at', today())->count(),
            'play_history_this_week' => PlayHistory::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'play_history_this_month' => PlayHistory::whereMonth('created_at', now()->month)->count(),
            'play_history_this_year' => PlayHistory::whereYear('created_at', now()->year)->count(),

            // Story Listens by Story (Top Stories)
            'top_stories_today' => PlayHistory::whereDate('created_at', today())
                ->select('story_id', DB::raw('count(*) as listens'))
                ->groupBy('story_id')
                ->orderBy('listens', 'desc')
                ->limit(10)
                ->get(),
            'top_stories_this_week' => PlayHistory::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->select('story_id', DB::raw('count(*) as listens'))
                ->groupBy('story_id')
                ->orderBy('listens', 'desc')
                ->limit(10)
                ->get(),
            'top_stories_this_month' => PlayHistory::whereMonth('created_at', now()->month)
                ->select('story_id', DB::raw('count(*) as listens'))
                ->groupBy('story_id')
                ->orderBy('listens', 'desc')
                ->limit(10)
                ->get(),
            'top_stories_this_year' => PlayHistory::whereYear('created_at', now()->year)
                ->select('story_id', DB::raw('count(*) as listens'))
                ->groupBy('story_id')
                ->orderBy('listens', 'desc')
                ->limit(10)
                ->get(),

            // Plan Sales Statistics
            'total_plans' => SubscriptionPlan::count(),
            'active_plans' => SubscriptionPlan::where('is_active', true)->count(),
            'featured_plans' => SubscriptionPlan::where('is_featured', true)->count(),
            'plan_sales_this_month' => Subscription::whereMonth('created_at', now()->month)->count(),
            'plan_sales_last_month' => Subscription::whereMonth('created_at', now()->subMonth()->month)->count(),
            'plan_sales_today' => Subscription::whereDate('created_at', today())->count(),
            'plan_sales_this_week' => Subscription::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),

            // Performance Metrics
            'avg_completion_rate' => $this->calculateAvgCompletionRate(),
            'avg_listening_duration' => $this->calculateAvgListeningDuration(),
            'dau' => $this->calculateDAU(), // Daily Active Users
            'wau' => $this->calculateWAU(), // Weekly Active Users
            'mau' => $this->calculateMAU(), // Monthly Active Users
            'retention_rate' => $this->calculateRetentionRate(),
            'churn_rate' => $this->calculateChurnRate(),
            'arpu' => $this->calculateARPU(), // Average Revenue Per User
            'ltv' => $this->calculateLTV(), // Lifetime Value estimation
            'avg_session_duration' => $this->calculateAvgSessionDuration(),
            'stories_per_user' => $this->calculateStoriesPerUser(),
            'episodes_per_user' => $this->calculateEpisodesPerUser(),
            'return_user_rate' => $this->calculateReturnUserRate(),
        ];

        // Growth Statistics (comparing with previous periods)
        $stats['user_growth_rate'] = $this->calculateGrowthRate(
            User::whereMonth('created_at', now()->subMonth()->month)->count(),
            User::whereMonth('created_at', now()->month)->count()
        );

        $stats['story_growth_rate'] = $this->calculateGrowthRate(
            Story::whereMonth('created_at', now()->subMonth()->month)->count(),
            Story::whereMonth('created_at', now()->month)->count()
        );

        $stats['revenue_growth_rate'] = $this->calculateGrowthRate(
            Payment::where('status', 'completed')->whereMonth('created_at', now()->subMonth()->month)->sum('amount'),
            Payment::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('amount')
        );

        $stats['plan_sales_growth_rate'] = $this->calculateGrowthRate(
            Subscription::whereMonth('created_at', now()->subMonth()->month)->count(),
            Subscription::whereMonth('created_at', now()->month)->count()
        );

        // Recent Activity
        $recentStories = Story::with(['category', 'director', 'narrator'])
            ->latest()
            ->limit(5)
            ->get();

        $recentUsers = User::latest()
            ->limit(5)
            ->get();

        $recentPayments = Payment::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $recentComments = StoryComment::with(['story', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // Top Categories by story count
        $topCategories = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(5)
            ->get();

        // Top Stories by ratings
        $topRatedStories = Story::withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->having('ratings_avg_rating', '>', 0)
            ->orderBy('ratings_avg_rating', 'desc')
            ->limit(5)
            ->get();

        // Most Popular Stories (by favorites)
        $mostPopularStories = Story::withCount('favorites')
            ->orderBy('favorites_count', 'desc')
            ->limit(5)
            ->get();

        // Top Stories Listened (by play history)
        $topStoriesListenedToday = Story::whereHas('playHistories', function($q) {
                $q->whereDate('created_at', today());
            })
            ->withCount(['playHistories' => function($q) {
                $q->whereDate('created_at', today());
            }])
            ->orderBy('play_histories_count', 'desc')
            ->limit(10)
            ->get();

        $topStoriesListenedThisWeek = Story::whereHas('playHistories', function($q) {
                $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->withCount(['playHistories' => function($q) {
                $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }])
            ->orderBy('play_histories_count', 'desc')
            ->limit(10)
            ->get();

        $topStoriesListenedThisMonth = Story::whereHas('playHistories', function($q) {
                $q->whereMonth('created_at', now()->month);
            })
            ->withCount(['playHistories' => function($q) {
                $q->whereMonth('created_at', now()->month);
            }])
            ->orderBy('play_histories_count', 'desc')
            ->limit(10)
            ->get();

        $topStoriesListenedThisYear = Story::whereHas('playHistories', function($q) {
                $q->whereYear('created_at', now()->year);
            })
            ->withCount(['playHistories' => function($q) {
                $q->whereYear('created_at', now()->year);
            }])
            ->orderBy('play_histories_count', 'desc')
            ->limit(10)
            ->get();

        // Recent Reports
        $recentReports = Report::with('user')
            ->latest()
            ->limit(5)
            ->get();

        // Plan Sales Data
        $planSalesData = Subscription::selectRaw('type, COUNT(*) as count, SUM(price) as total_revenue')
            ->where('status', 'active')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();

        $topSellingPlans = SubscriptionPlan::withCount(['subscriptions' => function($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('subscriptions_count', 'desc')
            ->limit(5)
            ->get();

        // Get additional chart data
        $dailyEngagement = Cache::remember(
            'dashboard_daily_engagement_' . md5($startDate . $endDate),
            $cacheDuration,
            function() use ($startDate, $endDate) {
                return $this->getDailyEngagement($startDate, $endDate);
            }
        );

        // Plan sales data (cached)
        $planSalesData = Cache::remember(
            'dashboard_plan_sales_' . md5($startDate . $endDate),
            $cacheDuration,
            function() use ($startDate, $endDate) {
                return $this->getPlanSalesData($startDate, $endDate);
            }
        );

        $topSellingPlans = Cache::remember(
            'dashboard_top_selling_plans',
            600, // 10 minutes
            function() {
                return SubscriptionPlan::withCount('subscriptions')
                    ->orderBy('subscriptions_count', 'desc')
                    ->limit(5)
                    ->get();
            }
        );

        $recentReports = collect(); // Placeholder for reports

        return view('admin.dashboard', compact(
            'stats',
            'recentStories',
            'recentUsers',
            'recentPayments',
            'recentComments',
            'topCategories',
            'topRatedStories',
            'mostPopularStories',
            'recentReports',
            'monthlyRevenue',
            'dailyUserRegistrations',
            'dailyPlayHistory',
            'dailyEngagement',
            'planSalesData',
            'topSellingPlans',
            'topStoriesListenedToday',
            'topStoriesListenedThisWeek',
            'topStoriesListenedThisMonth',
            'topStoriesListenedThisYear',
            'comparisonData',
            'compareMode',
            'alerts',
            'systemHealth',
            'recentActivity'
        ));
    }

    /**
     * Get monthly revenue data
     */
    private function getMonthlyRevenue($startDate, $endDate)
    {
        return Payment::where('status', 'completed')
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(amount) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
    }

    /**
     * Get daily user registrations
     */
    private function getDailyUserRegistrations($startDate, $endDate)
    {
        return User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Get daily play history
     */
    private function getDailyPlayHistory($startDate, $endDate)
    {
        $daysDiff = $startDate->diffInDays($endDate);
        $daysForChart = min(max($daysDiff, 7), 90); // Between 7 and 90 days

        return collect(range(0, $daysForChart - 1))->map(function($day) use ($endDate) {
            $date = $endDate->copy()->subDays($day);
            return [
                'date' => $date->format('Y-m-d'),
                'count' => PlayHistory::whereDate('played_at', $date->format('Y-m-d'))->count()
            ];
        })->reverse()->values();
    }

    /**
     * Get daily engagement data
     */
    private function getDailyEngagement($startDate, $endDate)
    {
        $daysDiff = $startDate->diffInDays($endDate);
        $daysForChart = min(max($daysDiff, 7), 90);

        return collect(range(0, $daysForChart - 1))->map(function($day) use ($endDate) {
            $date = $endDate->copy()->subDays($day);
            return [
                'date' => $date->format('Y-m-d'),
                'comments' => StoryComment::whereDate('created_at', $date->format('Y-m-d'))->count() +
                             UserComment::whereDate('created_at', $date->format('Y-m-d'))->count(),
                'ratings' => Rating::whereDate('created_at', $date->format('Y-m-d'))->count(),
                'favorites' => Favorite::whereDate('created_at', $date->format('Y-m-d'))->count()
            ];
        })->reverse()->values();
    }

    /**
     * Get plan sales data
     */
    private function getPlanSalesData($startDate, $endDate)
    {
        return Subscription::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    private function calculateGrowthRate($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get start date based on date range selection
     */
    private function getStartDate($dateRange, $customDateFrom = null)
    {
        if ($dateRange === 'custom' && $customDateFrom) {
            return Carbon::parse($customDateFrom)->startOfDay();
        }

        switch ($dateRange) {
            case 'today':
                return today()->startOfDay();
            case '7days':
                return now()->subDays(7)->startOfDay();
            case '30days':
                return now()->subDays(30)->startOfDay();
            case '3months':
                return now()->subMonths(3)->startOfDay();
            case '1year':
                return now()->subYear()->startOfDay();
            default:
                return now()->subDays(30)->startOfDay();
        }
    }

    /**
     * Calculate average completion rate for stories
     */
    private function calculateAvgCompletionRate()
    {
        $totalPlays = PlayHistory::count();
        if ($totalPlays == 0) return 0;

        $completedPlays = PlayHistory::where('completed', true)->count();
        return round(($completedPlays / $totalPlays) * 100, 1);
    }

    /**
     * Calculate average listening duration
     */
    private function calculateAvgListeningDuration()
    {
        $avgDuration = PlayHistory::avg('duration_played');
        return round($avgDuration ?? 0, 0); // in seconds
    }

    /**
     * Calculate Daily Active Users
     */
    private function calculateDAU()
    {
        return User::whereDate('last_login_at', today())->count();
    }

    /**
     * Calculate Weekly Active Users
     */
    private function calculateWAU()
    {
        return User::where('last_login_at', '>=', now()->startOfWeek())->count();
    }

    /**
     * Calculate Monthly Active Users
     */
    private function calculateMAU()
    {
        return User::where('last_login_at', '>=', now()->startOfMonth())->count();
    }

    /**
     * Calculate retention rate (users who logged in this week and last week)
     */
    private function calculateRetentionRate()
    {
        $lastWeekUsers = User::whereBetween('last_login_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->pluck('id');

        if ($lastWeekUsers->count() == 0) return 0;

        $retainedUsers = User::whereIn('id', $lastWeekUsers)
            ->where('last_login_at', '>=', now()->startOfWeek())
            ->count();

        return round(($retainedUsers / $lastWeekUsers->count()) * 100, 1);
    }

    /**
     * Calculate churn rate
     */
    private function calculateChurnRate()
    {
        $activeLastMonth = User::where('last_login_at', '>=', now()->subMonth()->startOfMonth())
            ->where('last_login_at', '<', now()->startOfMonth())
            ->count();

        $totalLastMonth = User::where('created_at', '<', now()->startOfMonth())->count();

        if ($totalLastMonth == 0) return 0;

        $churnedUsers = $totalLastMonth - User::where('last_login_at', '>=', now()->startOfMonth())
            ->where('created_at', '<', now()->startOfMonth())
            ->count();

        return round(($churnedUsers / $totalLastMonth) * 100, 1);
    }

    /**
     * Calculate Average Revenue Per User
     */
    private function calculateARPU()
    {
        $totalUsers = User::count();
        if ($totalUsers == 0) return 0;

        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        return round($totalRevenue / $totalUsers, 0);
    }

    /**
     * Calculate Lifetime Value (estimation)
     */
    private function calculateLTV()
    {
        $avgMonthlyRevenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(3))
            ->sum('amount') / 3;

        $avgMonthlyUsers = User::where('created_at', '>=', now()->subMonths(3))->count() / 3;

        if ($avgMonthlyUsers == 0) return 0;

        $avgRevenuePerUserPerMonth = $avgMonthlyRevenue / max($avgMonthlyUsers, 1);
        $avgLifespanMonths = 12; // Estimated average user lifespan

        return round($avgRevenuePerUserPerMonth * $avgLifespanMonths, 0);
    }

    /**
     * Calculate average session duration
     */
    private function calculateAvgSessionDuration()
    {
        $avgDuration = PlayHistory::avg('duration_played');
        return round(($avgDuration ?? 0) / 60, 1); // Convert to minutes
    }

    /**
     * Calculate average stories per user
     */
    private function calculateStoriesPerUser()
    {
        $totalUsers = User::where('status', 'active')->count();
        if ($totalUsers == 0) return 0;

        $totalStoriesListened = PlayHistory::distinct('user_id')->count('user_id');
        return round($totalStoriesListened / $totalUsers, 1);
    }

    /**
     * Calculate average episodes per user
     */
    private function calculateEpisodesPerUser()
    {
        $totalUsers = User::where('status', 'active')->count();
        if ($totalUsers == 0) return 0;

        $totalEpisodesListened = PlayHistory::distinct('user_id')->count('user_id');
        return round($totalEpisodesListened / $totalUsers, 1);
    }

    /**
     * Calculate return user rate
     */
    private function calculateReturnUserRate()
    {
        $totalUsers = User::count();
        if ($totalUsers == 0) return 0;

        $returnUsers = User::where('last_login_at', '>=', now()->subDays(30))
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $oldUsers = User::where('created_at', '<', now()->subDays(30))->count();

        if ($oldUsers == 0) return 0;

        return round(($returnUsers / $oldUsers) * 100, 1);
    }

    /**
     * Export dashboard data to CSV
     */
    public function export(Request $request)
    {
        $dateRange = $request->get('date_range', '30days');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $startDate = $this->getStartDate($dateRange, $dateFrom);
        $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now();

        // Get all stats
        $stats = $this->getStats($startDate, $endDate);

        $filename = 'dashboard_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $callback = function() use ($stats, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Header
            fputcsv($file, ['گزارش داشبورد - ' . $startDate->format('Y/m/d') . ' تا ' . $endDate->format('Y/m/d')]);
            fputcsv($file, []);

            // Statistics Section
            fputcsv($file, ['آمار کلی']);
            fputcsv($file, ['عنوان', 'مقدار']);
            fputcsv($file, ['کل کاربران', $stats['total_users']]);
            fputcsv($file, ['کاربران فعال', $stats['active_users']]);
            fputcsv($file, ['کل داستان‌ها', $stats['total_stories']]);
            fputcsv($file, ['کل اپیزودها', $stats['total_episodes']]);
            fputcsv($file, ['کل پرداخت‌ها', $stats['total_payments']]);
            fputcsv($file, ['درآمد کل', number_format($stats['total_revenue'], 0) . ' تومان']);
            fputcsv($file, ['کل نظرات', $stats['total_comments']]);
            fputcsv($file, ['کل تاریخچه پخش', $stats['total_play_history']]);
            fputcsv($file, []);

            // Performance Metrics
            fputcsv($file, ['معیارهای عملکرد']);
            fputcsv($file, ['عنوان', 'مقدار']);
            fputcsv($file, ['نرخ تکمیل', number_format($stats['avg_completion_rate'] ?? 0, 1) . '%']);
            fputcsv($file, ['میانگین مدت زمان گوش دادن', number_format(($stats['avg_listening_duration'] ?? 0) / 60, 0) . ' دقیقه']);
            fputcsv($file, ['کاربران فعال روزانه (DAU)', $stats['dau'] ?? 0]);
            fputcsv($file, ['کاربران فعال هفتگی (WAU)', $stats['wau'] ?? 0]);
            fputcsv($file, ['کاربران فعال ماهانه (MAU)', $stats['mau'] ?? 0]);
            fputcsv($file, ['نرخ حفظ کاربر', number_format($stats['retention_rate'] ?? 0, 1) . '%']);
            fputcsv($file, ['نرخ ریزش', number_format($stats['churn_rate'] ?? 0, 1) . '%']);
            fputcsv($file, ['درآمد به ازای هر کاربر (ARPU)', number_format($stats['arpu'] ?? 0) . ' تومان']);
            fputcsv($file, ['ارزش طول عمر کاربر (LTV)', number_format($stats['ltv'] ?? 0) . ' تومان']);
            fputcsv($file, []);

            // Revenue Breakdown
            fputcsv($file, ['درآمد امروز', number_format($stats['revenue_today'] ?? 0, 0) . ' تومان']);
            fputcsv($file, ['درآمد این هفته', number_format($stats['revenue_this_week'] ?? 0, 0) . ' تومان']);
            fputcsv($file, ['درآمد این ماه', number_format($stats['revenue_this_month'] ?? 0, 0) . ' تومان']);
            fputcsv($file, ['درآمد امسال', number_format($stats['revenue_this_year'] ?? 0, 0) . ' تومان']);
            fputcsv($file, []);

            // Play History
            fputcsv($file, ['تاریخچه پخش']);
            fputcsv($file, ['امروز', $stats['play_history_today'] ?? 0]);
            fputcsv($file, ['این هفته', $stats['play_history_this_week'] ?? 0]);
            fputcsv($file, ['این ماه', $stats['play_history_this_month'] ?? 0]);
            fputcsv($file, ['امسال', $stats['play_history_this_year'] ?? 0]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get stats for export
     */
    private function getStats($startDate, $endDate)
    {
        // This is a simplified version - in production, you might want to cache this
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_payments' => Payment::where('status', 'completed')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'total_comments' => StoryComment::count() + UserComment::count(),
            'total_play_history' => PlayHistory::count(),
            'revenue_today' => Payment::where('status', 'completed')->whereDate('created_at', today())->sum('amount'),
            'revenue_this_week' => Payment::where('status', 'completed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
            'revenue_this_month' => Payment::where('status', 'completed')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount'),
            'revenue_this_year' => Payment::where('status', 'completed')->whereYear('created_at', now()->year)->sum('amount'),
            'play_history_today' => PlayHistory::whereDate('played_at', today())->count(),
            'play_history_this_week' => PlayHistory::whereBetween('played_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'play_history_this_month' => PlayHistory::whereMonth('played_at', now()->month)->whereYear('played_at', now()->year)->count(),
            'play_history_this_year' => PlayHistory::whereYear('played_at', now()->year)->count(),
            'avg_completion_rate' => $this->calculateAvgCompletionRate(),
            'avg_listening_duration' => $this->calculateAvgListeningDuration(),
            'dau' => $this->calculateDAU(),
            'wau' => $this->calculateWAU(),
            'mau' => $this->calculateMAU(),
            'retention_rate' => $this->calculateRetentionRate(),
            'churn_rate' => $this->calculateChurnRate(),
            'arpu' => $this->calculateARPU(),
            'ltv' => $this->calculateLTV(),
        ];
    }

    /**
     * Get comparison data for selected period
     */
    private function getComparisonData($startDate, $endDate, $comparePeriod, $compareDateFrom = null, $compareDateTo = null)
    {
        $compareStartDate = null;
        $compareEndDate = null;

        $daysDiff = $startDate->diffInDays($endDate);

        switch ($comparePeriod) {
            case 'previous':
                // Previous period of same duration
                $compareEndDate = $startDate->copy()->subDay();
                $compareStartDate = $compareEndDate->copy()->subDays($daysDiff);
                break;

            case 'lastYear':
                // Same period last year
                $compareStartDate = $startDate->copy()->subYear();
                $compareEndDate = $endDate->copy()->subYear();
                break;

            case 'custom':
                if ($compareDateFrom && $compareDateTo) {
                    $compareStartDate = Carbon::parse($compareDateFrom)->startOfDay();
                    $compareEndDate = Carbon::parse($compareDateTo)->endOfDay();
                }
                break;
        }

        if (!$compareStartDate || !$compareEndDate) {
            return null;
        }

        return [
            'start_date' => $compareStartDate,
            'end_date' => $compareEndDate,
            'total_users' => User::whereBetween('created_at', [$compareStartDate, $compareEndDate])->count(),
            'active_users' => User::where('status', 'active')
                ->whereBetween('last_login_at', [$compareStartDate, $compareEndDate])
                ->count(),
            'total_revenue' => Payment::where('status', 'completed')
                ->whereBetween('created_at', [$compareStartDate, $compareEndDate])
                ->sum('amount'),
            'total_payments' => Payment::where('status', 'completed')
                ->whereBetween('created_at', [$compareStartDate, $compareEndDate])
                ->count(),
            'total_play_history' => PlayHistory::whereBetween('played_at', [$compareStartDate, $compareEndDate])->count(),
            'total_comments' => StoryComment::whereBetween('created_at', [$compareStartDate, $compareEndDate])->count() +
                               UserComment::whereBetween('created_at', [$compareStartDate, $compareEndDate])->count(),
            'new_users' => User::whereBetween('created_at', [$compareStartDate, $compareEndDate])->count(),
                'new_stories' => Story::whereBetween('created_at', [$compareStartDate, $compareEndDate])->count(),
        ];
    }

    /**
     * Get dashboard alerts and notifications
     */
    private function getDashboardAlerts()
    {
        $alerts = [];

        // Check for low revenue (less than 50% of last month)
        $lastMonthRevenue = Payment::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        $thisMonthRevenue = Payment::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        if ($lastMonthRevenue > 0 && $thisMonthRevenue < ($lastMonthRevenue * 0.5)) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => 'هشدار درآمد',
                'message' => 'درآمد این ماه ' . round((($lastMonthRevenue - $thisMonthRevenue) / $lastMonthRevenue) * 100) . '% کمتر از ماه قبل است.',
                'action' => route('admin.payments.index')
            ];
        }

        // Check for high churn rate (more than 10%)
        $churnRate = $this->calculateChurnRate();
        if ($churnRate > 10) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'user-minus',
                'title' => 'نرخ ریزش بالا',
                'message' => 'نرخ ریزش کاربران ' . number_format($churnRate, 1) . '% است که بالاتر از حد نرمال است.',
                'action' => route('admin.users.index')
            ];
        }

        // Check for pending payments
        $pendingPayments = Payment::where('status', 'pending')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($pendingPayments > 10) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'clock',
                'title' => 'پرداخت‌های در انتظار',
                'message' => $pendingPayments . ' پرداخت در ۷ روز گذشته در وضعیت انتظار هستند.',
                'action' => route('admin.payments.index', ['status' => 'pending'])
            ];
        }

        // Check for low completion rate (less than 50%)
        $completionRate = $this->calculateAvgCompletionRate();
        if ($completionRate < 50) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'chart-line',
                'title' => 'نرخ تکمیل پایین',
                'message' => 'نرخ تکمیل داستان‌ها ' . number_format($completionRate, 1) . '% است که نیاز به بررسی دارد.',
                'action' => route('admin.stories.index')
            ];
        }

        // Check for pending comments
        $pendingComments = StoryComment::where('is_approved', false)
            ->where('is_visible', true)
            ->count();

        if ($pendingComments > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'comment',
                'title' => 'نظرات در انتظار تایید',
                'message' => $pendingComments . ' نظر در انتظار بررسی و تایید هستند.',
                'action' => route('admin.comments.pending')
            ];
        }

        // Check for inactive users (not logged in for 30+ days)
        $inactiveUsers = User::where('status', 'active')
            ->where('last_login_at', '<', now()->subDays(30))
            ->orWhereNull('last_login_at')
            ->count();

        if ($inactiveUsers > 100) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'users',
                'title' => 'کاربران غیرفعال',
                'message' => $inactiveUsers . ' کاربر فعال بیش از ۳۰ روز است که وارد سیستم نشده‌اند.',
                'action' => route('admin.users.index')
            ];
        }

        // Check for system health (if we have error logs)
        $recentErrors = \DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDays(1))
            ->count();

        if ($recentErrors > 10) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'exclamation-circle',
                'title' => 'خطاهای سیستم',
                'message' => $recentErrors . ' خطا در ۲۴ ساعت گذشته ثبت شده است.',
                'action' => null
            ];
        }

        return $alerts;
    }

    /**
     * Get system health status
     */
    private function getSystemHealth()
    {
        $health = [
            'overall' => 'healthy',
            'checks' => []
        ];

        // Database connection check
        try {
            \DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'اتصال به پایگاه داده برقرار است',
                'icon' => 'check-circle',
                'color' => 'green'
            ];
        } catch (\Exception $e) {
            $health['overall'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => 'خطا در اتصال به پایگاه داده',
                'icon' => 'x-circle',
                'color' => 'red'
            ];
        }

        // Cache check
        try {
            $testKey = 'health_check_' . time();
            \Cache::put($testKey, 'test', 10);
            $value = \Cache::get($testKey);
            \Cache::forget($testKey);

            if ($value === 'test') {
                $health['checks']['cache'] = [
                    'status' => 'healthy',
                    'message' => 'سیستم کش فعال است',
                    'icon' => 'check-circle',
                    'color' => 'green'
                ];
            } else {
                $health['overall'] = 'warning';
                $health['checks']['cache'] = [
                    'status' => 'warning',
                    'message' => 'سیستم کش پاسخ نمی‌دهد',
                    'icon' => 'exclamation-circle',
                    'color' => 'yellow'
                ];
            }
        } catch (\Exception $e) {
            $health['overall'] = 'warning';
            $health['checks']['cache'] = [
                'status' => 'warning',
                'message' => 'خطا در سیستم کش',
                'icon' => 'exclamation-circle',
                'color' => 'yellow'
            ];
        }

        // Storage check
        try {
            $disk = \Storage::disk('public');
            $testFile = 'health_check_' . time() . '.txt';
            $disk->put($testFile, 'test');
            $exists = $disk->exists($testFile);
            $disk->delete($testFile);

            if ($exists) {
                $health['checks']['storage'] = [
                    'status' => 'healthy',
                    'message' => 'سیستم ذخیره‌سازی در دسترس است',
                    'icon' => 'check-circle',
                    'color' => 'green'
                ];
            } else {
                $health['overall'] = 'warning';
                $health['checks']['storage'] = [
                    'status' => 'warning',
                    'message' => 'مشکل در سیستم ذخیره‌سازی',
                    'icon' => 'exclamation-circle',
                    'color' => 'yellow'
                ];
            }
        } catch (\Exception $e) {
            $health['overall'] = 'warning';
            $health['checks']['storage'] = [
                'status' => 'warning',
                'message' => 'خطا در دسترسی به سیستم ذخیره‌سازی',
                'icon' => 'exclamation-circle',
                'color' => 'yellow'
            ];
        }

        // Queue check
        try {
            $queueDriver = config('queue.default');
            $health['checks']['queue'] = [
                'status' => 'healthy',
                'message' => 'سیستم صف فعال است (' . $queueDriver . ')',
                'icon' => 'check-circle',
                'color' => 'green'
            ];
        } catch (\Exception $e) {
            $health['overall'] = 'warning';
            $health['checks']['queue'] = [
                'status' => 'warning',
                'message' => 'مشکل در سیستم صف',
                'icon' => 'exclamation-circle',
                'color' => 'yellow'
            ];
        }

        // Failed jobs check
        $failedJobs = \DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDays(1))
            ->count();

        if ($failedJobs > 0) {
            $health['overall'] = $health['overall'] === 'unhealthy' ? 'unhealthy' : 'warning';
            $health['checks']['failed_jobs'] = [
                'status' => $failedJobs > 10 ? 'unhealthy' : 'warning',
                'message' => $failedJobs . ' کار ناموفق در ۲۴ ساعت گذشته',
                'icon' => $failedJobs > 10 ? 'x-circle' : 'exclamation-circle',
                'color' => $failedJobs > 10 ? 'red' : 'yellow',
                'count' => $failedJobs
            ];
        } else {
            $health['checks']['failed_jobs'] = [
                'status' => 'healthy',
                'message' => 'هیچ کار ناموفقی وجود ندارد',
                'icon' => 'check-circle',
                'color' => 'green'
            ];
        }

        // Disk space check (if on Linux)
        if (PHP_OS_FAMILY === 'Linux') {
            $diskFree = disk_free_space(base_path());
            $diskTotal = disk_total_space(base_path());
            $diskUsedPercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

            if ($diskUsedPercent > 90) {
                $health['overall'] = 'unhealthy';
                $health['checks']['disk_space'] = [
                    'status' => 'unhealthy',
                    'message' => 'فضای دیسک بیش از ۹۰% پر است',
                    'icon' => 'x-circle',
                    'color' => 'red',
                    'percent' => round($diskUsedPercent, 1)
                ];
            } elseif ($diskUsedPercent > 75) {
                $health['overall'] = $health['overall'] === 'unhealthy' ? 'unhealthy' : 'warning';
                $health['checks']['disk_space'] = [
                    'status' => 'warning',
                    'message' => 'فضای دیسک ' . round($diskUsedPercent, 1) . '% استفاده شده',
                    'icon' => 'exclamation-circle',
                    'color' => 'yellow',
                    'percent' => round($diskUsedPercent, 1)
                ];
            } else {
                $health['checks']['disk_space'] = [
                    'status' => 'healthy',
                    'message' => 'فضای دیسک: ' . round($diskUsedPercent, 1) . '% استفاده شده',
                    'icon' => 'check-circle',
                    'color' => 'green',
                    'percent' => round($diskUsedPercent, 1)
                ];
            }
        }

        // Memory usage check
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;

        if ($memoryPercent > 80) {
            $health['overall'] = $health['overall'] === 'unhealthy' ? 'unhealthy' : 'warning';
            $health['checks']['memory'] = [
                'status' => 'warning',
                'message' => 'استفاده از حافظه: ' . round($memoryPercent, 1) . '%',
                'icon' => 'exclamation-circle',
                'color' => 'yellow',
                'percent' => round($memoryPercent, 1)
            ];
        } else {
            $health['checks']['memory'] = [
                'status' => 'healthy',
                'message' => 'استفاده از حافظه: ' . round($memoryPercent, 1) . '%',
                'icon' => 'check-circle',
                'color' => 'green',
                'percent' => round($memoryPercent, 1)
            ];
        }

        return $health;
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get recent activity feed
     */
    private function getRecentActivity($startDate, $endDate)
    {
        $activities = collect();

        // Recent user registrations
        $recentUsers = User::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'type' => 'user_registered',
                    'icon' => 'user-plus',
                    'color' => 'blue',
                    'title' => 'کاربر جدید ثبت‌نام کرد',
                    'description' => $user->first_name . ' ' . $user->last_name,
                    'time' => $user->created_at,
                    'url' => route('admin.users.show', $user->id)
                ];
            });

        $activities = $activities->merge($recentUsers);

        // Recent story creations
        $recentStories = Story::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($story) {
                return [
                    'type' => 'story_created',
                    'icon' => 'book',
                    'color' => 'green',
                    'title' => 'داستان جدید ایجاد شد',
                    'description' => $story->title,
                    'time' => $story->created_at,
                    'url' => route('admin.stories.show', $story->id)
                ];
            });

        $activities = $activities->merge($recentStories);

        // Recent payments
        $recentPayments = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with('user')
            ->get()
            ->map(function($payment) {
                return [
                    'type' => 'payment_received',
                    'icon' => 'dollar-sign',
                    'color' => 'yellow',
                    'title' => 'پرداخت جدید دریافت شد',
                    'description' => number_format($payment->amount) . ' تومان - ' . ($payment->user?->first_name ?? 'کاربر ناشناس'),
                    'time' => $payment->created_at,
                    'url' => route('admin.payments.show', $payment->id)
                ];
            });

        $activities = $activities->merge($recentPayments);

        // Recent comments
        $recentComments = StoryComment::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with(['user', 'story'])
            ->get()
            ->map(function($comment) {
                return [
                    'type' => 'comment_added',
                    'icon' => 'comment',
                    'color' => 'purple',
                    'title' => 'نظر جدید اضافه شد',
                    'description' => 'برای داستان: ' . ($comment->story?->title ?? 'نامشخص'),
                    'time' => $comment->created_at,
                    'url' => route('admin.comments.index')
                ];
            });

        $activities = $activities->merge($recentComments);

        // Sort by time and limit to 20 most recent
        return $activities->sortByDesc('time')->take(20)->values();
    }

    /**
     * Global search across all entities
     */
    public function globalSearch(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'حداقل ۲ کاراکتر وارد کنید'
            ], 400);
        }

        $results = [
            'stories' => [],
            'episodes' => [],
            'users' => [],
            'payments' => []
        ];

        // Search stories
        $stories = Story::where('title', 'like', "%{$query}%")
            ->orWhere('subtitle', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with('category')
            ->limit(5)
            ->get()
            ->map(function($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'category' => $story->category
                ];
            });

        $results['stories'] = $stories;

        // Search episodes
        $episodes = Episode::where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with('story')
            ->limit(5)
            ->get()
            ->map(function($episode) {
                return [
                    'id' => $episode->id,
                    'title' => $episode->title,
                    'story' => $episode->story
                ];
            });

        $results['episodes'] = $episodes;

        // Search users
        $users = User::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email
                ];
            });

        $results['users'] = $users;

        // Search payments
        $payments = Payment::whereHas('user', function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->orWhere('transaction_id', 'like', "%{$query}%")
            ->with('user')
            ->limit(5)
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'user' => $payment->user
                ];
            });

        $results['payments'] = $payments;

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Get voice actors analytics data
     */
    private function getVoiceActorsAnalytics($startDate, $endDate)
    {
        // Get all voice actors (users with voice_actor, admin, or super_admin role)
        $voiceActors = User::whereIn('role', ['voice_actor', 'admin', 'super_admin'])
            ->withCount([
                'storiesAsNarrator',
                'storiesAsAuthor',
                'characters'
            ])
            ->get();

        $analytics = [];

        foreach ($voiceActors as $actor) {
            // Get stories narrated by this voice actor (all time, not filtered by date)
            $narratedStories = Story::where('narrator_id', $actor->id)->get();

            // Get stories authored by this voice actor (all time, not filtered by date)
            $authoredStories = Story::where('author_id', $actor->id)->get();

            // Calculate total plays for narrated stories
            $totalPlays = PlayHistory::whereHas('story', function($q) use ($actor) {
                    $q->where('narrator_id', $actor->id);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Calculate average rating for narrated stories
            $avgRating = Rating::whereHas('story', function($q) use ($actor) {
                    $q->where('narrator_id', $actor->id);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->avg('rating') ?? 0;

            // Calculate total favorites for narrated stories
            $totalFavorites = Favorite::whereHas('story', function($q) use ($actor) {
                    $q->where('narrator_id', $actor->id);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Calculate engagement rate (favorites + ratings + comments per story)
            $totalEngagement = $totalFavorites +
                Rating::whereHas('story', function($q) use ($actor) {
                    $q->where('narrator_id', $actor->id);
                })->whereBetween('created_at', [$startDate, $endDate])->count() +
                StoryComment::whereHas('story', function($q) use ($actor) {
                    $q->where('narrator_id', $actor->id);
                })->whereBetween('created_at', [$startDate, $endDate])->count();

            $engagementRate = $narratedStories->count() > 0
                ? round($totalEngagement / $narratedStories->count(), 2)
                : 0;

            $analytics[] = [
                'id' => $actor->id,
                'name' => $actor->first_name . ' ' . $actor->last_name,
                'phone' => $actor->phone_number,
                'role' => $actor->role,
                'status' => $actor->status,
                'total_stories_narrated' => $narratedStories->count(),
                'total_stories_authored' => $authoredStories->count(),
                'total_characters' => $actor->characters_count,
                'total_plays' => $totalPlays,
                'average_rating' => round($avgRating, 2),
                'total_favorites' => $totalFavorites,
                'engagement_rate' => $engagementRate,
                'total_engagement' => $totalEngagement,
                'profile_image' => $actor->profile_image_url,
            ];
        }

        // Sort by total plays (most popular first)
        usort($analytics, function($a, $b) {
            return $b['total_plays'] <=> $a['total_plays'];
        });

        return $analytics;
    }

    /**
     * Get content moderation analytics
     */
    private function getModerationAnalytics($startDate, $endDate)
    {
        // Comments moderation
        $pendingComments = StoryComment::where('is_approved', false)
            ->whereNull('approved_at')
            ->count();

        $approvedComments = StoryComment::where('is_approved', true)
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->count();

        $rejectedComments = StoryComment::where('is_approved', false)
            ->where('is_visible', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalComments = StoryComment::whereBetween('created_at', [$startDate, $endDate])->count();

        // Calculate approval/rejection rates
        $processedComments = $approvedComments + $rejectedComments;
        $approvalRate = $processedComments > 0
            ? round(($approvedComments / $processedComments) * 100, 1)
            : 0;

        $rejectionRate = $processedComments > 0
            ? round(($rejectedComments / $processedComments) * 100, 1)
            : 0;

        // Stories moderation
        $pendingStories = Story::where('moderation_status', 'pending')
            ->orWhere(function($q) {
                $q->whereNull('moderation_status')
                  ->where('status', 'pending');
            })
            ->count();

        $approvedStories = Story::where('moderation_status', 'approved')
            ->whereBetween('moderated_at', [$startDate, $endDate])
            ->count();

        $rejectedStories = Story::where('moderation_status', 'rejected')
            ->whereBetween('moderated_at', [$startDate, $endDate])
            ->count();

        $flaggedStories = Story::where('moderation_status', 'flagged')
            ->orWhere('flag_type', '!=', null)
            ->count();

        // Episodes moderation
        $pendingEpisodes = Episode::where('status', 'pending')
            ->count();

        // Recent pending items (for queue)
        $recentPendingComments = StoryComment::where('is_approved', false)
            ->whereNull('approved_at')
            ->with(['story', 'user'])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function($comment) {
                return [
                    'id' => $comment->id,
                    'type' => 'comment',
                    'content' => substr($comment->content ?? '', 0, 100) . '...',
                    'story_title' => $comment->story?->title ?? 'نامشخص',
                    'user_name' => ($comment->user?->first_name ?? '') . ' ' . ($comment->user?->last_name ?? ''),
                    'created_at' => $comment->created_at,
                    'url' => route('admin.comments.show', $comment->id)
                ];
            });

        $recentPendingStories = Story::where(function($q) {
                $q->where('moderation_status', 'pending')
                  ->orWhere(function($q2) {
                      $q2->whereNull('moderation_status')
                         ->where('status', 'pending');
                  });
            })
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function($story) {
                return [
                    'id' => $story->id,
                    'type' => 'story',
                    'content' => $story->title,
                    'created_at' => $story->created_at,
                    'url' => route('admin.stories.show', $story->id)
                ];
            });

        // Combine moderation queue
        $moderationQueue = $recentPendingComments->merge($recentPendingStories)
            ->sortBy('created_at')
            ->take(10)
            ->values();

        // Moderation activity over time (last 30 days)
        $moderationActivity = collect(range(0, 29))->map(function($day) {
            $date = now()->subDays($day);
            return [
                'date' => $date->format('Y-m-d'),
                'approved' => StoryComment::where('is_approved', true)
                    ->whereDate('approved_at', $date)
                    ->count(),
                'rejected' => StoryComment::where('is_approved', false)
                    ->where('is_visible', false)
                    ->whereDate('created_at', $date)
                    ->count(),
                'pending' => StoryComment::where('is_approved', false)
                    ->whereNull('approved_at')
                    ->whereDate('created_at', $date)
                    ->count()
            ];
        })->reverse()->values();

        return [
            'comments' => [
                'pending' => $pendingComments,
                'approved' => $approvedComments,
                'rejected' => $rejectedComments,
                'total' => $totalComments,
                'approval_rate' => $approvalRate,
                'rejection_rate' => $rejectionRate
            ],
            'stories' => [
                'pending' => $pendingStories,
                'approved' => $approvedStories,
                'rejected' => $rejectedStories,
                'flagged' => $flaggedStories
            ],
            'episodes' => [
                'pending' => $pendingEpisodes
            ],
            'queue' => $moderationQueue,
            'activity' => $moderationActivity
        ];
    }

    /**
     * Get device and platform analytics
     */
    private function getDevicePlatformAnalytics($startDate, $endDate)
    {
        try {
            // Device Type Distribution (from users table)
            $deviceTypeStats = User::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('device_type, COUNT(*) as count')
                ->whereNotNull('device_type')
                ->groupBy('device_type')
                ->get()
                ->pluck('count', 'device_type')
                ->toArray();
        } catch (\Exception $e) {
            $deviceTypeStats = [];
        }

        try {
            // Platform Distribution (Android, iOS, Web)
            $platformStats = User::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    CASE
                        WHEN os LIKE "%Android%" OR device_type = "android" THEN "Android"
                        WHEN os LIKE "%iOS%" OR os LIKE "%iPhone%" OR device_type = "ios" THEN "iOS"
                        WHEN registration_source = "web" OR device_type = "desktop" THEN "Web"
                        ELSE "Unknown"
                    END as platform,
                    COUNT(*) as count
                ')
                ->groupBy('platform')
                ->get()
                ->pluck('count', 'platform')
                ->toArray();
        } catch (\Exception $e) {
            $platformStats = [];
        }

        // OS Version Distribution
        $osVersionStats = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('os, COUNT(*) as count')
            ->whereNotNull('os')
            ->where('os', '!=', '')
            ->groupBy('os')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'os' => $item->os,
                    'count' => (int)($item->count ?? 0)
                ];
            })
            ->values()
            ->toArray();

        // Browser Distribution
        $browserStats = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('browser, COUNT(*) as count')
            ->whereNotNull('browser')
            ->where('browser', '!=', '')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'browser' => $item->browser,
                    'count' => $item->count
                ];
            });

        // Check if user_devices table exists and get device data
        $deviceModelStats = [];
        $appVersionStats = [];
        $activeDevices = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('user_devices')) {
                // Device Model Distribution
                $deviceModelStats = DB::table('user_devices')
                    ->selectRaw('device_model, COUNT(*) as count')
                    ->whereNotNull('device_model')
                    ->where('device_model', '!=', '')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('device_model')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->map(function($item) {
                        return [
                            'model' => $item->device_model,
                            'count' => (int)($item->count ?? 0)
                        ];
                    })
                    ->values()
                    ->toArray();

                // App Version Distribution
                $appVersionStats = DB::table('user_devices')
                    ->selectRaw('app_version, COUNT(*) as count')
                    ->whereNotNull('app_version')
                    ->where('app_version', '!=', '')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('app_version')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->map(function($item) {
                        return [
                            'version' => $item->app_version,
                            'count' => (int)($item->count ?? 0)
                        ];
                    })
                    ->values()
                    ->toArray();

                // Active Devices (devices active in the last 30 days)
                $activeDevices = (int)DB::table('user_devices')
                    ->where('last_active', '>=', now()->subDays(30))
                    ->count();
            }
        } catch (\Exception $e) {
            // Table doesn't exist or error occurred, use defaults
            $activeDevices = 0;
        }

        // Platform-specific Engagement
        $platformEngagement = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                CASE
                    WHEN os LIKE "%Android%" OR device_type = "android" THEN "Android"
                    WHEN os LIKE "%iOS%" OR os LIKE "%iPhone%" OR device_type = "ios" THEN "iOS"
                    WHEN registration_source = "web" OR device_type = "desktop" THEN "Web"
                    ELSE "Unknown"
                END as platform,
                COUNT(DISTINCT users.id) as user_count,
                SUM(total_sessions) as total_sessions,
                SUM(total_play_time) as total_play_time,
                SUM(total_favorites) as total_favorites,
                SUM(total_ratings) as total_ratings
            ')
            ->groupBy('platform')
            ->get()
            ->map(function($item) {
                return [
                    'platform' => $item->platform,
                    'user_count' => (int)($item->user_count ?? 0),
                    'total_sessions' => (int)($item->total_sessions ?? 0),
                    'total_play_time' => (int)($item->total_play_time ?? 0),
                    'total_favorites' => (int)($item->total_favorites ?? 0),
                    'total_ratings' => (int)($item->total_ratings ?? 0),
                    'avg_sessions' => $item->user_count > 0 ? round(($item->total_sessions ?? 0) / $item->user_count, 2) : 0,
                    'avg_play_time' => $item->user_count > 0 ? round(($item->total_play_time ?? 0) / $item->user_count, 2) : 0
                ];
            })
            ->values()
            ->toArray();

        // Platform-specific Revenue
        $platformRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->join('users', 'payments.user_id', '=', 'users.id')
            ->selectRaw('
                CASE
                    WHEN users.os LIKE "%Android%" OR users.device_type = "android" THEN "Android"
                    WHEN users.os LIKE "%iOS%" OR users.os LIKE "%iPhone%" OR users.device_type = "ios" THEN "iOS"
                    WHEN users.registration_source = "web" OR users.device_type = "desktop" THEN "Web"
                    ELSE "Unknown"
                END as platform,
                SUM(payments.amount) as total_revenue,
                COUNT(DISTINCT payments.user_id) as paying_users,
                COUNT(*) as transaction_count
            ')
            ->groupBy('platform')
            ->get()
            ->map(function($item) {
                return [
                    'platform' => $item->platform,
                    'total_revenue' => (int)($item->total_revenue ?? 0),
                    'paying_users' => (int)($item->paying_users ?? 0),
                    'transaction_count' => (int)($item->transaction_count ?? 0),
                    'arpu' => $item->paying_users > 0 ? round(($item->total_revenue ?? 0) / $item->paying_users, 2) : 0
                ];
            })
            ->values()
            ->toArray();

        // Platform-specific User Retention (users active in last 7 days)
        $platformRetention = User::where('last_activity_at', '>=', now()->subDays(7))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                CASE
                    WHEN os LIKE "%Android%" OR device_type = "android" THEN "Android"
                    WHEN os LIKE "%iOS%" OR os LIKE "%iPhone%" OR device_type = "ios" THEN "iOS"
                    WHEN registration_source = "web" OR device_type = "desktop" THEN "Web"
                    ELSE "Unknown"
                END as platform,
                COUNT(*) as active_users
            ')
            ->groupBy('platform')
            ->get()
            ->pluck('active_users', 'platform')
            ->toArray();

        // Total users by platform for retention calculation
        $totalUsersByPlatform = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                CASE
                    WHEN os LIKE "%Android%" OR device_type = "android" THEN "Android"
                    WHEN os LIKE "%iOS%" OR os LIKE "%iPhone%" OR device_type = "ios" THEN "iOS"
                    WHEN registration_source = "web" OR device_type = "desktop" THEN "Web"
                    ELSE "Unknown"
                END as platform,
                COUNT(*) as total_users
            ')
            ->groupBy('platform')
            ->get()
            ->pluck('total_users', 'platform')
            ->toArray();

        // Calculate retention rates
        $retentionRates = [];
        foreach ($totalUsersByPlatform as $platform => $total) {
            $active = $platformRetention[$platform] ?? 0;
            $retentionRates[$platform] = $total > 0 ? round(($active / $total) * 100, 1) : 0;
        }

        // Platform growth over time (last 30 days)
        $platformGrowth = collect(range(0, 29))->map(function($day) {
            $date = now()->subDays($day);
            return [
                'date' => $date->format('Y-m-d'),
                'android' => User::whereDate('created_at', $date)
                    ->where(function($q) {
                        $q->where('os', 'like', '%Android%')
                          ->orWhere('device_type', 'android');
                    })
                    ->count(),
                'ios' => User::whereDate('created_at', $date)
                    ->where(function($q) {
                        $q->where('os', 'like', '%iOS%')
                          ->orWhere('os', 'like', '%iPhone%')
                          ->orWhere('device_type', 'ios');
                    })
                    ->count(),
                'web' => User::whereDate('created_at', $date)
                    ->where(function($q) {
                        $q->where('registration_source', 'web')
                          ->orWhere('device_type', 'desktop');
                    })
                    ->count()
            ];
        })->reverse()->values()->toArray();

        return [
            'device_types' => $deviceTypeStats ?? [],
            'platforms' => $platformStats ?? [],
            'os_versions' => $osVersionStats ?? [],
            'browsers' => $browserStats ?? [],
            'device_models' => $deviceModelStats ?? [],
            'app_versions' => $appVersionStats ?? [],
            'active_devices' => $activeDevices ?? 0,
            'engagement' => $platformEngagement ?? [],
            'revenue' => $platformRevenue ?? [],
            'retention_rates' => $retentionRates ?? [],
            'growth' => $platformGrowth ?? []
        ];
    }
}
