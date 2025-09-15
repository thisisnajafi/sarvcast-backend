<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RevenueAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RevenueAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(RevenueAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display revenue analytics dashboard
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'subscription_type' => $request->get('subscription_type'),
            'payment_method' => $request->get('payment_method')
        ];

        $analytics = $this->analyticsService->getRevenueAnalytics($filters);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        }

        return view('admin.analytics.revenue', compact('analytics', 'filters'));
    }

    /**
     * Get revenue overview
     */
    public function overview(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'subscription_type' => $request->get('subscription_type'),
            'payment_method' => $request->get('payment_method')
        ];

        $overview = $this->analyticsService->getRevenueOverview(
            $filters['date_from'],
            $filters['date_to'],
            $filters['subscription_type'],
            $filters['payment_method']
        );

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }

    /**
     * Get subscription metrics
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'subscription_type' => $request->get('subscription_type')
        ];

        $subscriptions = $this->analyticsService->getSubscriptionMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['subscription_type']
        );

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Get payment metrics
     */
    public function payments(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'payment_method' => $request->get('payment_method')
        ];

        $payments = $this->analyticsService->getPaymentMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['payment_method']
        );

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Get revenue trends
     */
    public function trends(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'subscription_type' => $request->get('subscription_type'),
            'payment_method' => $request->get('payment_method')
        ];

        $trends = $this->analyticsService->getRevenueTrends(
            $filters['date_from'],
            $filters['date_to'],
            $filters['subscription_type'],
            $filters['payment_method']
        );

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Get customer metrics
     */
    public function customers(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'subscription_type' => $request->get('subscription_type')
        ];

        $customers = $this->analyticsService->getCustomerMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['subscription_type']
        );

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Get conversion metrics
     */
    public function conversions(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $conversions = $this->analyticsService->getConversionMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $conversions
        ]);
    }

    /**
     * Export revenue analytics data
     */
    public function export(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'subscription_type' => $request->get('subscription_type'),
            'payment_method' => $request->get('payment_method')
        ];

        $analytics = $this->analyticsService->getRevenueAnalytics($filters);

        $filename = 'revenue_analytics_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($analytics)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Get revenue analytics summary
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $overview = $this->analyticsService->getRevenueOverview(
            $filters['date_from'],
            $filters['date_to']
        );

        $subscriptions = $this->analyticsService->getSubscriptionMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $conversions = $this->analyticsService->getConversionMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $summary = [
            'total_revenue' => $overview['total_revenue'],
            'total_payments' => $overview['total_payments'],
            'avg_payment_amount' => $overview['avg_payment_amount'],
            'unique_customers' => $overview['unique_customers'],
            'mrr' => $overview['mrr'],
            'arr' => $overview['arr'],
            'active_subscriptions' => $overview['active_subscriptions'],
            'renewal_rate' => $subscriptions['renewal_rate'],
            'trial_conversion_rate' => $subscriptions['trial_conversion_rate'],
            'growth_rate' => $subscriptions['growth_rate'],
            'trial_conversion_rate' => $conversions['trial_conversion_rate'],
            'free_to_paid_conversion_rate' => $conversions['free_to_paid_conversion_rate']
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}