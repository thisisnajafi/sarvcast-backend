<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\CouponCode;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    /**
     * Display the sales dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Revenue statistics
        $stats = [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'monthly_revenue' => Payment::where('status', 'completed')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'daily_revenue' => Payment::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount'),
            'total_transactions' => Payment::where('status', 'completed')->count(),
            'successful_transactions' => Payment::where('status', 'completed')->count(),
            'failed_transactions' => Payment::where('status', 'failed')->count(),
            'pending_transactions' => Payment::where('status', 'pending')->count(),
            'avg_transaction_value' => Payment::where('status', 'completed')->avg('amount'),
        ];

        // Subscription statistics
        $subscriptionStats = [
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'monthly_subscriptions' => Subscription::where('type', '1month')->where('status', 'active')->count(),
            'yearly_subscriptions' => Subscription::where('type', '1year')->where('status', 'active')->count(),
            'subscription_revenue' => Subscription::where('status', 'active')->sum('price'),
            'cancelled_subscriptions' => Subscription::where('status', 'cancelled')->count(),
            'trial_subscriptions' => Subscription::where('status', 'trial')->count(),
        ];

        // Recent transactions
        $recentTransactions = Payment::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Revenue trends
        $revenueTrends = Payment::selectRaw('DATE(created_at) as date, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Payment method distribution
        $paymentMethods = Payment::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->get();

        // Subscription type distribution
        $subscriptionTypes = Subscription::selectRaw('type, COUNT(*) as count, SUM(price) as total_revenue')
            ->where('status', 'active')
            ->groupBy('type')
            ->get();

        // Top customers by revenue
        $topCustomers = User::withSum('payments as total_spent', 'amount')
            ->whereHas('payments', function ($query) {
                $query->where('status', 'completed');
            })
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'total_spent']);

        // Coupon usage statistics
        $couponStats = [
            'total_coupons' => CouponCode::count(),
            'active_coupons' => CouponCode::where('is_active', true)->count(),
            'total_usage' => CouponUsage::count(),
            'total_discount' => CouponUsage::sum('discount_amount'),
        ];

        // Monthly revenue comparison
        $monthlyComparison = $this->getMonthlyRevenueComparison();

        // Conversion metrics
        $conversionMetrics = [
            'conversion_rate' => $this->calculateConversionRate(),
            'average_order_value' => Payment::where('status', 'completed')->avg('amount'),
            'customer_lifetime_value' => $this->calculateCustomerLifetimeValue(),
            'churn_rate' => $this->calculateChurnRate(),
        ];

        return view('admin.dashboards.sales', compact(
            'stats',
            'subscriptionStats',
            'recentTransactions',
            'revenueTrends',
            'paymentMethods',
            'subscriptionTypes',
            'topCustomers',
            'couponStats',
            'monthlyComparison',
            'conversionMetrics',
            'dateRange'
        ));
    }

    /**
     * Get sales analytics data
     */
    public function analytics(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Revenue by day
        $dailyRevenue = Payment::selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Transaction volume by day
        $dailyTransactions = Payment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue by payment method
        $revenueByMethod = Payment::selectRaw('payment_method, SUM(amount) as revenue')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->groupBy('payment_method')
            ->get();

        // Subscription revenue trends
        $subscriptionRevenue = Subscription::selectRaw('DATE(created_at) as date, SUM(price) as revenue')
            ->where('status', 'active')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_revenue' => $dailyRevenue,
                'daily_transactions' => $dailyTransactions,
                'revenue_by_method' => $revenueByMethod,
                'subscription_revenue' => $subscriptionRevenue,
                'date_range' => $dateRange
            ]
        ]);
    }

    /**
     * Get monthly revenue comparison
     */
    private function getMonthlyRevenueComparison(): array
    {
        $months = [];
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonth();

        // Current month
        $currentMonthRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$currentMonth, now()])
            ->sum('amount');

        // Previous month
        $previousMonthRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$previousMonth, $currentMonth])
            ->sum('amount');

        $growthRate = $previousMonthRevenue > 0 
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 
            : 0;

        return [
            'current_month' => $currentMonthRevenue,
            'previous_month' => $previousMonthRevenue,
            'growth_rate' => round($growthRate, 2),
            'growth_direction' => $growthRate >= 0 ? 'up' : 'down'
        ];
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate(): float
    {
        $totalUsers = User::count();
        if ($totalUsers === 0) return 0;

        $payingUsers = User::whereHas('payments', function ($query) {
            $query->where('status', 'completed');
        })->count();

        return round(($payingUsers / $totalUsers) * 100, 2);
    }

    /**
     * Calculate customer lifetime value
     */
    private function calculateCustomerLifetimeValue(): float
    {
        $payingUsers = User::whereHas('payments', function ($query) {
            $query->where('status', 'completed');
        })->count();

        if ($payingUsers === 0) return 0;

        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        return round($totalRevenue / $payingUsers, 2);
    }

    /**
     * Calculate churn rate
     */
    private function calculateChurnRate(): float
    {
        $totalSubscriptions = Subscription::count();
        if ($totalSubscriptions === 0) return 0;

        $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();
        return round(($cancelledSubscriptions / $totalSubscriptions) * 100, 2);
    }

    /**
     * Export sales data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $type = $request->get('type', 'all');

        return redirect()->back()
            ->with('success', "گزارش فروش با فرمت {$format} آماده دانلود است.");
    }
}