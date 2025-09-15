<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(UserAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display user analytics dashboard
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'user_type' => $request->get('user_type'),
            'status' => $request->get('status')
        ];

        $analytics = $this->analyticsService->getUserAnalytics($filters);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        }

        return view('admin.analytics.users', compact('analytics', 'filters'));
    }

    /**
     * Get overview metrics
     */
    public function overview(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'user_type' => $request->get('user_type'),
            'status' => $request->get('status')
        ];

        $overview = $this->analyticsService->getOverviewMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['user_type'],
            $filters['status']
        );

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }

    /**
     * Get registration metrics
     */
    public function registration(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $registration = $this->analyticsService->getRegistrationMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $registration
        ]);
    }

    /**
     * Get engagement metrics
     */
    public function engagement(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $engagement = $this->analyticsService->getEngagementMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $engagement
        ]);
    }

    /**
     * Get subscription metrics
     */
    public function subscription(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $subscription = $this->analyticsService->getSubscriptionMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $subscription
        ]);
    }

    /**
     * Get activity metrics
     */
    public function activity(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $activity = $this->analyticsService->getActivityMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    /**
     * Get retention metrics
     */
    public function retention(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $retention = $this->analyticsService->getRetentionMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $retention
        ]);
    }

    /**
     * Get demographics metrics
     */
    public function demographics(): JsonResponse
    {
        $demographics = $this->analyticsService->getDemographicsMetrics();

        return response()->json([
            'success' => true,
            'data' => $demographics
        ]);
    }

    /**
     * Get behavior metrics
     */
    public function behavior(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $behavior = $this->analyticsService->getBehaviorMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $behavior
        ]);
    }

    /**
     * Get trend metrics
     */
    public function trends(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $trends = $this->analyticsService->getTrendMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Get user segments
     */
    public function segments(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $segments = $this->analyticsService->getUserSegments(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $segments
        ]);
    }

    /**
     * Get real-time metrics
     */
    public function realTime(): JsonResponse
    {
        $realTime = $this->analyticsService->getRealTimeMetrics();

        return response()->json([
            'success' => true,
            'data' => $realTime
        ]);
    }

    /**
     * Export user analytics data
     */
    public function export(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'user_type' => $request->get('user_type'),
            'status' => $request->get('status')
        ];

        $analytics = $this->analyticsService->getUserAnalytics($filters);

        $filename = 'user_analytics_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($analytics)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Get user analytics summary
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $overview = $this->analyticsService->getOverviewMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $engagement = $this->analyticsService->getEngagementMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $subscription = $this->analyticsService->getSubscriptionMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $summary = [
            'total_users' => $overview['total_users'],
            'active_users' => $overview['active_users'],
            'new_users' => $overview['new_users'],
            'engagement_rate' => $engagement['engagement_rate'],
            'subscription_rate' => $overview['subscription_rate'],
            'avg_session_duration' => $overview['avg_session_duration'],
            'churn_rate' => $subscription['churn_rate']
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}