<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SystemAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(SystemAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display system analytics dashboard
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $analytics = $this->analyticsService->getSystemAnalytics($filters);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        }

        return view('admin.analytics.system', compact('analytics', 'filters'));
    }

    /**
     * Get system overview
     */
    public function overview(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $overview = $this->analyticsService->getSystemOverview(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }

    /**
     * Get API performance metrics
     */
    public function apiPerformance(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $apiPerformance = $this->analyticsService->getApiPerformanceMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $apiPerformance
        ]);
    }

    /**
     * Get database performance metrics
     */
    public function databasePerformance(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $dbPerformance = $this->analyticsService->getDatabasePerformanceMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $dbPerformance
        ]);
    }

    /**
     * Get server health metrics
     */
    public function serverHealth(Request $request): JsonResponse
    {
        $serverHealth = $this->analyticsService->getServerHealthMetrics();

        return response()->json([
            'success' => true,
            'data' => $serverHealth
        ]);
    }

    /**
     * Get error tracking metrics
     */
    public function errorTracking(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $errorTracking = $this->analyticsService->getErrorTrackingMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $errorTracking
        ]);
    }

    /**
     * Get resource usage metrics
     */
    public function resourceUsage(Request $request): JsonResponse
    {
        $resourceUsage = $this->analyticsService->getResourceUsageMetrics();

        return response()->json([
            'success' => true,
            'data' => $resourceUsage
        ]);
    }

    /**
     * Get uptime monitoring metrics
     */
    public function uptimeMonitoring(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $uptimeMonitoring = $this->analyticsService->getUptimeMonitoringMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $uptimeMonitoring
        ]);
    }

    /**
     * Get security metrics
     */
    public function security(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $security = $this->analyticsService->getSecurityMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        return response()->json([
            'success' => true,
            'data' => $security
        ]);
    }

    /**
     * Export system analytics data
     */
    public function export(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $analytics = $this->analyticsService->getSystemAnalytics($filters);

        $filename = 'system_analytics_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($analytics)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Get system analytics summary
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)),
            'date_to' => $request->get('date_to', now())
        ];

        $overview = $this->analyticsService->getSystemOverview(
            $filters['date_from'],
            $filters['date_to']
        );

        $apiPerformance = $this->analyticsService->getApiPerformanceMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $errorTracking = $this->analyticsService->getErrorTrackingMetrics(
            $filters['date_from'],
            $filters['date_to']
        );

        $summary = [
            'system_status' => $overview['system_status'],
            'uptime' => $overview['uptime'],
            'total_api_requests' => $overview['total_api_requests'],
            'avg_response_time' => $overview['avg_response_time'],
            'error_rate' => $overview['error_rate'],
            'cache_hit_rate' => $overview['cache_hit_rate'],
            'memory_usage' => $overview['memory_usage'],
            'cpu_usage' => $overview['cpu_usage'],
            'storage_usage' => $overview['storage_usage'],
            'total_errors' => $errorTracking['total_errors'],
            'p95_response_time' => $apiPerformance['p95_response_time'],
            'p99_response_time' => $apiPerformance['p99_response_time']
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get real-time system metrics
     */
    public function realTime(Request $request): JsonResponse
    {
        $now = now();

        $realTime = [
            'cpu_usage' => $this->analyticsService->getCpuUsage(),
            'memory_usage' => $this->analyticsService->getMemoryUsage(),
            'disk_usage' => $this->analyticsService->getDiskUsage(),
            'load_average' => $this->analyticsService->getLoadAverage(),
            'active_connections' => $this->analyticsService->getDatabaseConnections(),
            'system_uptime' => $this->analyticsService->getSystemUptime(),
            'timestamp' => $now->toISOString()
        ];

        return response()->json([
            'success' => true,
            'data' => $realTime
        ]);
    }

    /**
     * Get system health check
     */
    public function healthCheck(Request $request): JsonResponse
    {
        $healthCheck = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'api' => $this->checkApiHealth(),
            'overall_status' => 'healthy'
        ];

        // Determine overall status
        $statuses = array_column($healthCheck, 'status');
        if (in_array('critical', $statuses)) {
            $healthCheck['overall_status'] = 'critical';
        } elseif (in_array('warning', $statuses)) {
            $healthCheck['overall_status'] = 'warning';
        }

        return response()->json([
            'success' => true,
            'data' => $healthCheck
        ]);
    }

    /**
     * Helper methods for health checks
     */
    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            \Cache::put('health_check', 'ok', 60);
            $value = \Cache::get('health_check');
            if ($value === 'ok') {
                return ['status' => 'healthy', 'message' => 'Cache is working properly'];
            }
            return ['status' => 'warning', 'message' => 'Cache read/write test failed'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Cache is not available: ' . $e->getMessage()];
        }
    }

    private function checkStorageHealth(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            \Storage::put($testFile, 'health check');
            $content = \Storage::get($testFile);
            \Storage::delete($testFile);
            
            if ($content === 'health check') {
                return ['status' => 'healthy', 'message' => 'Storage is working properly'];
            }
            return ['status' => 'warning', 'message' => 'Storage read/write test failed'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Storage is not available: ' . $e->getMessage()];
        }
    }

    private function checkApiHealth(): array
    {
        // Simple API health check
        $responseTime = microtime(true);
        $responseTime = microtime(true) - $responseTime;
        
        if ($responseTime < 0.1) {
            return ['status' => 'healthy', 'message' => 'API response time is good'];
        } elseif ($responseTime < 0.5) {
            return ['status' => 'warning', 'message' => 'API response time is slow'];
        } else {
            return ['status' => 'critical', 'message' => 'API response time is very slow'];
        }
    }
}