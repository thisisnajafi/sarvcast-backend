<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueAnalyticsService
{
    /**
     * Get comprehensive revenue analytics
     */
    public function getRevenueAnalytics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();
        $subscriptionType = $filters['subscription_type'] ?? null;
        $paymentMethod = $filters['payment_method'] ?? null;

        return [
            'overview' => $this->getRevenueOverview($dateFrom, $dateTo, $subscriptionType, $paymentMethod),
            'subscriptions' => $this->getSubscriptionMetrics($dateFrom, $dateTo, $subscriptionType),
            'payments' => $this->getPaymentMetrics($dateFrom, $dateTo, $paymentMethod),
            'revenue_trends' => $this->getRevenueTrends($dateFrom, $dateTo, $subscriptionType, $paymentMethod),
            'customer_metrics' => $this->getCustomerMetrics($dateFrom, $dateTo, $subscriptionType),
            'conversion_metrics' => $this->getConversionMetrics($dateFrom, $dateTo),
            'churn_analysis' => $this->getChurnAnalysis($dateFrom, $dateTo, $subscriptionType),
            'lifetime_value' => $this->getLifetimeValueMetrics($dateFrom, $dateTo, $subscriptionType),
            'refund_analysis' => $this->getRefundAnalysis($dateFrom, $dateTo, $subscriptionType, $paymentMethod),
            'forecasting' => $this->getRevenueForecasting($dateFrom, $dateTo, $subscriptionType)
        ];
    }

    /**
     * Get revenue overview metrics
     */
    public function getRevenueOverview($dateFrom, $dateTo, $subscriptionType = null, $paymentMethod = null): array
    {
        $paymentQuery = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($subscriptionType) {
            $paymentQuery->whereHas('subscription', function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            });
        }

        if ($paymentMethod) {
            $paymentQuery->where('payment_method', $paymentMethod);
        }

        $totalRevenue = $paymentQuery->sum('amount');
        $totalPayments = $paymentQuery->count();
        $avgPaymentAmount = $paymentQuery->avg('amount');
        $uniqueCustomers = $paymentQuery->distinct('user_id')->count();

        // Subscription metrics
        $subscriptionQuery = Subscription::whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($subscriptionType) {
            $subscriptionQuery->where('type', $subscriptionType);
        }

        $totalSubscriptions = $subscriptionQuery->count();
        $activeSubscriptions = Subscription::where('status', 'active')
            ->where('end_date', '>', now())
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        // Revenue by subscription type
        $revenueByType = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->join('subscriptions', 'payments.subscription_id', '=', 'subscriptions.id')
            ->selectRaw('subscriptions.type, SUM(payments.amount) as revenue, COUNT(*) as count')
            ->groupBy('subscriptions.type')
            ->get();

        // Revenue by payment method
        $revenueByMethod = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('payment_method, SUM(amount) as revenue, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();

        // Monthly recurring revenue (MRR)
        $mrr = $this->calculateMRR($dateFrom, $dateTo, $subscriptionType);

        // Annual recurring revenue (ARR)
        $arr = $mrr * 12;

        return [
            'total_revenue' => $totalRevenue,
            'total_payments' => $totalPayments,
            'avg_payment_amount' => round($avgPaymentAmount ?? 0, 2),
            'unique_customers' => $uniqueCustomers,
            'total_subscriptions' => $totalSubscriptions,
            'active_subscriptions' => $activeSubscriptions,
            'revenue_by_type' => $revenueByType,
            'revenue_by_method' => $revenueByMethod,
            'mrr' => $mrr,
            'arr' => $arr,
            'avg_revenue_per_customer' => $uniqueCustomers > 0 ? round($totalRevenue / $uniqueCustomers, 2) : 0
        ];
    }

    /**
     * Get subscription metrics
     */
    public function getSubscriptionMetrics($dateFrom, $dateTo, $subscriptionType = null): array
    {
        $query = Subscription::whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($subscriptionType) {
            $query->where('type', $subscriptionType);
        }

        // Subscription counts by status
        $subscriptionsByStatus = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Subscription counts by type
        $subscriptionsByType = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        // Subscription duration analysis
        $avgSubscriptionDuration = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->whereNotNull('end_date')
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            ->value('avg_duration');

        // Renewal rate
        $renewalRate = $this->calculateRenewalRate($dateFrom, $dateTo, $subscriptionType);

        // Trial conversion rate
        $trialConversionRate = $this->calculateTrialConversionRate($dateFrom, $dateTo, $subscriptionType);

        // Subscription growth rate
        $growthRate = $this->calculateSubscriptionGrowthRate($dateFrom, $dateTo, $subscriptionType);

        return [
            'subscriptions_by_status' => $subscriptionsByStatus,
            'subscriptions_by_type' => $subscriptionsByType,
            'avg_subscription_duration' => round($avgSubscriptionDuration ?? 0, 2),
            'renewal_rate' => $renewalRate,
            'trial_conversion_rate' => $trialConversionRate,
            'growth_rate' => $growthRate,
            'total_new_subscriptions' => $query->count(),
            'total_active_subscriptions' => Subscription::where('status', 'active')
                ->where('end_date', '>', now())
                ->when($subscriptionType, function($q) use ($subscriptionType) {
                    $q->where('type', $subscriptionType);
                })
                ->count()
        ];
    }

    /**
     * Get payment metrics
     */
    public function getPaymentMetrics($dateFrom, $dateTo, $paymentMethod = null): array
    {
        $query = Payment::whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        // Payment status distribution
        $paymentsByStatus = $query->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get();

        // Payment method distribution
        $paymentsByMethod = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->get();

        // Payment amount distribution
        $paymentAmountDistribution = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->selectRaw('
                CASE 
                    WHEN amount < 100000 THEN "کمتر از 100,000 تومان"
                    WHEN amount < 500000 THEN "100,000 - 500,000 تومان"
                    WHEN amount < 1000000 THEN "500,000 - 1,000,000 تومان"
                    ELSE "بیش از 1,000,000 تومان"
                END as amount_range,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->groupBy('amount_range')
            ->get();

        // Failed payment analysis
        $failedPayments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'failed')
            ->count();

        $failedPaymentAmount = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'failed')
            ->sum('amount');

        // Payment success rate
        $totalPayments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $successfulPayments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->count();

        $successRate = $totalPayments > 0 ? round(($successfulPayments / $totalPayments) * 100, 2) : 0;

        return [
            'payments_by_status' => $paymentsByStatus,
            'payments_by_method' => $paymentsByMethod,
            'payment_amount_distribution' => $paymentAmountDistribution,
            'failed_payments' => $failedPayments,
            'failed_payment_amount' => $failedPaymentAmount,
            'success_rate' => $successRate,
            'total_payments' => $totalPayments,
            'successful_payments' => $successfulPayments
        ];
    }

    /**
     * Get revenue trends
     */
    public function getRevenueTrends($dateFrom, $dateTo, $subscriptionType = null, $paymentMethod = null): array
    {
        // Daily revenue trends
        $dailyRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->whereHas('subscription', function($subQ) use ($subscriptionType) {
                    $subQ->where('type', $subscriptionType);
                });
            })
            ->when($paymentMethod, function($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            })
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue, COUNT(*) as payments')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Weekly revenue trends
        $weeklyRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->whereHas('subscription', function($subQ) use ($subscriptionType) {
                    $subQ->where('type', $subscriptionType);
                });
            })
            ->when($paymentMethod, function($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            })
            ->selectRaw('YEARWEEK(created_at) as week, SUM(amount) as revenue, COUNT(*) as payments')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Monthly revenue trends
        $monthlyRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->whereHas('subscription', function($subQ) use ($subscriptionType) {
                    $subQ->where('type', $subscriptionType);
                });
            })
            ->when($paymentMethod, function($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            })
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as revenue, COUNT(*) as payments')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Revenue growth rates
        $revenueGrowthRate = $this->calculateGrowthRate($dailyRevenue);
        $paymentGrowthRate = $this->calculateGrowthRate($dailyRevenue->map(function($item) {
            return (object)['count' => $item->payments];
        }));

        return [
            'daily_revenue' => $dailyRevenue,
            'weekly_revenue' => $weeklyRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'revenue_growth_rate' => $revenueGrowthRate,
            'payment_growth_rate' => $paymentGrowthRate
        ];
    }

    /**
     * Get customer metrics
     */
    public function getCustomerMetrics($dateFrom, $dateTo, $subscriptionType = null): array
    {
        // New customers
        $newCustomers = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('payments', function($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'completed')
                  ->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->count();

        // Paying customers
        $payingCustomers = User::whereHas('payments', function($q) use ($dateFrom, $dateTo) {
            $q->where('status', 'completed')
              ->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->count();

        // Customers by subscription type
        $customersByType = User::whereHas('subscriptions', function($q) use ($dateFrom, $dateTo, $subscriptionType) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            if ($subscriptionType) {
                $q->where('type', $subscriptionType);
            }
        })
        ->when($subscriptionType, function($q) use ($subscriptionType) {
            $q->whereHas('subscriptions', function($subQ) use ($subscriptionType) {
                $subQ->where('type', $subscriptionType);
            });
        })
        ->selectRaw('COUNT(*) as count')
        ->value('count');

        // Customer acquisition cost (CAC) - simplified calculation
        $cac = $this->calculateCAC($dateFrom, $dateTo);

        // Average revenue per user (ARPU)
        $arpu = $this->calculateARPU($dateFrom, $dateTo, $subscriptionType);

        // Customer lifetime value (CLV)
        $clv = $this->calculateCLV($dateFrom, $dateTo, $subscriptionType);

        return [
            'new_customers' => $newCustomers,
            'paying_customers' => $payingCustomers,
            'customers_by_type' => $customersByType,
            'cac' => $cac,
            'arpu' => $arpu,
            'clv' => $clv,
            'customer_growth_rate' => $this->calculateCustomerGrowthRate($dateFrom, $dateTo)
        ];
    }

    /**
     * Get conversion metrics
     */
    public function getConversionMetrics($dateFrom, $dateTo): array
    {
        // Trial to paid conversion
        $trialConversions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('type', 'trial')
            ->where('status', 'active')
            ->count();

        $trialToPaidConversions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('type', 'trial')
            ->whereHas('payments', function($q) {
                $q->where('status', 'completed');
            })
            ->count();

        $trialConversionRate = $trialConversions > 0 ? 
            round(($trialToPaidConversions / $trialConversions) * 100, 2) : 0;

        // Free to paid conversion
        $freeUsers = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereDoesntHave('subscriptions')
            ->count();

        $freeToPaidConversions = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereDoesntHave('subscriptions', function($q) use ($dateFrom, $dateTo) {
                $q->where('created_at', '<', $dateFrom);
            })
            ->whereHas('payments', function($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'completed')
                  ->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->count();

        $freeToPaidConversionRate = $freeUsers > 0 ? 
            round(($freeToPaidConversions / $freeUsers) * 100, 2) : 0;

        // Overall conversion funnel
        $totalUsers = User::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $usersWithSubscriptions = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('subscriptions')
            ->count();

        $subscriptionConversionRate = $totalUsers > 0 ? 
            round(($usersWithSubscriptions / $totalUsers) * 100, 2) : 0;

        return [
            'trial_conversion_rate' => $trialConversionRate,
            'free_to_paid_conversion_rate' => $freeToPaidConversionRate,
            'subscription_conversion_rate' => $subscriptionConversionRate,
            'trial_conversions' => $trialToPaidConversions,
            'free_to_paid_conversions' => $freeToPaidConversions,
            'total_conversions' => $trialToPaidConversions + $freeToPaidConversions
        ];
    }

    /**
     * Get churn analysis
     */
    public function getChurnAnalysis($dateFrom, $dateTo, $subscriptionType = null): array
    {
        // Churned subscriptions
        $churnedSubscriptions = Subscription::whereBetween('end_date', [$dateFrom, $dateTo])
            ->where('status', 'cancelled')
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        // Active subscriptions at start of period
        $activeSubscriptionsStart = Subscription::where('status', 'active')
            ->where('start_date', '<=', $dateFrom)
            ->where('end_date', '>', $dateFrom)
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        // Churn rate calculation
        $churnRate = $activeSubscriptionsStart > 0 ? 
            round(($churnedSubscriptions / $activeSubscriptionsStart) * 100, 2) : 0;

        // Churn reasons analysis
        $churnReasons = Subscription::whereBetween('end_date', [$dateFrom, $dateTo])
            ->where('status', 'cancelled')
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->selectRaw('cancellation_reason, COUNT(*) as count')
            ->groupBy('cancellation_reason')
            ->get();

        // Revenue lost due to churn
        $revenueLost = Subscription::whereBetween('end_date', [$dateFrom, $dateTo])
            ->where('status', 'cancelled')
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->join('payments', 'subscriptions.id', '=', 'payments.subscription_id')
            ->where('payments.status', 'completed')
            ->sum('payments.amount');

        return [
            'churned_subscriptions' => $churnedSubscriptions,
            'churn_rate' => $churnRate,
            'churn_reasons' => $churnReasons,
            'revenue_lost' => $revenueLost,
            'active_subscriptions_start' => $activeSubscriptionsStart
        ];
    }

    /**
     * Get lifetime value metrics
     */
    public function getLifetimeValueMetrics($dateFrom, $dateTo, $subscriptionType = null): array
    {
        // Average customer lifetime value
        $avgCLV = $this->calculateCLV($dateFrom, $dateTo, $subscriptionType);

        // CLV by subscription type
        $clvByType = [];
        $subscriptionTypes = ['monthly', 'yearly', 'trial'];
        
        foreach ($subscriptionTypes as $type) {
            $clvByType[$type] = $this->calculateCLV($dateFrom, $dateTo, $type);
        }

        // CLV distribution
        $clvDistribution = User::whereHas('payments', function($q) use ($dateFrom, $dateTo) {
            $q->where('status', 'completed')
              ->whereBetween('created_at', [$dateFrom, $dateTo]);
        })
        ->withSum(['payments as total_spent' => function($q) use ($dateFrom, $dateTo) {
            $q->where('status', 'completed')
              ->whereBetween('created_at', [$dateFrom, $dateTo]);
        }], 'amount')
        ->get()
        ->map(function($user) {
            return [
                'user_id' => $user->id,
                'total_spent' => $user->total_spent,
                'clv_category' => $this->categorizeCLV($user->total_spent)
            ];
        })
        ->groupBy('clv_category')
        ->map(function($group) {
            return $group->count();
        });

        return [
            'avg_clv' => $avgCLV,
            'clv_by_type' => $clvByType,
            'clv_distribution' => $clvDistribution
        ];
    }

    /**
     * Get refund analysis
     */
    public function getRefundAnalysis($dateFrom, $dateTo, $subscriptionType = null, $paymentMethod = null): array
    {
        $refundQuery = Payment::where('status', 'refunded')
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($subscriptionType) {
            $refundQuery->whereHas('subscription', function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            });
        }

        if ($paymentMethod) {
            $refundQuery->where('payment_method', $paymentMethod);
        }

        $totalRefunds = $refundQuery->count();
        $totalRefundAmount = $refundQuery->sum('amount');
        $avgRefundAmount = $refundQuery->avg('amount');

        // Refunds by subscription type
        $refundsByType = Payment::where('status', 'refunded')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->join('subscriptions', 'payments.subscription_id', '=', 'subscriptions.id')
            ->selectRaw('subscriptions.type, COUNT(*) as count, SUM(payments.amount) as amount')
            ->groupBy('subscriptions.type')
            ->get();

        // Refund rate
        $totalPayments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->count();

        $refundRate = $totalPayments > 0 ? 
            round(($totalRefunds / $totalPayments) * 100, 2) : 0;

        return [
            'total_refunds' => $totalRefunds,
            'total_refund_amount' => $totalRefundAmount,
            'avg_refund_amount' => round($avgRefundAmount ?? 0, 2),
            'refunds_by_type' => $refundsByType,
            'refund_rate' => $refundRate
        ];
    }

    /**
     * Get revenue forecasting
     */
    public function getRevenueForecasting($dateFrom, $dateTo, $subscriptionType = null): array
    {
        // Simple linear regression for revenue forecasting
        $historicalRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->whereHas('subscription', function($subQ) use ($subscriptionType) {
                    $subQ->where('type', $subscriptionType);
                });
            })
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate forecast for next 30 days
        $forecast = $this->calculateRevenueForecast($historicalRevenue, 30);

        // Calculate forecast for next 90 days
        $forecast90 = $this->calculateRevenueForecast($historicalRevenue, 90);

        return [
            'next_30_days_forecast' => $forecast,
            'next_90_days_forecast' => $forecast90,
            'forecast_confidence' => $this->calculateForecastConfidence($historicalRevenue),
            'historical_data_points' => $historicalRevenue->count()
        ];
    }

    /**
     * Helper methods
     */
    private function calculateMRR($dateFrom, $dateTo, $subscriptionType = null): float
    {
        $monthlyRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->whereHas('subscription', function($subQ) use ($subscriptionType) {
                    $subQ->where('type', $subscriptionType);
                });
            })
            ->whereHas('subscription', function($q) {
                $q->where('type', 'monthly');
            })
            ->sum('amount');

        $daysInPeriod = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
        $daysInMonth = 30; // Approximate

        return $daysInPeriod > 0 ? round(($monthlyRevenue / $daysInPeriod) * $daysInMonth, 2) : 0;
    }

    private function calculateRenewalRate($dateFrom, $dateTo, $subscriptionType = null): float
    {
        $expiredSubscriptions = Subscription::whereBetween('end_date', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        $renewedSubscriptions = Subscription::whereBetween('end_date', [$dateFrom, $dateTo])
            ->whereHas('payments', function($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', DB::raw('subscriptions.end_date'));
            })
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        return $expiredSubscriptions > 0 ? 
            round(($renewedSubscriptions / $expiredSubscriptions) * 100, 2) : 0;
    }

    private function calculateTrialConversionRate($dateFrom, $dateTo, $subscriptionType = null): float
    {
        $trialSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('type', 'trial')
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        $convertedTrials = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('type', 'trial')
            ->whereHas('payments', function($q) {
                $q->where('status', 'completed');
            })
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        return $trialSubscriptions > 0 ? 
            round(($convertedTrials / $trialSubscriptions) * 100, 2) : 0;
    }

    private function calculateSubscriptionGrowthRate($dateFrom, $dateTo, $subscriptionType = null): float
    {
        $currentPeriodSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        $previousPeriodStart = Carbon::parse($dateFrom)->subDays(Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)));
        $previousPeriodEnd = Carbon::parse($dateFrom);

        $previousPeriodSubscriptions = Subscription::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->count();

        return $previousPeriodSubscriptions > 0 ? 
            round((($currentPeriodSubscriptions - $previousPeriodSubscriptions) / $previousPeriodSubscriptions) * 100, 2) : 0;
    }

    private function calculateCAC($dateFrom, $dateTo): float
    {
        // Simplified CAC calculation
        $marketingCost = 0; // This would come from marketing spend data
        $newCustomers = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('payments', function($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'completed')
                  ->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->count();

        return $newCustomers > 0 ? round($marketingCost / $newCustomers, 2) : 0;
    }

    private function calculateARPU($dateFrom, $dateTo, $subscriptionType = null): float
    {
        $totalRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->whereHas('subscription', function($subQ) use ($subscriptionType) {
                    $subQ->where('type', $subscriptionType);
                });
            })
            ->sum('amount');

        $uniqueCustomers = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->whereHas('subscription', function($subQ) use ($subscriptionType) {
                    $subQ->where('type', $subscriptionType);
                });
            })
            ->distinct('user_id')
            ->count();

        return $uniqueCustomers > 0 ? round($totalRevenue / $uniqueCustomers, 2) : 0;
    }

    private function calculateCLV($dateFrom, $dateTo, $subscriptionType = null): float
    {
        $avgARPU = $this->calculateARPU($dateFrom, $dateTo, $subscriptionType);
        $avgSubscriptionDuration = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($subscriptionType, function($q) use ($subscriptionType) {
                $q->where('type', $subscriptionType);
            })
            ->whereNotNull('end_date')
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            ->value('avg_duration');

        $avgDurationMonths = $avgSubscriptionDuration ? $avgSubscriptionDuration / 30 : 1;

        return round($avgARPU * $avgDurationMonths, 2);
    }

    private function calculateCustomerGrowthRate($dateFrom, $dateTo): float
    {
        $currentPeriodCustomers = User::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('payments', function($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'completed')
                  ->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->count();

        $previousPeriodStart = Carbon::parse($dateFrom)->subDays(Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)));
        $previousPeriodEnd = Carbon::parse($dateFrom);

        $previousPeriodCustomers = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->whereHas('payments', function($q) use ($previousPeriodStart, $previousPeriodEnd) {
                $q->where('status', 'completed')
                  ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd]);
            })
            ->count();

        return $previousPeriodCustomers > 0 ? 
            round((($currentPeriodCustomers - $previousPeriodCustomers) / $previousPeriodCustomers) * 100, 2) : 0;
    }

    private function calculateGrowthRate($data): float
    {
        if ($data->count() < 2) {
            return 0;
        }

        $firstValue = $data->first()->count ?? $data->first()->revenue ?? 0;
        $lastValue = $data->last()->count ?? $data->last()->revenue ?? 0;

        if ($firstValue == 0) {
            return $lastValue > 0 ? 100 : 0;
        }

        return round((($lastValue - $firstValue) / $firstValue) * 100, 2);
    }

    private function categorizeCLV($amount): string
    {
        if ($amount < 100000) return 'کم';
        if ($amount < 500000) return 'متوسط';
        if ($amount < 1000000) return 'بالا';
        return 'خیلی بالا';
    }

    private function calculateRevenueForecast($historicalData, $days): array
    {
        // Simple linear regression implementation
        $n = $historicalData->count();
        if ($n < 2) {
            return ['forecast' => 0, 'confidence' => 0];
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;

        foreach ($historicalData as $index => $data) {
            $x = $index;
            $y = $data->revenue;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        $forecast = $slope * ($n + $days) + $intercept;

        return [
            'forecast' => max(0, round($forecast, 2)),
            'confidence' => min(100, max(0, round($n * 10, 2)))
        ];
    }

    private function calculateForecastConfidence($historicalData): float
    {
        $n = $historicalData->count();
        if ($n < 7) return 30; // Low confidence for less than a week
        if ($n < 30) return 60; // Medium confidence for less than a month
        return 85; // High confidence for more than a month
    }
}
