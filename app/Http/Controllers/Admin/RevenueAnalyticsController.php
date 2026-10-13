<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AnalyticsCsvExport;
use App\Models\Subscription;
use App\Models\CoinTransaction;
use App\Models\Payment;
use App\Services\RevenueAnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RevenueAnalyticsController extends Controller
{
    public function __construct(private RevenueAnalyticsService $revenueService) {}

    // ──────────────────────────────────────────────────────────────
    // Blade views (kept but now use real counts where trivial)
    // ──────────────────────────────────────────────────────────────

    public function overview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $overview = $this->revenueService->getRevenueOverview($startDate, now());

        $revenueStats = [
            'total_revenue'           => $overview['total_revenue'],
            'subscription_revenue'    => $this->subscriptionRevenue($overview['revenue_by_type']),
            'coin_revenue'            => $this->coinRevenue($startDate, now()),
            'active_subscriptions'    => $overview['active_subscriptions'],
            'total_subscribers'       => Subscription::count(),
            'average_revenue_per_user' => $overview['avg_revenue_per_customer'],
        ];

        $trends = $this->revenueService->getRevenueTrends($startDate, now());
        $revenueTrends = collect($trends['daily_revenue'])->map(fn ($r) => [
            'date'          => $r->date,
            'revenue'       => $r->revenue,
            'subscriptions' => $r->payments ?? 0,
        ])->values()->all();

        return view('admin.revenue-analytics.overview', compact('revenueStats', 'revenueTrends', 'dateRange'));
    }

    public function subscriptions(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $subMetrics = $this->revenueService->getSubscriptionMetrics($startDate, now());

        $subscriptionStats = [
            'total_subscriptions'       => Subscription::count(),
            'active_subscriptions'      => $subMetrics['total_active_subscriptions'],
            'new_subscriptions'         => $subMetrics['total_new_subscriptions'],
            'cancelled_subscriptions'   => Subscription::where('status', 'cancelled')->count(),
            'renewal_rate'              => $subMetrics['renewal_rate'],
            'churn_rate'                => $this->churnRateFromAnalysis($startDate, now()),
        ];

        $subscriptionPlans = collect($subMetrics['subscriptions_by_type'])->map(fn ($s) => [
            'name'    => $s->type ?? 'نامشخص',
            'count'   => $s->count,
            'revenue' => 0,
        ])->values()->all();

        return view('admin.revenue-analytics.subscriptions', compact('subscriptionStats', 'subscriptionPlans', 'dateRange'));
    }

    public function payments(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $payMetrics = $this->revenueService->getPaymentMetrics($startDate, now());

        $paymentStats = [
            'total_payments'         => $payMetrics['total_payments'],
            'successful_payments'    => $payMetrics['successful_payments'],
            'failed_payments'        => $payMetrics['failed_payments'],
            'pending_payments'       => Payment::whereBetween('created_at', [$startDate, now()])->where('status', 'pending')->count(),
            'average_payment_amount' => $this->revenueService->getRevenueOverview($startDate, now())['avg_payment_amount'],
            'payment_success_rate'   => $payMetrics['success_rate'],
        ];

        $paymentMethods = collect($payMetrics['payments_by_method'])->map(fn ($m) => [
            'method' => $m->payment_method ?? 'نامشخص',
            'count'  => $m->count,
            'amount' => $m->total_amount,
        ])->values()->all();

        return view('admin.revenue-analytics.payments', compact('paymentStats', 'paymentMethods', 'dateRange'));
    }

    public function export(Request $request)
    {
        $type   = $request->get('type', 'overview');
        $format = $request->get('format', 'csv');

        return redirect()->back()
            ->with('success', "گزارش تحلیل درآمد {$type} با فرمت {$format} آماده دانلود است.");
    }

    // ──────────────────────────────────────────────────────────────
    // API methods — used by Next.js dashboard
    // ──────────────────────────────────────────────────────────────

    public function apiOverview(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate   = Carbon::now();

        $overview  = $this->revenueService->getRevenueOverview($startDate, $endDate);
        $trends    = $this->revenueService->getRevenueTrends($startDate, $endDate);
        $churn     = $this->revenueService->getChurnAnalysis($startDate, $endDate);

        $subscriptionRevenue = $this->subscriptionRevenue($overview['revenue_by_type']);
        $coinRevenue         = $this->coinRevenue($startDate, $endDate);

        $revenueStats = [
            'total_revenue'             => $overview['total_revenue'],
            'subscription_revenue'      => $subscriptionRevenue,
            'coin_revenue'              => $coinRevenue,
            'active_subscriptions'      => $overview['active_subscriptions'],
            'total_subscribers'         => Subscription::count(),
            'average_revenue_per_user'  => $overview['avg_revenue_per_customer'],
            'revenue_growth_rate'       => $trends['revenue_growth_rate'],
            'monthly_recurring_revenue' => $overview['mrr'],
        ];

        $revenueTrends = collect($trends['daily_revenue'])->map(fn ($r) => [
            'date'          => $r->date,
            'revenue'       => (float) $r->revenue,
            'subscriptions' => (int) ($r->payments ?? 0),
            'coins_sold'    => 0,
        ])->values()->all();

        $revenueSources = $this->buildRevenueSources($overview['revenue_by_type'], $subscriptionRevenue, $coinRevenue);

        return response()->json([
            'success' => true,
            'data'    => [
                'revenue_stats'   => $revenueStats,
                'revenue_trends'  => $revenueTrends,
                'revenue_sources' => $revenueSources,
                'date_range'      => $dateRange,
            ],
        ]);
    }

    public function apiSubscriptions(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate   = Carbon::now();

        $subMetrics = $this->revenueService->getSubscriptionMetrics($startDate, $endDate);
        $churn      = $this->revenueService->getChurnAnalysis($startDate, $endDate);
        $trends     = $this->revenueService->getRevenueTrends($startDate, $endDate);

        $subscriptionStats = [
            'total_subscriptions'          => Subscription::count(),
            'active_subscriptions'         => $subMetrics['total_active_subscriptions'],
            'new_subscriptions'            => $subMetrics['total_new_subscriptions'],
            'cancelled_subscriptions'      => Subscription::where('status', 'cancelled')->count(),
            'renewal_rate'                 => $subMetrics['renewal_rate'],
            'churn_rate'                   => $churn['churn_rate'],
            'average_subscription_duration' => $subMetrics['avg_subscription_duration'],
            'lifetime_value'               => 0,
        ];

        $subscriptionPlans = collect($subMetrics['subscriptions_by_type'])->map(fn ($s) => [
            'name'    => $this->localiseSubscriptionType($s->type ?? ''),
            'count'   => (int) $s->count,
            'revenue' => 0,
            'price'   => 0,
        ])->values()->all();

        $subscriptionTrends = collect($trends['daily_revenue'])->map(fn ($r) => [
            'date'                   => $r->date,
            'new_subscriptions'      => 0,
            'cancelled_subscriptions' => 0,
            'revenue'                => (float) $r->revenue,
        ])->values()->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'subscription_stats'  => $subscriptionStats,
                'subscription_plans'  => $subscriptionPlans,
                'subscription_trends' => $subscriptionTrends,
                'date_range'          => $dateRange,
            ],
        ]);
    }

    public function apiPayments(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate   = Carbon::now();

        $overview   = $this->revenueService->getRevenueOverview($startDate, $endDate);
        $payMetrics = $this->revenueService->getPaymentMetrics($startDate, $endDate);
        $trends     = $this->revenueService->getRevenueTrends($startDate, $endDate);

        $paymentStats = [
            'total_payments'        => $payMetrics['total_payments'],
            'successful_payments'   => $payMetrics['successful_payments'],
            'failed_payments'       => $payMetrics['failed_payments'],
            'pending_payments'      => Payment::whereBetween('created_at', [$startDate, $endDate])->where('status', 'pending')->count(),
            'average_payment_amount' => $overview['avg_payment_amount'],
            'payment_success_rate'  => $payMetrics['success_rate'],
            'total_payment_volume'  => $overview['total_revenue'],
            'refund_rate'           => $this->revenueService->getRefundAnalysis($startDate, $endDate)['refund_rate'],
        ];

        $paymentMethods = collect($payMetrics['payments_by_method'])->map(fn ($m) => [
            'method'     => $m->payment_method ?? 'نامشخص',
            'count'      => (int) $m->count,
            'amount'     => (float) $m->total_amount,
            'percentage' => $payMetrics['total_payments'] > 0
                ? round(($m->count / $payMetrics['total_payments']) * 100, 1)
                : 0,
        ])->values()->all();

        $paymentTrends = collect($trends['daily_revenue'])->map(fn ($r) => [
            'date'                => $r->date,
            'total_payments'      => (int) ($r->payments ?? 0),
            'successful_payments' => (int) ($r->payments ?? 0),
            'failed_payments'     => 0,
            'total_amount'        => (float) $r->revenue,
        ])->values()->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'payment_stats'   => $paymentStats,
                'payment_methods' => $paymentMethods,
                'payment_trends'  => $paymentTrends,
                'date_range'      => $dateRange,
            ],
        ]);
    }

    public function apiStatistics()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate   = Carbon::now();
        $overview  = $this->revenueService->getRevenueOverview($startDate, $endDate);
        $subMet    = $this->revenueService->getSubscriptionMetrics($startDate, $endDate);
        $trends    = $this->revenueService->getRevenueTrends(Carbon::now()->subDays(30), $endDate);

        $stats = [
            'total_revenue'               => $this->revenueService->getRevenueOverview(Carbon::parse('2000-01-01'), $endDate)['total_revenue'],
            'monthly_revenue'             => $overview['total_revenue'],
            'daily_revenue'               => $this->revenueService->getRevenueOverview(Carbon::today(), $endDate)['total_revenue'],
            'active_subscriptions'        => $overview['active_subscriptions'],
            'total_subscribers'           => Subscription::count(),
            'new_subscriptions_today'     => Subscription::whereDate('created_at', Carbon::today())->count(),
            'new_subscriptions_this_week' => Subscription::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'new_subscriptions_this_month' => $subMet['total_new_subscriptions'],
            'average_revenue_per_user'    => $overview['avg_revenue_per_customer'],
            'revenue_growth_rate'         => $trends['revenue_growth_rate'],
            'subscription_plans'          => collect($subMet['subscriptions_by_type'])->map(fn ($s) => [
                'name'    => $this->localiseSubscriptionType($s->type ?? ''),
                'count'   => (int) $s->count,
                'revenue' => 0,
            ])->values()->all(),
            'payment_methods'      => collect($this->revenueService->getPaymentMetrics($startDate, $endDate)['payments_by_method'])->map(fn ($m) => [
                'method' => $m->payment_method ?? 'نامشخص',
                'count'  => (int) $m->count,
                'amount' => (float) $m->total_amount,
            ])->values()->all(),
            'revenue_trends'      => [],
            'subscription_trends' => [],
            'payment_trends'      => [],
        ];

        foreach ($trends['daily_revenue'] as $r) {
            $stats['revenue_trends'][]      = ['date' => $r->date, 'revenue' => (float) $r->revenue];
            $stats['subscription_trends'][] = ['date' => $r->date, 'new_subscriptions' => (int) ($r->payments ?? 0)];
            $stats['payment_trends'][]      = ['date' => $r->date, 'total_payments' => (int) ($r->payments ?? 0)];
        }

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function apiExport(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate   = Carbon::now();

        $overview = $this->revenueService->getRevenueOverview($startDate, $endDate);
        $metrics  = [
            'total_subscriptions'    => Subscription::count(),
            'active_subscriptions'   => $overview['active_subscriptions'],
            'new_subscriptions'      => $this->revenueService->getSubscriptionMetrics($startDate, $endDate)['total_new_subscriptions'],
            'cancelled_subscriptions' => Subscription::where('status', 'cancelled')->count(),
        ];

        $trends = $this->revenueService->getRevenueTrends($startDate, $endDate);
        $rows   = collect($trends['daily_revenue'])->map(fn ($r) => [
            'date'             => $r->date,
            'new_subscriptions' => (int) ($r->payments ?? 0),
        ])->all();

        return AnalyticsCsvExport::stream(
            'revenue-analytics-'.now()->format('Y-m-d-His').'.csv',
            $metrics,
            ['date', 'new_subscriptions'],
            $rows,
            $dateRange,
        );
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    private function subscriptionRevenue($revenueByType): float
    {
        return collect($revenueByType)->sum(fn ($r) => (float) ($r->revenue ?? 0));
    }

    private function coinRevenue(Carbon $from, Carbon $to): float
    {
        return (float) CoinTransaction::where('transaction_type', 'purchased')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');
    }

    private function churnRateFromAnalysis(Carbon $from, Carbon $to): float
    {
        return $this->revenueService->getChurnAnalysis($from, $to)['churn_rate'];
    }

    private function buildRevenueSources($revenueByType, float $subscriptionRevenue, float $coinRevenue): array
    {
        $total = $subscriptionRevenue + $coinRevenue;

        $sources = collect($revenueByType)->map(fn ($r) => [
            'source'     => $this->localiseSubscriptionType($r->type ?? ''),
            'amount'     => (float) ($r->revenue ?? 0),
            'percentage' => $total > 0 ? round(((float)($r->revenue ?? 0) / $total) * 100, 1) : 0,
        ])->values()->all();

        if ($coinRevenue > 0) {
            $sources[] = [
                'source'     => 'فروش سکه',
                'amount'     => $coinRevenue,
                'percentage' => $total > 0 ? round(($coinRevenue / $total) * 100, 1) : 0,
            ];
        }

        return $sources;
    }

    private function localiseSubscriptionType(string $type): string
    {
        return match ($type) {
            '1month', 'monthly'  => 'اشتراک ماهانه',
            '3months', 'quarterly' => 'اشتراک سه‌ماهه',
            '12months', 'yearly', 'annual' => 'اشتراک سالانه',
            'trial'              => 'آزمایشی',
            default              => $type ?: 'نامشخص',
        };
    }
}
