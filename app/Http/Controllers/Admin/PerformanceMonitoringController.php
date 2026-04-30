<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerformanceMetric;
use App\Models\SystemAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PerformanceMonitoringController extends Controller
{
    /**
     * Display the performance monitoring dashboard.
     */
    public function index(Request $request)
    {
        // Get current system metrics
        $currentMetrics = $this->getCurrentMetrics();
        
        // Get performance trends
        $trends = $this->getPerformanceTrends();
        
        // Get recent alerts
        $recentAlerts = SystemAlert::orderBy('created_at', 'desc')->limit(10)->get();
        
        // Get system health status
        $healthStatus = $this->getSystemHealthStatus();
        
        // Get performance statistics
        $stats = [
            'total_requests' => $this->getTotalRequests(),
            'average_response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'uptime_percentage' => $this->getUptimePercentage(),
            'active_users' => $this->getActiveUsers(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
        ];

        return view('admin.performance-monitoring.index', compact(
            'currentMetrics', 
            'trends', 
            'recentAlerts', 
            'healthStatus', 
            'stats'
        ));
    }

    /**
     * Display performance statistics.
     */
    public function statistics(Request $request)
    {
        $query = PerformanceMetric::query();

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by feature
        if ($request->filled('feature')) {
            $query->where('feature', $request->feature);
        }

        $metrics = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get statistics
        $stats = [
            'total_metrics' => PerformanceMetric::count(),
            'avg_response_time' => PerformanceMetric::avg('response_time'),
            'avg_memory_usage' => PerformanceMetric::avg('memory_usage'),
            'avg_database_queries' => PerformanceMetric::avg('database_queries'),
            'total_alerts' => SystemAlert::count(),
            'critical_alerts' => SystemAlert::where('severity', 'critical')->count(),
            'warning_alerts' => SystemAlert::where('severity', 'warning')->count(),
            'info_alerts' => SystemAlert::where('severity', 'info')->count(),
        ];

        // Get performance charts data
        $chartData = $this->getPerformanceChartData($request);

        return view('admin.performance-monitoring.statistics', compact(
            'metrics', 
            'stats', 
            'chartData'
        ));
    }

    /**
     * Display system alerts.
     */
    public function alerts(Request $request)
    {
        $query = SystemAlert::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%");
            });
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get alert statistics
        $alertStats = [
            'total' => SystemAlert::count(),
            'critical' => SystemAlert::where('severity', 'critical')->count(),
            'warning' => SystemAlert::where('severity', 'warning')->count(),
            'info' => SystemAlert::where('severity', 'info')->count(),
            'resolved' => SystemAlert::where('status', 'resolved')->count(),
            'unresolved' => SystemAlert::where('status', 'unresolved')->count(),
        ];

        return view('admin.performance-monitoring.alerts', compact('alerts', 'alertStats'));
    }

    /**
     * Show the form for creating a new alert.
     */
    public function createAlert()
    {
        return view('admin.performance-monitoring.create-alert');
    }

    /**
     * Store a newly created alert.
     */
    public function storeAlert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'severity' => 'required|in:info,warning,critical',
            'source' => 'required|string|max:255',
            'status' => 'required|in:unresolved,resolved',
            'threshold_value' => 'nullable|numeric',
            'threshold_operator' => 'nullable|in:>,<,>=,<=,=,!=',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            SystemAlert::create([
                'title' => $request->title,
                'message' => $request->message,
                'severity' => $request->severity,
                'source' => $request->source,
                'status' => $request->status,
                'threshold_value' => $request->threshold_value,
                'threshold_operator' => $request->threshold_operator,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.performance-monitoring.alerts')
                ->with('success', 'هشدار با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در ایجاد هشدار: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified alert.
     */
    public function showAlert(SystemAlert $alert)
    {
        // Get related alerts
        $relatedAlerts = SystemAlert::where('source', $alert->source)
            ->where('id', '!=', $alert->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.performance-monitoring.show-alert', compact('alert', 'relatedAlerts'));
    }

    /**
     * Resolve an alert.
     */
    public function resolveAlert(SystemAlert $alert)
    {
        try {
            $alert->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'هشدار با موفقیت حل شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حل هشدار: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified alert.
     */
    public function destroyAlert(SystemAlert $alert)
    {
        try {
            $alert->delete();
            return redirect()->route('admin.performance-monitoring.alerts')
                ->with('success', 'هشدار با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف هشدار: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on alerts.
     */
    public function bulkActionAlerts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:resolve,delete',
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'exists:system_alerts,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک هشدار را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $alerts = SystemAlert::whereIn('id', $request->alert_ids);

            switch ($request->action) {
                case 'resolve':
                    $alerts->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                        'resolved_by' => auth()->id(),
                    ]);
                    break;

                case 'delete':
                    $alerts->delete();
                    break;
            }

            DB::commit();

            $actionLabels = [
                'resolve' => 'حل',
                'delete' => 'حذف',
            ];

            return redirect()->back()
                ->with('success', 'عملیات ' . $actionLabels[$request->action] . ' با موفقیت انجام شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در انجام عملیات: ' . $e->getMessage()]);
        }
    }

    /**
     * Get current system metrics.
     */
    private function getCurrentMetrics()
    {
        return [
            'response_time' => $this->getAverageResponseTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'active_connections' => $this->getActiveConnections(),
            'queue_size' => $this->getQueueSize(),
        ];
    }

    /**
     * Get performance trends.
     */
    private function getPerformanceTrends()
    {
        $trends = [];
        
        // Get response time trends
        $trends['response_time'] = PerformanceMetric::where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'asc')
            ->get(['response_time as value', 'created_at']);
            
        // Get memory usage trends
        $trends['memory_usage'] = PerformanceMetric::where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'asc')
            ->get(['memory_usage as value', 'created_at']);
            
        // Get database queries trends
        $trends['database_queries'] = PerformanceMetric::where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'asc')
            ->get(['database_queries as value', 'created_at']);
        
        return $trends;
    }

    /**
     * Get system health status.
     */
    private function getSystemHealthStatus()
    {
        $responseTime = $this->getAverageResponseTime();
        $memoryUsage = $this->getMemoryUsage();
        $cpuUsage = $this->getCpuUsage();
        $errorRate = $this->getErrorRate();

        $status = 'healthy';
        $issues = [];

        if ($responseTime > 2000) {
            $status = 'warning';
            $issues[] = 'Response time is high';
        }

        if ($memoryUsage > 80) {
            $status = 'warning';
            $issues[] = 'Memory usage is high';
        }

        if ($cpuUsage > 80) {
            $status = 'critical';
            $issues[] = 'CPU usage is critical';
        }

        if ($errorRate > 5) {
            $status = 'critical';
            $issues[] = 'Error rate is high';
        }

        return [
            'status' => $status,
            'issues' => $issues,
        ];
    }

    /**
     * Get performance chart data.
     */
    private function getPerformanceChartData($request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : now()->subDays(7);
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : now();

        $chartData = [];

        // Get response time chart data
        $chartData['response_time'] = PerformanceMetric::whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'asc')
            ->get(['response_time as value', 'created_at']);

        // Get memory usage chart data
        $chartData['memory_usage'] = PerformanceMetric::whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'asc')
            ->get(['memory_usage as value', 'created_at']);

        // Get database queries chart data
        $chartData['database_queries'] = PerformanceMetric::whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'asc')
            ->get(['database_queries as value', 'created_at']);

        return $chartData;
    }

    /**
     * Get total requests.
     */
    private function getTotalRequests()
    {
        return Cache::remember('total_requests', 60, function () {
            return rand(10000, 50000); // Simulated data
        });
    }

    /**
     * Get average response time.
     */
    private function getAverageResponseTime()
    {
        return Cache::remember('avg_response_time', 60, function () {
            return rand(100, 500); // Simulated data in milliseconds
        });
    }

    /**
     * Get error rate.
     */
    private function getErrorRate()
    {
        return Cache::remember('error_rate', 60, function () {
            return rand(0, 10) / 100; // Simulated data as percentage
        });
    }

    /**
     * Get uptime percentage.
     */
    private function getUptimePercentage()
    {
        return Cache::remember('uptime_percentage', 60, function () {
            return rand(95, 100); // Simulated data
        });
    }

    /**
     * Get active users.
     */
    private function getActiveUsers()
    {
        return Cache::remember('active_users', 60, function () {
            return rand(100, 1000); // Simulated data
        });
    }

    /**
     * Get memory usage.
     */
    private function getMemoryUsage()
    {
        return Cache::remember('memory_usage', 60, function () {
            return rand(40, 80); // Simulated data as percentage
        });
    }

    /**
     * Get CPU usage.
     */
    private function getCpuUsage()
    {
        return Cache::remember('cpu_usage', 60, function () {
            return rand(20, 70); // Simulated data as percentage
        });
    }

    /**
     * Get disk usage.
     */
    private function getDiskUsage()
    {
        return Cache::remember('disk_usage', 60, function () {
            return rand(30, 90); // Simulated data as percentage
        });
    }

    /**
     * Get active connections.
     */
    private function getActiveConnections()
    {
        return Cache::remember('active_connections', 60, function () {
            return rand(50, 200); // Simulated data
        });
    }

    /**
     * Get queue size.
     */
    private function getQueueSize()
    {
        return Cache::remember('queue_size', 60, function () {
            return rand(0, 100); // Simulated data
        });
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = SystemAlert::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'resolved') {
                $query->where('resolved', true);
            } elseif ($request->status === 'unresolved') {
                $query->where('resolved', false);
            }
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('triggered_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('triggered_at', '<=', $request->date_to);
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'pagination' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ]
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'severity' => 'required|in:info,warning,critical',
            'type' => 'required|string|max:100',
            'metadata' => 'nullable|array',
            'resolved' => 'boolean',
            'acknowledged' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $alert = SystemAlert::create([
                'title' => $request->title,
                'message' => $request->message,
                'severity' => $request->severity,
                'type' => $request->type,
                'metadata' => $request->metadata,
                'acknowledged' => $request->boolean('acknowledged', false),
                'acknowledged_at' => $request->boolean('acknowledged', false) ? now() : null,
                'resolved' => $request->boolean('resolved', false),
                'resolved_at' => $request->boolean('resolved', false) ? now() : null,
                'triggered_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'هشدار عملکرد با موفقیت ایجاد شد.',
                'data' => $alert
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating performance alert: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد هشدار عملکرد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShow(SystemAlert $performanceAlert)
    {
        return response()->json([
            'success' => true,
            'data' => $performanceAlert
        ]);
    }

    public function apiUpdate(Request $request, SystemAlert $performanceAlert)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'severity' => 'required|in:info,warning,critical',
            'type' => 'required|string|max:100',
            'metadata' => 'nullable|array',
            'resolved' => 'boolean',
            'acknowledged' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $performanceAlert->update([
                'title' => $request->title,
                'message' => $request->message,
                'severity' => $request->severity,
                'type' => $request->type,
                'metadata' => $request->metadata,
                'acknowledged' => $request->boolean('acknowledged', $performanceAlert->acknowledged),
                'acknowledged_at' => $request->boolean('acknowledged', $performanceAlert->acknowledged) ? ($performanceAlert->acknowledged_at ?? now()) : null,
                'resolved' => $request->boolean('resolved', $performanceAlert->resolved),
                'resolved_at' => $request->boolean('resolved', $performanceAlert->resolved) ? now() : null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'هشدار عملکرد با موفقیت به‌روزرسانی شد.',
                'data' => $performanceAlert
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating performance alert: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی هشدار عملکرد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDestroy(SystemAlert $performanceAlert)
    {
        try {
            $performanceAlert->delete();

            return response()->json([
                'success' => true,
                'message' => 'هشدار عملکرد با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting performance alert: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف هشدار عملکرد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:resolve,unresolve,delete',
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'integer|exists:system_alerts,id',
        ]);

        try {
            DB::beginTransaction();

            $alertIds = $request->alert_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($alertIds as $alertId) {
                try {
                    $alert = SystemAlert::findOrFail($alertId);

                    switch ($action) {
                        case 'resolve':
                            $alert->update(['resolved' => true, 'resolved_at' => now()]);
                            break;

                        case 'unresolve':
                            $alert->update(['resolved' => false, 'resolved_at' => null]);
                            break;

                        case 'delete':
                            $alert->delete();
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for performance alert', [
                        'alert_id' => $alertId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $actionLabels = [
                'resolve' => 'حل',
                'unresolve' => 'بازگشایی',
                'delete' => 'حذف',
            ];

            $message = "عملیات {$actionLabels[$action]} روی {$successCount} هشدار عملکرد انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} هشدار ناموفق بود";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'alert_ids' => $request->alert_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiStatistics()
    {
        $stats = [
            'total_alerts' => SystemAlert::count(),
            'resolved_alerts' => SystemAlert::where('resolved', true)->count(),
            'unresolved_alerts' => SystemAlert::where('resolved', false)->count(),
            'acknowledged_alerts' => SystemAlert::where('acknowledged', true)->count(),
            'alerts_by_severity' => SystemAlert::selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->get(),
            'alerts_by_type' => SystemAlert::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'recent_alerts' => SystemAlert::orderBy('triggered_at', 'desc')->limit(10)->get(),
            'alerts_by_month' => SystemAlert::selectRaw('DATE_FORMAT(triggered_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function apiOverview(Request $request)
    {
        $overview = [
            'total_requests' => $this->getTotalRequests(),
            'average_response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'uptime_percentage' => $this->getUptimePercentage(),
            'active_users' => $this->getActiveUsers(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'active_connections' => $this->getActiveConnections(),
            'queue_size' => $this->getQueueSize(),
            'system_health' => $this->getSystemHealthStatus(),
            'performance_charts' => $this->getPerformanceChartData($request),
        ];

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }

    public function apiMetrics(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : now()->subDays(7);
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : now();

        $metrics = PerformanceMetric::whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);
    }

    public function apiHealth()
    {
        $health = $this->getSystemHealthStatus();

        return response()->json([
            'success' => true,
            'data' => $health
        ]);
    }
}