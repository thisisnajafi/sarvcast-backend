<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\PlayHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('range', 30);
        $startDate = now()->subDays($dateRange);
        
        // Basic statistics
        $stats = [
            'total_users' => User::count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'subscription_rate' => $this->calculateSubscriptionRate(),
            'total_stories' => Story::count(),
            'published_stories' => Story::where('status', 'published')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'revenue_this_month' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'active_users_today' => User::whereDate('last_login_at', today())->count(),
            'active_users_this_week' => User::where('last_login_at', '>=', now()->subWeek())->count(),
            'avg_listening_time' => $this->calculateAvgListeningTime(),
            'plays_today' => PlayHistory::whereDate('played_at', today())->count(),
            'monthly_subscriptions' => Subscription::where('plan_id', 'monthly')->where('status', 'active')->count(),
            'yearly_subscriptions' => Subscription::where('plan_id', 'yearly')->where('status', 'active')->count(),
            'cancellation_rate' => $this->calculateCancellationRate(),
            'avg_revenue_per_user' => $this->calculateAvgRevenuePerUser(),
        ];

        // Top stories
        $top_stories = Story::with('category')
            ->orderBy('play_count', 'desc')
            ->limit(5)
            ->get();

        // Recent activities
        $recent_activities = PlayHistory::with(['user', 'story'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($play) {
                return (object) [
                    'user' => $play->user,
                    'story' => $play->story,
                    'action' => 'گوش دادن',
                    'created_at' => $play->created_at,
                ];
            });

        // Chart data
        $chart_data = [
            'user_growth' => $this->getUserGrowthData($startDate),
            'revenue' => $this->getRevenueData($startDate),
        ];

        return view('admin.analytics', compact('stats', 'top_stories', 'recent_activities', 'chart_data'));
    }

    /**
     * Get user growth data for chart
     */
    private function getUserGrowthData($startDate)
    {
        $data = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $counts = [];
        
        // Fill missing dates with 0
        $currentDate = $startDate->copy();
        while ($currentDate <= now()) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('m/d');
            
            $dayData = $data->where('date', $dateStr)->first();
            $counts[] = $dayData ? $dayData->count : 0;
            
            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $counts,
        ];
    }

    /**
     * Get revenue data for chart
     */
    private function getRevenueData($startDate)
    {
        $data = Payment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $amounts = [];
        
        // Fill missing dates with 0
        $currentDate = $startDate->copy();
        while ($currentDate <= now()) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('m/d');
            
            $dayData = $data->where('date', $dateStr)->first();
            $amounts[] = $dayData ? $dayData->total : 0;
            
            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $amounts,
        ];
    }

    /**
     * Calculate subscription rate
     */
    private function calculateSubscriptionRate()
    {
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        
        if ($totalUsers == 0) return 0;
        
        return round(($activeSubscriptions / $totalUsers) * 100, 2);
    }

    /**
     * Calculate average listening time
     */
    private function calculateAvgListeningTime()
    {
        $avgTime = PlayHistory::select(DB::raw('AVG(duration_played) as avg_time'))
            ->where('played_at', '>=', now()->subMonth())
            ->first();
        
        return $avgTime ? round($avgTime->avg_time / 60, 1) : 0; // Convert to minutes
    }

    /**
     * Calculate cancellation rate
     */
    private function calculateCancellationRate()
    {
        $totalSubscriptions = Subscription::count();
        $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();
        
        if ($totalSubscriptions == 0) return 0;
        
        return round(($cancelledSubscriptions / $totalSubscriptions) * 100, 2);
    }

    /**
     * Calculate average revenue per user
     */
    private function calculateAvgRevenuePerUser()
    {
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $totalUsers = User::where('role', '!=', 'admin')->count();
        
        if ($totalUsers == 0) return 0;
        
        return round($totalRevenue / $totalUsers);
    }

    /**
     * Get analytics data as JSON for API
     */
    public function getAnalyticsData(Request $request)
    {
        $dateRange = $request->get('range', 30);
        $startDate = now()->subDays($dateRange);
        
        $stats = [
            'total_users' => User::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'total_stories' => Story::count(),
        ];

        $chart_data = [
            'user_growth' => $this->getUserGrowthData($startDate),
            'revenue' => $this->getRevenueData($startDate),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'charts' => $chart_data,
            ]
        ]);
    }
}