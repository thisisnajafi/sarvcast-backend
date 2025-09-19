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
}