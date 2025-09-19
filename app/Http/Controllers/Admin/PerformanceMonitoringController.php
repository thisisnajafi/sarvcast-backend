<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PerformanceMonitoringController extends Controller
{
    protected $performanceService;

    public function __construct(PerformanceMonitoringService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Get performance dashboard data
     */
    public function dashboard(): JsonResponse
    {
        try {
            $realTimeMetrics = $this->performanceService->getRealTimeMetrics();
            $performanceStats = $this->performanceService->getPerformanceStats(null, 24);
            $recentAlerts = $this->getRecentAlerts();

            return response()->json([
                'success' => true,
                'message' => 'داده‌های داشبورد عملکرد دریافت شد',
                'data' => [
                    'real_time' => $realTimeMetrics,
                    'performance_stats' => $performanceStats,
                    'recent_alerts' => $recentAlerts,
                    'summary' => $this->generateSummary($performanceStats, $realTimeMetrics)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت داده‌های عملکرد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $feature = $request->input('feature');
            $hours = $request->input('hours', 24);
            
            $stats = $this->performanceService->getPerformanceStats($feature, $hours);

            return response()->json([
                'success' => true,
                'message' => 'آمار عملکرد دریافت شد',
                'data' => [
                    'feature' => $feature,
                    'period_hours' => $hours,
                    'statistics' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار عملکرد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time metrics
     */
    public function realTime(): JsonResponse
    {
        try {
            $metrics = $this->performanceService->getRealTimeMetrics();

            return response()->json([
                'success' => true,
                'message' => 'متریک‌های زمان واقعی دریافت شد',
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت متریک‌های زمان واقعی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate performance report
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 7);
            $report = $this->performanceService->generateReport($days);

            return response()->json([
                'success' => true,
                'message' => 'گزارش عملکرد تولید شد',
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در تولید گزارش عملکرد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean old performance data
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 30);
            $deletedCount = $this->performanceService->cleanOldData($days);

            return response()->json([
                'success' => true,
                'message' => "پاکسازی داده‌های قدیمی انجام شد",
                'data' => [
                    'deleted_records' => $deletedCount,
                    'days_cleaned' => $days
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در پاکسازی داده‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance alerts
     */
    public function alerts(): JsonResponse
    {
        try {
            $alerts = $this->getRecentAlerts();

            return response()->json([
                'success' => true,
                'message' => 'هشدارهای عملکرد دریافت شد',
                'data' => [
                    'alerts' => $alerts,
                    'total_alerts' => count($alerts)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت هشدارها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline performance metrics
     */
    public function timelineMetrics(Request $request): JsonResponse
    {
        try {
            $episodeId = $request->input('episode_id');
            $hours = $request->input('hours', 24);

            $stats = $this->performanceService->getPerformanceStats('timeline', $hours);
            
            // Filter by episode if specified
            if ($episodeId) {
                $stats = array_filter($stats, function($stat) use ($episodeId) {
                    return isset($stat->episode_id) && $stat->episode_id == $episodeId;
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'متریک‌های تایم‌لاین دریافت شد',
                'data' => [
                    'episode_id' => $episodeId,
                    'period_hours' => $hours,
                    'metrics' => array_values($stats)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت متریک‌های تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comment performance metrics
     */
    public function commentMetrics(Request $request): JsonResponse
    {
        try {
            $storyId = $request->input('story_id');
            $hours = $request->input('hours', 24);

            $stats = $this->performanceService->getPerformanceStats('comment', $hours);
            
            // Filter by story if specified
            if ($storyId) {
                $stats = array_filter($stats, function($stat) use ($storyId) {
                    return isset($stat->story_id) && $stat->story_id == $storyId;
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'متریک‌های نظرات دریافت شد',
                'data' => [
                    'story_id' => $storyId,
                    'period_hours' => $hours,
                    'metrics' => array_values($stats)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت متریک‌های نظرات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent alerts
     */
    private function getRecentAlerts(): array
    {
        $alerts = [];
        
        // Get alerts from cache (last 24 hours)
        for ($i = 0; $i < 24; $i++) {
            $timestamp = now()->subHours($i)->timestamp;
            $alertKey = "performance_alert_{$timestamp}";
            
            $alert = \Cache::get($alertKey);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Generate summary data
     */
    private function generateSummary(array $performanceStats, array $realTimeMetrics): array
    {
        $totalRequests = array_sum(array_column($performanceStats, 'total_requests'));
        $avgResponseTime = $totalRequests > 0 ? 
            array_sum(array_column($performanceStats, 'avg_response_time')) / count($performanceStats) : 0;
        $slowRequests = array_sum(array_column($performanceStats, 'slow_requests'));

        return [
            'total_requests' => $totalRequests,
            'avg_response_time' => round($avgResponseTime, 2),
            'slow_requests' => $slowRequests,
            'error_rate' => $realTimeMetrics['features']['error_rate'],
            'memory_usage' => round($realTimeMetrics['system']['memory_usage'] / 1024 / 1024, 2), // MB
            'cpu_usage' => round($realTimeMetrics['system']['cpu_usage'], 2),
            'cache_hit_ratio' => round($realTimeMetrics['cache']['hit_ratio'], 2)
        ];
    }
}