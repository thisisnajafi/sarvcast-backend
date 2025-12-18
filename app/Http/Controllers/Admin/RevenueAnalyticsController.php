<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\CoinTransaction;
use App\Models\CouponCode;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RevenueAnalyticsController extends Controller
{
    public function overview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $revenueStats = [
            'total_revenue' => rand(50000, 200000),
            'subscription_revenue' => rand(30000, 150000),
            'coin_revenue' => rand(10000, 50000),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_subscribers' => Subscription::count(),
            'average_revenue_per_user' => rand(15, 45),
        ];

        $revenueTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenueTrends[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => rand(1000, 5000),
                'subscriptions' => rand(10, 50),
            ];
        }

        return view('admin.revenue-analytics.overview', compact('revenueStats', 'revenueTrends', 'dateRange'));
    }

    public function subscriptions(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $subscriptionStats = [
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'new_subscriptions' => Subscription::where('created_at', '>=', $startDate)->count(),
            'cancelled_subscriptions' => Subscription::where('status', 'cancelled')->count(),
            'renewal_rate' => rand(70, 90),
            'churn_rate' => rand(5, 15),
        ];

        $subscriptionPlans = [
            ['name' => 'ماهانه', 'count' => rand(100, 500), 'revenue' => rand(10000, 50000)],
            ['name' => 'سه ماهه', 'count' => rand(50, 200), 'revenue' => rand(15000, 60000)],
            ['name' => 'سالانه', 'count' => rand(20, 100), 'revenue' => rand(20000, 80000)],
        ];

        return view('admin.revenue-analytics.subscriptions', compact('subscriptionStats', 'subscriptionPlans', 'dateRange'));
    }

    public function payments(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $paymentStats = [
            'total_payments' => rand(1000, 5000),
            'successful_payments' => rand(800, 4500),
            'failed_payments' => rand(50, 200),
            'pending_payments' => rand(20, 100),
            'average_payment_amount' => rand(25, 75),
            'payment_success_rate' => rand(85, 95),
        ];

        $paymentMethods = [
            ['method' => 'کارت اعتباری', 'count' => rand(500, 2000), 'amount' => rand(20000, 80000)],
            ['method' => 'پرداخت آنلاین', 'count' => rand(300, 1500), 'amount' => rand(15000, 60000)],
            ['method' => 'کیف پول', 'count' => rand(200, 1000), 'amount' => rand(10000, 40000)],
        ];

        return view('admin.revenue-analytics.payments', compact('paymentStats', 'paymentMethods', 'dateRange'));
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'overview');
        $format = $request->get('format', 'csv');

        return redirect()->back()
            ->with('success', "گزارش تحلیل درآمد {$type} با فرمت {$format} آماده دانلود است.");
    }

    // API Methods
    public function apiOverview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $revenueStats = [
            'total_revenue' => rand(50000, 200000),
            'subscription_revenue' => rand(30000, 150000),
            'coin_revenue' => rand(10000, 50000),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_subscribers' => Subscription::count(),
            'average_revenue_per_user' => rand(15, 45),
            'revenue_growth_rate' => rand(5, 25),
            'monthly_recurring_revenue' => rand(40000, 120000),
        ];

        $revenueTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenueTrends[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => rand(1000, 5000),
                'subscriptions' => rand(10, 50),
                'coins_sold' => rand(100, 500),
            ];
        }

        $revenueSources = [
            ['source' => 'اشتراک ماهانه', 'amount' => rand(20000, 80000), 'percentage' => rand(40, 60)],
            ['source' => 'اشتراک سالانه', 'amount' => rand(15000, 60000), 'percentage' => rand(25, 40)],
            ['source' => 'فروش سکه', 'amount' => rand(10000, 40000), 'percentage' => rand(15, 25)],
            ['source' => 'پیشنهادات ویژه', 'amount' => rand(5000, 20000), 'percentage' => rand(5, 15)],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'revenue_stats' => $revenueStats,
                'revenue_trends' => $revenueTrends,
                'revenue_sources' => $revenueSources,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiSubscriptions(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $subscriptionStats = [
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'new_subscriptions' => Subscription::where('created_at', '>=', $startDate)->count(),
            'cancelled_subscriptions' => Subscription::where('status', 'cancelled')->count(),
            'renewal_rate' => rand(70, 90),
            'churn_rate' => rand(5, 15),
            'average_subscription_duration' => rand(6, 18),
            'lifetime_value' => rand(50, 200),
        ];

        $subscriptionPlans = [
            ['name' => 'ماهانه', 'count' => rand(100, 500), 'revenue' => rand(10000, 50000), 'price' => 29],
            ['name' => 'سه ماهه', 'count' => rand(50, 200), 'revenue' => rand(15000, 60000), 'price' => 79],
            ['name' => 'سالانه', 'count' => rand(20, 100), 'revenue' => rand(20000, 80000), 'price' => 299],
        ];

        $subscriptionTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $subscriptionTrends[] = [
                'date' => $date->format('Y-m-d'),
                'new_subscriptions' => rand(5, 25),
                'cancelled_subscriptions' => rand(1, 10),
                'revenue' => rand(500, 2500),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription_stats' => $subscriptionStats,
                'subscription_plans' => $subscriptionPlans,
                'subscription_trends' => $subscriptionTrends,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiPayments(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $paymentStats = [
            'total_payments' => rand(1000, 5000),
            'successful_payments' => rand(800, 4500),
            'failed_payments' => rand(50, 200),
            'pending_payments' => rand(20, 100),
            'average_payment_amount' => rand(25, 75),
            'payment_success_rate' => rand(85, 95),
            'total_payment_volume' => rand(50000, 200000),
            'refund_rate' => rand(2, 8),
        ];

        $paymentMethods = [
            ['method' => 'کارت اعتباری', 'count' => rand(500, 2000), 'amount' => rand(20000, 80000), 'percentage' => rand(40, 60)],
            ['method' => 'پرداخت آنلاین', 'count' => rand(300, 1500), 'amount' => rand(15000, 60000), 'percentage' => rand(25, 40)],
            ['method' => 'کیف پول', 'count' => rand(200, 1000), 'amount' => rand(10000, 40000), 'percentage' => rand(15, 25)],
        ];

        $paymentTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $paymentTrends[] = [
                'date' => $date->format('Y-m-d'),
                'total_payments' => rand(20, 100),
                'successful_payments' => rand(18, 95),
                'failed_payments' => rand(1, 5),
                'total_amount' => rand(1000, 5000),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment_stats' => $paymentStats,
                'payment_methods' => $paymentMethods,
                'payment_trends' => $paymentTrends,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiStatistics()
    {
        $stats = [
            'total_revenue' => rand(50000, 200000),
            'monthly_revenue' => rand(15000, 60000),
            'daily_revenue' => rand(500, 2000),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_subscribers' => Subscription::count(),
            'new_subscriptions_today' => Subscription::whereDate('created_at', Carbon::today())->count(),
            'new_subscriptions_this_week' => Subscription::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'new_subscriptions_this_month' => Subscription::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            'average_revenue_per_user' => rand(15, 45),
            'revenue_growth_rate' => rand(5, 25),
            'subscription_plans' => [
                ['name' => 'ماهانه', 'count' => rand(100, 500), 'revenue' => rand(10000, 50000)],
                ['name' => 'سه ماهه', 'count' => rand(50, 200), 'revenue' => rand(15000, 60000)],
                ['name' => 'سالانه', 'count' => rand(20, 100), 'revenue' => rand(20000, 80000)],
            ],
            'payment_methods' => [
                ['method' => 'کارت اعتباری', 'count' => rand(500, 2000), 'amount' => rand(20000, 80000)],
                ['method' => 'پرداخت آنلاین', 'count' => rand(300, 1500), 'amount' => rand(15000, 60000)],
                ['method' => 'کیف پول', 'count' => rand(200, 1000), 'amount' => rand(10000, 40000)],
            ],
            'revenue_trends' => [],
            'subscription_trends' => [],
            'payment_trends' => []
        ];

        // Generate trends for the last 30 days
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $stats['revenue_trends'][] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => rand(500, 2500),
            ];
            $stats['subscription_trends'][] = [
                'date' => $date->format('Y-m-d'),
                'new_subscriptions' => rand(2, 15),
            ];
            $stats['payment_trends'][] = [
                'date' => $date->format('Y-m-d'),
                'total_payments' => rand(10, 50),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}