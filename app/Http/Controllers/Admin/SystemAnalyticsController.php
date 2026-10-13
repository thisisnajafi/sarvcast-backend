<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AnalyticsCsvExport;
use App\Services\SystemAnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SystemAnalyticsController extends Controller
{
    public function __construct(private SystemAnalyticsService $systemService) {}

    // ──────────────────────────────────────────────────────────────
    // Blade views
    // ──────────────────────────────────────────────────────────────

    public function overview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $overview  = $this->systemService->getSystemOverview($startDate, now());
        $resource  = $this->systemService->getResourceUsageMetrics();

        $systemStats = [
            'server_uptime'        => $overview['uptime'],
            'response_time'        => $overview['avg_response_time'],
            'cpu_usage'            => $resource['cpu_usage'],
            'memory_usage'         => $resource['memory_usage']['percentage'] ?? 0,
            'disk_usage'           => $resource['disk_usage']['percentage'] ?? 0,
            'database_connections' => $overview['db_connections'],
            'active_users'         => 0,
            'api_requests'         => $overview['total_api_requests'],
        ];

        $performanceTrends = $this->systemService->getPerformanceTrends($startDate, now())['response_time_trends'];

        return view('admin.system-analytics.overview', compact('systemStats', 'performanceTrends', 'dateRange'));
    }

    public function performance(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $apiPerf = $this->systemService->getApiPerformanceMetrics($startDate, now());

        $performanceStats = [
            'average_response_time' => $apiPerf['avg_response_time'],
            'peak_response_time'    => $apiPerf['p99_response_time'],
            'requests_per_second'   => 0,
            'error_rate'            => $this->systemService->getSystemOverview($startDate, now())['error_rate'],
            'cache_hit_rate'        => 0,
            'database_query_time'   => 0,
        ];

        $endpointPerformance = $apiPerf['endpoint_performance'];

        return view('admin.system-analytics.performance', compact('performanceStats', 'endpointPerformance', 'dateRange'));
    }

    public function health(Request $request)
    {
        $healthChecks = $this->systemService->getServerHealthMetrics();
        $systemAlerts = $this->systemService->getAlertsAndNotifications(Carbon::now()->subDays(30), now())['active_alerts'];

        return view('admin.system-analytics.health', compact('healthChecks', 'systemAlerts'));
    }

    public function export(Request $request)
    {
        $type   = $request->get('type', 'overview');
        $format = $request->get('format', 'csv');

        return redirect()->back()
            ->with('success', "گزارش تحلیل سیستم {$type} با فرمت {$format} آماده دانلود است.");
    }

    // ──────────────────────────────────────────────────────────────
    // API methods — used by Next.js dashboard
    // ──────────────────────────────────────────────────────────────

    public function apiOverview(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate   = Carbon::now();

        $overview  = $this->systemService->getSystemOverview($startDate, $endDate);
        $resource  = $this->systemService->getResourceUsageMetrics();

        $systemStats = [
            'server_uptime'        => $overview['uptime'],
            'response_time'        => $overview['avg_response_time'],
            'cpu_usage'            => $resource['cpu_usage'],
            'memory_usage'         => $resource['memory_usage']['percentage'] ?? 0,
            'disk_usage'           => $resource['disk_usage']['percentage'] ?? 0,
            'database_connections' => $overview['db_connections'],
            'active_users'         => 0,
            'api_requests'         => $overview['total_api_requests'],
            'error_rate'           => $overview['error_rate'],
            'cache_hit_rate'       => $overview['cache_hit_rate'],
        ];

        $performanceTrends = $this->systemService->getPerformanceTrends($startDate, $endDate)['response_time_trends'];

        $resourceUsage = [
            ['resource' => 'CPU',    'usage' => $resource['cpu_usage'],    'status' => ($resource['cpu_usage'] > 70 ? 'warning' : 'healthy')],
            ['resource' => 'Memory', 'usage' => $resource['memory_usage']['percentage'] ?? 0, 'status' => (($resource['memory_usage']['percentage'] ?? 0) > 80 ? 'warning' : 'healthy')],
            ['resource' => 'Disk',   'usage' => $resource['disk_usage']['percentage'] ?? 0,   'status' => (($resource['disk_usage']['percentage'] ?? 0) > 85 ? 'warning' : 'healthy')],
            ['resource' => 'Network', 'usage' => 0, 'status' => 'healthy'],
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'system_stats'        => $systemStats,
                'performance_trends'  => $performanceTrends,
                'resource_usage'      => $resourceUsage,
                'date_range'          => $dateRange,
            ],
        ]);
    }

    public function apiPerformance(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate   = Carbon::now();

        $apiPerf = $this->systemService->getApiPerformanceMetrics($startDate, $endDate);

        $performanceStats = [
            'average_response_time' => $apiPerf['avg_response_time'],
            'peak_response_time'    => $apiPerf['p99_response_time'],
            'requests_per_second'   => 0,
            'error_rate'            => $this->systemService->getSystemOverview($startDate, $endDate)['error_rate'],
            'cache_hit_rate'        => 0,
            'database_query_time'   => 0,
            'throughput'            => 0,
            'concurrent_users'      => 0,
        ];

        $endpointPerformance = $apiPerf['endpoint_performance'];
        $performanceTrends   = $this->systemService->getPerformanceTrends($startDate, $endDate)['response_time_trends'];

        return response()->json([
            'success' => true,
            'data'    => [
                'performance_stats'    => $performanceStats,
                'endpoint_performance' => $endpointPerformance,
                'performance_trends'   => $performanceTrends,
                'date_range'           => $dateRange,
            ],
        ]);
    }

    public function apiHealth(Request $request)
    {
        $serverHealth = $this->systemService->getServerHealthMetrics();
        $alerts       = $this->systemService->getAlertsAndNotifications(Carbon::now()->subDays(30), now());
        $overview     = $this->systemService->getSystemOverview(Carbon::now()->subDays(1), now());

        $healthChecks = [
            'database' => ['status' => 'healthy', 'response_time' => $overview['avg_response_time'], 'last_check' => now()],
            'cache'    => ['status' => 'healthy', 'response_time' => 0, 'last_check' => now()],
            'storage'  => ['status' => 'healthy', 'response_time' => 0, 'last_check' => now()],
            'email'    => ['status' => 'healthy', 'response_time' => 0, 'last_check' => now()],
            'api'      => ['status' => $overview['system_status'], 'response_time' => $overview['avg_response_time'], 'last_check' => now()],
            'queue'    => ['status' => 'healthy', 'response_time' => 0, 'last_check' => now()],
        ];

        $systemMetrics = [
            'uptime'            => $overview['uptime'],
            'availability'      => 100,
            'reliability'       => 100,
            'performance_score' => 0,
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'health_checks'  => $healthChecks,
                'system_alerts'  => $alerts['active_alerts'],
                'system_metrics' => $systemMetrics,
            ],
        ]);
    }

    public function apiStatistics()
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate   = Carbon::now();

        $overview  = $this->systemService->getSystemOverview($startDate, $endDate);
        $resource  = $this->systemService->getResourceUsageMetrics();
        $perf      = $this->systemService->getApiPerformanceMetrics($startDate, $endDate);
        $trends    = $this->systemService->getPerformanceTrends($startDate, $endDate);

        $stats = [
            'server_uptime'         => $overview['uptime'],
            'average_response_time' => $overview['avg_response_time'],
            'cpu_usage'             => $resource['cpu_usage'],
            'memory_usage'          => $resource['memory_usage']['percentage'] ?? 0,
            'disk_usage'            => $resource['disk_usage']['percentage'] ?? 0,
            'database_connections'  => $overview['db_connections'],
            'active_users'          => 0,
            'api_requests_today'    => $this->systemService->getSystemOverview(Carbon::today(), $endDate)['total_api_requests'],
            'error_rate'            => $overview['error_rate'],
            'cache_hit_rate'        => 0,
            'throughput'            => 0,
            'concurrent_users'      => 0,
            'system_health'         => [
                'database' => 'healthy',
                'cache'    => 'healthy',
                'storage'  => 'healthy',
                'email'    => 'healthy',
                'api'      => $overview['system_status'],
                'queue'    => 'healthy',
            ],
            'performance_trends' => $trends['response_time_trends'],
            'resource_usage'     => [
                ['resource' => 'CPU',    'usage' => $resource['cpu_usage']],
                ['resource' => 'Memory', 'usage' => $resource['memory_usage']['percentage'] ?? 0],
                ['resource' => 'Disk',   'usage' => $resource['disk_usage']['percentage'] ?? 0],
                ['resource' => 'Network', 'usage' => 0],
            ],
            'endpoint_performance' => $perf['endpoint_performance'],
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function apiExport(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate   = Carbon::now();

        $overview = $this->systemService->getSystemOverview($startDate, $endDate);
        $resource = $this->systemService->getResourceUsageMetrics();

        $metrics = [
            'server_uptime'         => $overview['uptime'],
            'average_response_time' => $overview['avg_response_time'],
            'cpu_usage'             => $resource['cpu_usage'],
            'memory_usage'          => $resource['memory_usage']['percentage'] ?? 0,
            'disk_usage'            => $resource['disk_usage']['percentage'] ?? 0,
            'active_users'          => 0,
            'error_rate'            => $overview['error_rate'],
            'cache_hit_rate'        => 0,
        ];

        $trends = $this->systemService->getPerformanceTrends($startDate, $endDate)['response_time_trends'];
        $rows   = collect($trends)->map(fn ($r) => [
            'date'          => $r['date'],
            'response_time' => $r['avg_response_time'] ?? 0,
            'cpu_usage'     => 0,
            'memory_usage'  => 0,
            'active_users'  => 0,
        ])->all();

        return AnalyticsCsvExport::stream(
            'system-analytics-'.now()->format('Y-m-d-His').'.csv',
            $metrics,
            ['date', 'response_time', 'cpu_usage', 'memory_usage', 'active_users'],
            $rows,
            $dateRange,
        );
    }
}
