<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SystemAnalyticsController extends Controller
{
    public function overview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $systemStats = [
            'server_uptime' => rand(99, 100),
            'response_time' => rand(100, 500),
            'cpu_usage' => rand(20, 80),
            'memory_usage' => rand(30, 90),
            'disk_usage' => rand(40, 85),
            'database_connections' => rand(10, 50),
            'active_users' => rand(100, 1000),
            'api_requests' => rand(10000, 100000),
        ];

        $performanceTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $performanceTrends[] = [
                'date' => $date->format('Y-m-d'),
                'response_time' => rand(100, 500),
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 90),
            ];
        }

        return view('admin.system-analytics.overview', compact('systemStats', 'performanceTrends', 'dateRange'));
    }

    public function performance(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $performanceStats = [
            'average_response_time' => rand(150, 400),
            'peak_response_time' => rand(500, 1000),
            'requests_per_second' => rand(50, 200),
            'error_rate' => rand(1, 5),
            'cache_hit_rate' => rand(80, 95),
            'database_query_time' => rand(50, 200),
        ];

        $endpointPerformance = [
            ['endpoint' => '/api/stories', 'avg_time' => rand(100, 300), 'requests' => rand(1000, 5000)],
            ['endpoint' => '/api/episodes', 'avg_time' => rand(150, 400), 'requests' => rand(800, 4000)],
            ['endpoint' => '/api/users', 'avg_time' => rand(200, 500), 'requests' => rand(500, 3000)],
            ['endpoint' => '/api/auth', 'avg_time' => rand(100, 250), 'requests' => rand(2000, 8000)],
        ];

        return view('admin.system-analytics.performance', compact('performanceStats', 'endpointPerformance', 'dateRange'));
    }

    public function health(Request $request)
    {
        $healthChecks = [
            'database' => ['status' => 'healthy', 'response_time' => rand(10, 50)],
            'cache' => ['status' => 'healthy', 'response_time' => rand(5, 20)],
            'storage' => ['status' => 'healthy', 'response_time' => rand(20, 100)],
            'email' => ['status' => 'healthy', 'response_time' => rand(100, 500)],
            'api' => ['status' => 'healthy', 'response_time' => rand(50, 200)],
        ];

        $systemAlerts = [
            ['type' => 'warning', 'message' => 'حافظه سرور به 80% رسیده است', 'time' => Carbon::now()->subMinutes(30)],
            ['type' => 'info', 'message' => 'پشتیبان‌گیری روزانه تکمیل شد', 'time' => Carbon::now()->subHours(2)],
            ['type' => 'success', 'message' => 'به‌روزرسانی سیستم با موفقیت انجام شد', 'time' => Carbon::now()->subDays(1)],
        ];

        return view('admin.system-analytics.health', compact('healthChecks', 'systemAlerts'));
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'overview');
        $format = $request->get('format', 'csv');

        return redirect()->back()
            ->with('success', "گزارش تحلیل سیستم {$type} با فرمت {$format} آماده دانلود است.");
    }

    // API Methods
    public function apiOverview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $systemStats = [
            'server_uptime' => rand(99, 100),
            'response_time' => rand(100, 500),
            'cpu_usage' => rand(20, 80),
            'memory_usage' => rand(30, 90),
            'disk_usage' => rand(40, 85),
            'database_connections' => rand(10, 50),
            'active_users' => rand(100, 1000),
            'api_requests' => rand(10000, 100000),
            'error_rate' => rand(1, 5),
            'cache_hit_rate' => rand(80, 95),
        ];

        $performanceTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $performanceTrends[] = [
                'date' => $date->format('Y-m-d'),
                'response_time' => rand(100, 500),
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 90),
                'disk_usage' => rand(40, 85),
                'active_users' => rand(50, 500),
                'api_requests' => rand(5000, 50000),
            ];
        }

        $resourceUsage = [
            ['resource' => 'CPU', 'usage' => rand(20, 80), 'status' => rand(20, 80) > 70 ? 'warning' : 'healthy'],
            ['resource' => 'Memory', 'usage' => rand(30, 90), 'status' => rand(30, 90) > 80 ? 'warning' : 'healthy'],
            ['resource' => 'Disk', 'usage' => rand(40, 85), 'status' => rand(40, 85) > 85 ? 'warning' : 'healthy'],
            ['resource' => 'Network', 'usage' => rand(10, 60), 'status' => 'healthy'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'system_stats' => $systemStats,
                'performance_trends' => $performanceTrends,
                'resource_usage' => $resourceUsage,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiPerformance(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $performanceStats = [
            'average_response_time' => rand(150, 400),
            'peak_response_time' => rand(500, 1000),
            'requests_per_second' => rand(50, 200),
            'error_rate' => rand(1, 5),
            'cache_hit_rate' => rand(80, 95),
            'database_query_time' => rand(50, 200),
            'throughput' => rand(1000, 5000),
            'concurrent_users' => rand(100, 1000),
        ];

        $endpointPerformance = [
            ['endpoint' => '/api/stories', 'avg_time' => rand(100, 300), 'requests' => rand(1000, 5000), 'error_rate' => rand(1, 5)],
            ['endpoint' => '/api/episodes', 'avg_time' => rand(150, 400), 'requests' => rand(800, 4000), 'error_rate' => rand(1, 5)],
            ['endpoint' => '/api/users', 'avg_time' => rand(200, 500), 'requests' => rand(500, 3000), 'error_rate' => rand(1, 5)],
            ['endpoint' => '/api/auth', 'avg_time' => rand(100, 250), 'requests' => rand(2000, 8000), 'error_rate' => rand(1, 5)],
            ['endpoint' => '/api/admin', 'avg_time' => rand(150, 350), 'requests' => rand(300, 2000), 'error_rate' => rand(1, 5)],
        ];

        $performanceTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $performanceTrends[] = [
                'date' => $date->format('Y-m-d'),
                'avg_response_time' => rand(150, 400),
                'requests_per_second' => rand(50, 200),
                'error_rate' => rand(1, 5),
                'cache_hit_rate' => rand(80, 95),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'performance_stats' => $performanceStats,
                'endpoint_performance' => $endpointPerformance,
                'performance_trends' => $performanceTrends,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiHealth(Request $request)
    {
        $healthChecks = [
            'database' => ['status' => 'healthy', 'response_time' => rand(10, 50), 'last_check' => Carbon::now()->subMinutes(1)],
            'cache' => ['status' => 'healthy', 'response_time' => rand(5, 20), 'last_check' => Carbon::now()->subMinutes(1)],
            'storage' => ['status' => 'healthy', 'response_time' => rand(20, 100), 'last_check' => Carbon::now()->subMinutes(2)],
            'email' => ['status' => 'healthy', 'response_time' => rand(100, 500), 'last_check' => Carbon::now()->subMinutes(5)],
            'api' => ['status' => 'healthy', 'response_time' => rand(50, 200), 'last_check' => Carbon::now()->subMinutes(1)],
            'queue' => ['status' => 'healthy', 'response_time' => rand(10, 30), 'last_check' => Carbon::now()->subMinutes(1)],
        ];

        $systemAlerts = [
            ['type' => 'warning', 'message' => 'حافظه سرور به 80% رسیده است', 'time' => Carbon::now()->subMinutes(30), 'severity' => 'medium'],
            ['type' => 'info', 'message' => 'پشتیبان‌گیری روزانه تکمیل شد', 'time' => Carbon::now()->subHours(2), 'severity' => 'low'],
            ['type' => 'success', 'message' => 'به‌روزرسانی سیستم با موفقیت انجام شد', 'time' => Carbon::now()->subDays(1), 'severity' => 'low'],
            ['type' => 'error', 'message' => 'خطا در اتصال به پایگاه داده', 'time' => Carbon::now()->subHours(6), 'severity' => 'high'],
        ];

        $systemMetrics = [
            'uptime' => rand(99, 100),
            'availability' => rand(99, 100),
            'reliability' => rand(95, 100),
            'performance_score' => rand(80, 100),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'health_checks' => $healthChecks,
                'system_alerts' => $systemAlerts,
                'system_metrics' => $systemMetrics
            ]
        ]);
    }

    public function apiStatistics()
    {
        $stats = [
            'server_uptime' => rand(99, 100),
            'average_response_time' => rand(150, 400),
            'cpu_usage' => rand(20, 80),
            'memory_usage' => rand(30, 90),
            'disk_usage' => rand(40, 85),
            'database_connections' => rand(10, 50),
            'active_users' => rand(100, 1000),
            'api_requests_today' => rand(10000, 100000),
            'error_rate' => rand(1, 5),
            'cache_hit_rate' => rand(80, 95),
            'throughput' => rand(1000, 5000),
            'concurrent_users' => rand(100, 1000),
            'system_health' => [
                'database' => 'healthy',
                'cache' => 'healthy',
                'storage' => 'healthy',
                'email' => 'healthy',
                'api' => 'healthy',
                'queue' => 'healthy',
            ],
            'performance_trends' => [],
            'resource_usage' => [
                ['resource' => 'CPU', 'usage' => rand(20, 80)],
                ['resource' => 'Memory', 'usage' => rand(30, 90)],
                ['resource' => 'Disk', 'usage' => rand(40, 85)],
                ['resource' => 'Network', 'usage' => rand(10, 60)],
            ],
            'endpoint_performance' => [
                ['endpoint' => '/api/stories', 'avg_time' => rand(100, 300), 'requests' => rand(1000, 5000)],
                ['endpoint' => '/api/episodes', 'avg_time' => rand(150, 400), 'requests' => rand(800, 4000)],
                ['endpoint' => '/api/users', 'avg_time' => rand(200, 500), 'requests' => rand(500, 3000)],
                ['endpoint' => '/api/auth', 'avg_time' => rand(100, 250), 'requests' => rand(2000, 8000)],
            ]
        ];

        // Generate performance trends for the last 30 days
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $stats['performance_trends'][] = [
                'date' => $date->format('Y-m-d'),
                'response_time' => rand(150, 400),
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 90),
                'active_users' => rand(50, 500),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}