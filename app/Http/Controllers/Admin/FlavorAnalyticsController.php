<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FlavorAnalyticsController extends Controller
{
    /**
     * Display flavor analytics dashboard
     */
    public function index()
    {
        return view('admin.analytics.flavors.index');
    }

    /**
     * Get separate analytics for each flavor
     */
    public function separate(Request $request)
    {
        $flavor = $request->get('flavor', 'website');
        $dateRange = $request->get('date_range', '30'); // days
        
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate = Carbon::now();

        $analytics = $this->getFlavorAnalytics($flavor, $startDate, $endDate);

        return view('admin.analytics.flavors.separate', compact('analytics', 'flavor', 'dateRange'));
    }

    /**
     * Get combined analytics for all flavors
     */
    public function combined(Request $request)
    {
        $dateRange = $request->get('date_range', '30'); // days
        
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate = Carbon::now();

        $flavors = ['website', 'cafebazaar', 'myket'];
        $combined = [];

        foreach ($flavors as $flavor) {
            $combined[$flavor] = $this->getFlavorAnalytics($flavor, $startDate, $endDate);
        }

        // Calculate totals
        $totals = [
            'total_subscriptions' => array_sum(array_column($combined, 'total_subscriptions')),
            'active_subscriptions' => array_sum(array_column($combined, 'active_subscriptions')),
            'total_revenue' => array_sum(array_column($combined, 'total_revenue')),
            'average_revenue_per_subscription' => 0,
            'conversion_rate' => 0,
        ];

        if ($totals['total_subscriptions'] > 0) {
            $totals['average_revenue_per_subscription'] = $totals['total_revenue'] / $totals['total_subscriptions'];
        }

        return view('admin.analytics.flavors.combined', compact('combined', 'totals', 'dateRange'));
    }

    /**
     * Get comprehensive analytics
     */
    public function comprehensive(Request $request)
    {
        $dateRange = $request->get('date_range', '30'); // days
        
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate = Carbon::now();

        $flavors = ['website', 'cafebazaar', 'myket'];
        $comprehensive = [];

        // Get analytics for each flavor
        foreach ($flavors as $flavor) {
            $comprehensive[$flavor] = $this->getFlavorAnalytics($flavor, $startDate, $endDate);
        }

        // Get daily trends
        $dailyTrends = $this->getDailyTrends($startDate, $endDate);

        // Get plan distribution
        $planDistribution = $this->getPlanDistribution($startDate, $endDate);

        // Get revenue trends
        $revenueTrends = $this->getRevenueTrends($startDate, $endDate);

        // Get user acquisition by flavor
        $userAcquisition = $this->getUserAcquisitionByFlavor($startDate, $endDate);

        // Get churn rate by flavor
        $churnRates = $this->getChurnRatesByFlavor($startDate, $endDate);

        // Calculate market share
        $totalRevenue = array_sum(array_column($comprehensive, 'total_revenue'));
        $marketShare = [];
        foreach ($comprehensive as $flavor => $data) {
            $marketShare[$flavor] = $totalRevenue > 0 
                ? ($data['total_revenue'] / $totalRevenue) * 100 
                : 0;
        }

        return view('admin.analytics.flavors.comprehensive', compact(
            'comprehensive',
            'dailyTrends',
            'planDistribution',
            'revenueTrends',
            'userAcquisition',
            'churnRates',
            'marketShare',
            'dateRange'
        ));
    }

    /**
     * Get analytics for a specific flavor
     */
    private function getFlavorAnalytics(string $flavor, Carbon $startDate, Carbon $endDate): array
    {
        // Subscriptions
        $totalSubscriptions = Subscription::where('billing_platform', $flavor)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $activeSubscriptions = Subscription::where('billing_platform', $flavor)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->count();

        $expiredSubscriptions = Subscription::where('billing_platform', $flavor)
            ->where('status', 'expired')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Revenue
        $totalRevenue = Payment::whereHas('subscription', function($query) use ($flavor) {
                $query->where('billing_platform', $flavor);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $averageRevenuePerSubscription = $totalSubscriptions > 0 
            ? $totalRevenue / $totalSubscriptions 
            : 0;

        // Users
        $totalUsers = User::whereHas('subscriptions', function($query) use ($flavor) {
                $query->where('billing_platform', $flavor);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $activeUsers = User::whereHas('subscriptions', function($query) use ($flavor) {
                $query->where('billing_platform', $flavor)
                      ->where('status', 'active')
                      ->where('end_date', '>', now());
            })
            ->count();

        // Conversion rate (users with subscription / total users)
        $allUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $conversionRate = $allUsers > 0 ? ($totalUsers / $allUsers) * 100 : 0;

        // Monthly recurring revenue (MRR)
        $mrr = Subscription::where('billing_platform', $flavor)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->get()
            ->sum(function($subscription) {
                // Calculate monthly equivalent
                $days = $subscription->start_date->diffInDays($subscription->end_date);
                if ($days > 0) {
                    return ($subscription->price / $days) * 30;
                }
                return 0;
            });

        return [
            'flavor' => $flavor,
            'total_subscriptions' => $totalSubscriptions,
            'active_subscriptions' => $activeSubscriptions,
            'expired_subscriptions' => $expiredSubscriptions,
            'total_revenue' => $totalRevenue,
            'average_revenue_per_subscription' => $averageRevenuePerSubscription,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'conversion_rate' => $conversionRate,
            'mrr' => $mrr,
        ];
    }

    /**
     * Get daily trends for all flavors
     */
    private function getDailyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayData = [
                'date' => $currentDate->format('Y-m-d'),
                'website' => [
                    'subscriptions' => Subscription::where('billing_platform', 'website')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->count(),
                    'revenue' => Payment::whereHas('subscription', function($query) {
                            $query->where('billing_platform', 'website');
                        })
                        ->where('status', 'completed')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->sum('amount'),
                ],
                'cafebazaar' => [
                    'subscriptions' => Subscription::where('billing_platform', 'cafebazaar')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->count(),
                    'revenue' => Payment::whereHas('subscription', function($query) {
                            $query->where('billing_platform', 'cafebazaar');
                        })
                        ->where('status', 'completed')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->sum('amount'),
                ],
                'myket' => [
                    'subscriptions' => Subscription::where('billing_platform', 'myket')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->count(),
                    'revenue' => Payment::whereHas('subscription', function($query) {
                            $query->where('billing_platform', 'myket');
                        })
                        ->where('status', 'completed')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->sum('amount'),
                ],
            ];

            $trends[] = $dayData;
            $currentDate->addDay();
        }

        return $trends;
    }

    /**
     * Get plan distribution by flavor
     */
    private function getPlanDistribution(Carbon $startDate, Carbon $endDate): array
    {
        $distribution = [];

        $flavors = ['website', 'cafebazaar', 'myket'];
        foreach ($flavors as $flavor) {
            $distribution[$flavor] = Subscription::where('billing_platform', $flavor)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();
        }

        return $distribution;
    }

    /**
     * Get revenue trends by flavor
     */
    private function getRevenueTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];

        $flavors = ['website', 'cafebazaar', 'myket'];
        foreach ($flavors as $flavor) {
            $trends[$flavor] = Payment::whereHas('subscription', function($query) use ($flavor) {
                    $query->where('billing_platform', $flavor);
                })
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('revenue', 'date')
                ->toArray();
        }

        return $trends;
    }

    /**
     * Get user acquisition by flavor
     */
    private function getUserAcquisitionByFlavor(Carbon $startDate, Carbon $endDate): array
    {
        $acquisition = [];

        $flavors = ['website', 'cafebazaar', 'myket'];
        foreach ($flavors as $flavor) {
            $acquisition[$flavor] = User::whereHas('subscriptions', function($query) use ($flavor) {
                    $query->where('billing_platform', $flavor);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray();
        }

        return $acquisition;
    }

    /**
     * Get churn rates by flavor
     */
    private function getChurnRatesByFlavor(Carbon $startDate, Carbon $endDate): array
    {
        $churnRates = [];

        $flavors = ['website', 'cafebazaar', 'myket'];
        foreach ($flavors as $flavor) {
            $totalActive = Subscription::where('billing_platform', $flavor)
                ->where('status', 'active')
                ->where('start_date', '<=', $startDate)
                ->count();

            $churned = Subscription::where('billing_platform', $flavor)
                ->where('status', 'expired')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count();

            $churnRates[$flavor] = [
                'total_active' => $totalActive,
                'churned' => $churned,
                'churn_rate' => $totalActive > 0 ? ($churned / $totalActive) * 100 : 0,
            ];
        }

        return $churnRates;
    }

    /**
     * API endpoint for flavor analytics data
     */
    public function api(Request $request)
    {
        $type = $request->get('type', 'comprehensive'); // separate, combined, comprehensive
        $flavor = $request->get('flavor', 'website');
        $dateRange = $request->get('date_range', '30');

        $startDate = Carbon::now()->subDays($dateRange);
        $endDate = Carbon::now();

        switch ($type) {
            case 'separate':
                $data = $this->getFlavorAnalytics($flavor, $startDate, $endDate);
                break;

            case 'combined':
                $flavors = ['website', 'cafebazaar', 'myket'];
                $data = [];
                foreach ($flavors as $f) {
                    $data[$f] = $this->getFlavorAnalytics($f, $startDate, $endDate);
                }
                break;

            case 'comprehensive':
            default:
                $flavors = ['website', 'cafebazaar', 'myket'];
                $data = [
                    'flavors' => [],
                    'daily_trends' => $this->getDailyTrends($startDate, $endDate),
                    'plan_distribution' => $this->getPlanDistribution($startDate, $endDate),
                    'revenue_trends' => $this->getRevenueTrends($startDate, $endDate),
                    'user_acquisition' => $this->getUserAcquisitionByFlavor($startDate, $endDate),
                    'churn_rates' => $this->getChurnRatesByFlavor($startDate, $endDate),
                ];
                foreach ($flavors as $f) {
                    $data['flavors'][$f] = $this->getFlavorAnalytics($f, $startDate, $endDate);
                }
                $totalRevenue = array_sum(array_column($data['flavors'], 'total_revenue'));
                $data['market_share'] = [];
                foreach ($data['flavors'] as $f => $analytics) {
                    $data['market_share'][$f] = $totalRevenue > 0 
                        ? ($analytics['total_revenue'] / $totalRevenue) * 100 
                        : 0;
                }
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'date_range' => $dateRange,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
    }
}
