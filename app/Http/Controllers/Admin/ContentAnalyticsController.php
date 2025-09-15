<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContentAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(ContentAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display content analytics dashboard
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'content_type' => $request->get('content_type'),
            'category_id' => $request->get('category_id')
        ];

        $analytics = $this->analyticsService->getContentAnalytics($filters);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        }

        return view('admin.analytics.content', compact('analytics', 'filters'));
    }

    /**
     * Get overview metrics
     */
    public function overview(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'content_type' => $request->get('content_type'),
            'category_id' => $request->get('category_id')
        ];

        $overview = $this->analyticsService->getOverviewMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['content_type'],
            $filters['category_id']
        );

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }

    /**
     * Get performance metrics
     */
    public function performance(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'content_type' => $request->get('content_type'),
            'category_id' => $request->get('category_id')
        ];

        $performance = $this->analyticsService->getPerformanceMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['content_type'],
            $filters['category_id']
        );

        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    /**
     * Get popularity metrics
     */
    public function popularity(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'content_type' => $request->get('content_type'),
            'category_id' => $request->get('category_id')
        ];

        $popularity = $this->analyticsService->getPopularityMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['content_type'],
            $filters['category_id']
        );

        return response()->json([
            'success' => true,
            'data' => $popularity
        ]);
    }

    /**
     * Get engagement metrics
     */
    public function engagement(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'content_type' => $request->get('content_type'),
            'category_id' => $request->get('category_id')
        ];

        $engagement = $this->analyticsService->getEngagementMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['content_type'],
            $filters['category_id']
        );

        return response()->json([
            'success' => true,
            'data' => $engagement
        ]);
    }

    /**
     * Get trend metrics
     */
    public function trends(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'content_type' => $request->get('content_type'),
            'category_id' => $request->get('category_id')
        ];

        $trends = $this->analyticsService->getTrendMetrics(
            $filters['date_from'],
            $filters['date_to'],
            $filters['content_type'],
            $filters['category_id']
        );

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Get category metrics
     */
    public function categories(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $categories = $this->analyticsService->getCategoryMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get creator metrics
     */
    public function creators(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $creators = $this->analyticsService->getCreatorMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $creators
        ]);
    }

    /**
     * Export content analytics data
     */
    public function export(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now()),
            'content_type' => $request->get('content_type'),
            'category_id' => $request->get('category_id')
        ];

        $analytics = $this->analyticsService->getContentAnalytics($filters);

        $filename = 'content_analytics_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($analytics)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Get content analytics summary
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

        $performance = $this->analyticsService->getPerformanceMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $summary = [
            'total_content' => $overview['total_stories'] + $overview['total_episodes'],
            'total_plays' => $overview['total_plays'],
            'total_play_time' => $overview['total_play_time'],
            'total_favorites' => $overview['total_favorites'],
            'avg_rating' => $overview['avg_rating'],
            'avg_completion_rate' => $performance['avg_completion_rate'],
            'publish_rate' => $overview['publish_rate']
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}