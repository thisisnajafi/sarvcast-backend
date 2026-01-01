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
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Basic Statistics
        $stats = [
            // User Statistics
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

        // Monthly Revenue Chart Data (last 12 months)
        $monthlyRevenue = Payment::where('status', 'completed')
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(amount) as total')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // User Registration Chart Data (last 30 days)
        $dailyUserRegistrations = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

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
            'planSalesData',
            'topSellingPlans'
        ));
    }

    private function calculateGrowthRate($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
