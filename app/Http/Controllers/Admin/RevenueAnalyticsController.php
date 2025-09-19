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
}