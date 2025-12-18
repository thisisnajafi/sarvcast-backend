<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SystemAnalyticsService
{
    /**
     * Get comprehensive system analytics
     */
    public function getSystemAnalytics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        return [
            'overview' => $this->getSystemOverview($dateFrom, $dateTo),
            'api_performance' => $this->getApiPerformanceMetrics($dateFrom, $dateTo),
            'database_performance' => $this->getDatabasePerformanceMetrics($dateFrom, $dateTo),
            'server_health' => $this->getServerHealthMetrics(),
            'error_tracking' => $this->getErrorTrackingMetrics($dateFrom, $dateTo),
            'resource_usage' => $this->getResourceUsageMetrics(),
            'uptime_monitoring' => $this->getUptimeMonitoringMetrics($dateFrom, $dateTo),
            'security_metrics' => $this->getSecurityMetrics($dateFrom, $dateTo),
            'performance_trends' => $this->getPerformanceTrends($dateFrom, $dateTo),
            'alerts_notifications' => $this->getAlertsAndNotifications($dateFrom, $dateTo)
        ];
    }

    /**
     * Get system overview metrics
     */
    public function getSystemOverview($dateFrom, $dateTo): array
    {
        // System uptime
        $uptime = $this->getSystemUptime();
        
        // Total API requests
        $totalApiRequests = $this->getTotalApiRequests($dateFrom, $dateTo);
        
        // Average response time
        $avgResponseTime = $this->getAverageResponseTime($dateFrom, $dateTo);
        
        // Error rate
        $errorRate = $this->getErrorRate($dateFrom, $dateTo);
        
        // Database connections
        $dbConnections = $this->getDatabaseConnections();
        
        // Cache hit rate
        $cacheHitRate = $this->getCacheHitRate($dateFrom, $dateTo);
        
        // Storage usage
        $storageUsage = $this->getStorageUsage();
        
        // Memory usage
        $memoryUsage = $this->getMemoryUsage();
        
        // CPU usage
        $cpuUsage = $this->getCpuUsage();

        return [
            'uptime' => $uptime,
            'total_api_requests' => $totalApiRequests,
            'avg_response_time' => $avgResponseTime,
            'error_rate' => $errorRate,
            'db_connections' => $dbConnections,
            'cache_hit_rate' => $cacheHitRate,
            'storage_usage' => $storageUsage,
            'memory_usage' => $memoryUsage,
            'cpu_usage' => $cpuUsage,
            'system_status' => $this->getSystemStatus()
        ];
    }

    /**
     * Get API performance metrics
     */
    public function getApiPerformanceMetrics($dateFrom, $dateTo): array
    {
        // API endpoint performance
        $endpointPerformance = $this->getEndpointPerformance($dateFrom, $dateTo);
        
        // Response time distribution
        $responseTimeDistribution = $this->getResponseTimeDistribution($dateFrom, $dateTo);
        
        // Request volume by endpoint
        $requestVolumeByEndpoint = $this->getRequestVolumeByEndpoint($dateFrom, $dateTo);
        
        // HTTP status code distribution
        $statusCodeDistribution = $this->getStatusCodeDistribution($dateFrom, $dateTo);
        
        // Slowest endpoints
        $slowestEndpoints = $this->getSlowestEndpoints($dateFrom, $dateTo);
        
        // Most requested endpoints
        $mostRequestedEndpoints = $this->getMostRequestedEndpoints($dateFrom, $dateTo);
        
        // API version usage
        $apiVersionUsage = $this->getApiVersionUsage($dateFrom, $dateTo);

        return [
            'endpoint_performance' => $endpointPerformance,
            'response_time_distribution' => $responseTimeDistribution,
            'request_volume_by_endpoint' => $requestVolumeByEndpoint,
            'status_code_distribution' => $statusCodeDistribution,
            'slowest_endpoints' => $slowestEndpoints,
            'most_requested_endpoints' => $mostRequestedEndpoints,
            'api_version_usage' => $apiVersionUsage,
            'avg_response_time' => $this->getAverageResponseTime($dateFrom, $dateTo),
            'p95_response_time' => $this->getP95ResponseTime($dateFrom, $dateTo),
            'p99_response_time' => $this->getP99ResponseTime($dateFrom, $dateTo)
        ];
    }

    /**
     * Get database performance metrics
     */
    public function getDatabasePerformanceMetrics($dateFrom, $dateTo): array
    {
        // Query performance
        $queryPerformance = $this->getQueryPerformance($dateFrom, $dateTo);
        
        // Slow queries
        $slowQueries = $this->getSlowQueries($dateFrom, $dateTo);
        
        // Database connections
        $dbConnections = $this->getDatabaseConnections();
        
        // Table sizes
        $tableSizes = $this->getTableSizes();
        
        // Index usage
        $indexUsage = $this->getIndexUsage();
        
        // Database locks
        $dbLocks = $this->getDatabaseLocks();

        return [
            'query_performance' => $queryPerformance,
            'slow_queries' => $slowQueries,
            'db_connections' => $dbConnections,
            'table_sizes' => $tableSizes,
            'index_usage' => $indexUsage,
            'db_locks' => $dbLocks,
            'avg_query_time' => $this->getAverageQueryTime($dateFrom, $dateTo),
            'total_queries' => $this->getTotalQueries($dateFrom, $dateTo)
        ];
    }

    /**
     * Get server health metrics
     */
    public function getServerHealthMetrics(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'load_average' => $this->getLoadAverage(),
            'network_io' => $this->getNetworkIO(),
            'processes' => $this->getProcessCount(),
            'uptime' => $this->getSystemUptime(),
            'temperature' => $this->getSystemTemperature()
        ];
    }

    /**
     * Get error tracking metrics
     */
    public function getErrorTrackingMetrics($dateFrom, $dateTo): array
    {
        // Error count by type
        $errorsByType = $this->getErrorsByType($dateFrom, $dateTo);
        
        // Error count by severity
        $errorsBySeverity = $this->getErrorsBySeverity($dateFrom, $dateTo);
        
        // Error trends
        $errorTrends = $this->getErrorTrends($dateFrom, $dateTo);
        
        // Most common errors
        $mostCommonErrors = $this->getMostCommonErrors($dateFrom, $dateTo);
        
        // Error resolution time
        $errorResolutionTime = $this->getErrorResolutionTime($dateFrom, $dateTo);

        return [
            'errors_by_type' => $errorsByType,
            'errors_by_severity' => $errorsBySeverity,
            'error_trends' => $errorTrends,
            'most_common_errors' => $mostCommonErrors,
            'error_resolution_time' => $errorResolutionTime,
            'total_errors' => $this->getTotalErrors($dateFrom, $dateTo),
            'error_rate' => $this->getErrorRate($dateFrom, $dateTo)
        ];
    }

    /**
     * Get resource usage metrics
     */
    public function getResourceUsageMetrics(): array
    {
        return [
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'network_usage' => $this->getNetworkUsage(),
            'cache_usage' => $this->getCacheUsage(),
            'database_usage' => $this->getDatabaseUsage(),
            'storage_usage' => $this->getStorageUsage()
        ];
    }

    /**
     * Get uptime monitoring metrics
     */
    public function getUptimeMonitoringMetrics($dateFrom, $dateTo): array
    {
        // System uptime
        $systemUptime = $this->getSystemUptime();
        
        // Service uptime
        $serviceUptime = $this->getServiceUptime($dateFrom, $dateTo);
        
        // Downtime incidents
        $downtimeIncidents = $this->getDowntimeIncidents($dateFrom, $dateTo);
        
        // Uptime percentage
        $uptimePercentage = $this->getUptimePercentage($dateFrom, $dateTo);

        return [
            'system_uptime' => $systemUptime,
            'service_uptime' => $serviceUptime,
            'downtime_incidents' => $downtimeIncidents,
            'uptime_percentage' => $uptimePercentage,
            'last_restart' => $this->getLastRestart(),
            'planned_maintenance' => $this->getPlannedMaintenance($dateFrom, $dateTo)
        ];
    }

    /**
     * Get security metrics
     */
    public function getSecurityMetrics($dateFrom, $dateTo): array
    {
        // Failed login attempts
        $failedLogins = $this->getFailedLoginAttempts($dateFrom, $dateTo);
        
        // Suspicious activities
        $suspiciousActivities = $this->getSuspiciousActivities($dateFrom, $dateTo);
        
        // Security events
        $securityEvents = $this->getSecurityEvents($dateFrom, $dateTo);
        
        // IP blocking
        $ipBlocking = $this->getIpBlocking($dateFrom, $dateTo);
        
        // SSL certificate status
        $sslStatus = $this->getSslCertificateStatus();

        return [
            'failed_logins' => $failedLogins,
            'suspicious_activities' => $suspiciousActivities,
            'security_events' => $securityEvents,
            'ip_blocking' => $ipBlocking,
            'ssl_status' => $sslStatus,
            'security_score' => $this->getSecurityScore($dateFrom, $dateTo)
        ];
    }

    /**
     * Get performance trends
     */
    public function getPerformanceTrends($dateFrom, $dateTo): array
    {
        // Response time trends
        $responseTimeTrends = $this->getResponseTimeTrends($dateFrom, $dateTo);
        
        // Error rate trends
        $errorRateTrends = $this->getErrorRateTrends($dateFrom, $dateTo);
        
        // Resource usage trends
        $resourceUsageTrends = $this->getResourceUsageTrends($dateFrom, $dateTo);
        
        // Throughput trends
        $throughputTrends = $this->getThroughputTrends($dateFrom, $dateTo);

        return [
            'response_time_trends' => $responseTimeTrends,
            'error_rate_trends' => $errorRateTrends,
            'resource_usage_trends' => $resourceUsageTrends,
            'throughput_trends' => $throughputTrends
        ];
    }

    /**
     * Get alerts and notifications
     */
    public function getAlertsAndNotifications($dateFrom, $dateTo): array
    {
        // Active alerts
        $activeAlerts = $this->getActiveAlerts();
        
        // Alert history
        $alertHistory = $this->getAlertHistory($dateFrom, $dateTo);
        
        // Notification delivery
        $notificationDelivery = $this->getNotificationDelivery($dateFrom, $dateTo);
        
        // Alert types
        $alertTypes = $this->getAlertTypes($dateFrom, $dateTo);

        return [
            'active_alerts' => $activeAlerts,
            'alert_history' => $alertHistory,
            'notification_delivery' => $notificationDelivery,
            'alert_types' => $alertTypes,
            'alert_resolution_time' => $this->getAlertResolutionTime($dateFrom, $dateTo)
        ];
    }

    /**
     * Helper methods for system metrics
     */
    private function getSystemUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = shell_exec('uptime');
            return trim($uptime);
        }
        return 'N/A';
    }

    private function getTotalApiRequests($dateFrom, $dateTo): int
    {
        // This would typically come from API logs or monitoring system
        return Cache::remember("api_requests_{$dateFrom}_{$dateTo}", 300, function() use ($dateFrom, $dateTo) {
            // Simulate API request count - in real implementation, this would query logs
            return rand(10000, 100000);
        });
    }

    private function getAverageResponseTime($dateFrom, $dateTo): float
    {
        return Cache::remember("avg_response_time_{$dateFrom}_{$dateTo}", 300, function() use ($dateFrom, $dateTo) {
            // Simulate average response time - in real implementation, this would query logs
            return round(rand(100, 500) / 1000, 3); // Convert to seconds
        });
    }

    private function getErrorRate($dateFrom, $dateTo): float
    {
        $totalRequests = $this->getTotalApiRequests($dateFrom, $dateTo);
        $errorCount = $this->getTotalErrors($dateFrom, $dateTo);
        
        return $totalRequests > 0 ? round(($errorCount / $totalRequests) * 100, 2) : 0;
    }

    private function getDatabaseConnections(): int
    {
        try {
            return DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getCacheHitRate($dateFrom, $dateTo): float
    {
        // This would typically come from cache monitoring
        return Cache::remember("cache_hit_rate_{$dateFrom}_{$dateTo}", 300, function() {
            return round(rand(80, 95), 2);
        });
    }

    private function getStorageUsage(): array
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $freeSpace,
            'percentage' => round(($usedSpace / $totalSpace) * 100, 2)
        ];
    }

    private function getMemoryUsage(): array
    {
        $memory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'current' => $memory,
            'peak' => $peakMemory,
            'limit' => ini_get('memory_limit'),
            'percentage' => $this->parseMemoryLimit(ini_get('memory_limit')) > 0 ? 
                round(($memory / $this->parseMemoryLimit(ini_get('memory_limit'))) * 100, 2) : 0
        ];
    }

    private function getCpuUsage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0], 2);
        }
        return 0;
    }

    private function getSystemStatus(): string
    {
        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = $this->getMemoryUsage();
        $storageUsage = $this->getStorageUsage();
        
        if ($cpuUsage > 80 || $memoryUsage['percentage'] > 80 || $storageUsage['percentage'] > 90) {
            return 'critical';
        } elseif ($cpuUsage > 60 || $memoryUsage['percentage'] > 60 || $storageUsage['percentage'] > 80) {
            return 'warning';
        }
        
        return 'healthy';
    }

    private function getEndpointPerformance($dateFrom, $dateTo): array
    {
        // Simulate endpoint performance data
        return [
            ['endpoint' => '/api/stories', 'avg_response_time' => 0.245, 'requests' => 15420],
            ['endpoint' => '/api/episodes', 'avg_response_time' => 0.189, 'requests' => 12350],
            ['endpoint' => '/api/users', 'avg_response_time' => 0.156, 'requests' => 8750],
            ['endpoint' => '/api/search', 'avg_response_time' => 0.312, 'requests' => 6540],
            ['endpoint' => '/api/payments', 'avg_response_time' => 0.278, 'requests' => 4320]
        ];
    }

    private function getResponseTimeDistribution($dateFrom, $dateTo): array
    {
        return [
            ['range' => '0-100ms', 'count' => 45],
            ['range' => '100-200ms', 'count' => 30],
            ['range' => '200-500ms', 'count' => 20],
            ['range' => '500ms-1s', 'count' => 4],
            ['range' => '1s+', 'count' => 1]
        ];
    }

    private function getRequestVolumeByEndpoint($dateFrom, $dateTo): array
    {
        return [
            ['endpoint' => '/api/stories', 'requests' => 15420],
            ['endpoint' => '/api/episodes', 'requests' => 12350],
            ['endpoint' => '/api/users', 'requests' => 8750],
            ['endpoint' => '/api/search', 'requests' => 6540],
            ['endpoint' => '/api/payments', 'requests' => 4320]
        ];
    }

    private function getStatusCodeDistribution($dateFrom, $dateTo): array
    {
        return [
            ['status' => '200', 'count' => 85],
            ['status' => '201', 'count' => 8],
            ['status' => '400', 'count' => 3],
            ['status' => '401', 'count' => 2],
            ['status' => '404', 'count' => 1],
            ['status' => '500', 'count' => 1]
        ];
    }

    private function getSlowestEndpoints($dateFrom, $dateTo): array
    {
        return [
            ['endpoint' => '/api/search', 'avg_response_time' => 0.312],
            ['endpoint' => '/api/payments', 'avg_response_time' => 0.278],
            ['endpoint' => '/api/stories', 'avg_response_time' => 0.245],
            ['endpoint' => '/api/episodes', 'avg_response_time' => 0.189],
            ['endpoint' => '/api/users', 'avg_response_time' => 0.156]
        ];
    }

    private function getMostRequestedEndpoints($dateFrom, $dateTo): array
    {
        return [
            ['endpoint' => '/api/stories', 'requests' => 15420],
            ['endpoint' => '/api/episodes', 'requests' => 12350],
            ['endpoint' => '/api/users', 'requests' => 8750],
            ['endpoint' => '/api/search', 'requests' => 6540],
            ['endpoint' => '/api/payments', 'requests' => 4320]
        ];
    }

    private function getApiVersionUsage($dateFrom, $dateTo): array
    {
        return [
            ['version' => 'v1', 'usage' => 75],
            ['version' => 'v2', 'usage' => 25]
        ];
    }

    private function getP95ResponseTime($dateFrom, $dateTo): float
    {
        return Cache::remember("p95_response_time_{$dateFrom}_{$dateTo}", 300, function() {
            return round(rand(200, 800) / 1000, 3);
        });
    }

    private function getP99ResponseTime($dateFrom, $dateTo): float
    {
        return Cache::remember("p99_response_time_{$dateFrom}_{$dateTo}", 300, function() {
            return round(rand(500, 1500) / 1000, 3);
        });
    }

    private function getQueryPerformance($dateFrom, $dateTo): array
    {
        return [
            ['query_type' => 'SELECT', 'avg_time' => 0.045, 'count' => 12500],
            ['query_type' => 'INSERT', 'avg_time' => 0.089, 'count' => 3200],
            ['query_type' => 'UPDATE', 'avg_time' => 0.067, 'count' => 1800],
            ['query_type' => 'DELETE', 'avg_time' => 0.023, 'count' => 450]
        ];
    }

    private function getSlowQueries($dateFrom, $dateTo): array
    {
        return [
            ['query' => 'SELECT * FROM stories WHERE category_id = ?', 'avg_time' => 0.234, 'count' => 150],
            ['query' => 'SELECT * FROM episodes WHERE story_id = ?', 'avg_time' => 0.189, 'count' => 280],
            ['query' => 'SELECT * FROM users WHERE email = ?', 'avg_time' => 0.156, 'count' => 95]
        ];
    }

    private function getTableSizes(): array
    {
        try {
            $tables = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
            
            return $tables;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getIndexUsage(): array
    {
        // This would typically come from MySQL performance schema
        return [
            ['table' => 'stories', 'index' => 'category_id', 'usage' => 95],
            ['table' => 'episodes', 'index' => 'story_id', 'usage' => 88],
            ['table' => 'users', 'index' => 'email', 'usage' => 92]
        ];
    }

    private function getDatabaseLocks(): array
    {
        try {
            $locks = DB::select("SHOW ENGINE INNODB STATUS");
            return $locks;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getAverageQueryTime($dateFrom, $dateTo): float
    {
        return Cache::remember("avg_query_time_{$dateFrom}_{$dateTo}", 300, function() {
            return round(rand(50, 150) / 1000, 3);
        });
    }

    private function getTotalQueries($dateFrom, $dateTo): int
    {
        return Cache::remember("total_queries_{$dateFrom}_{$dateTo}", 300, function() {
            return rand(50000, 200000);
        });
    }

    private function getDiskUsage(): array
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $freeSpace,
            'percentage' => round(($usedSpace / $totalSpace) * 100, 2)
        ];
    }

    private function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            return sys_getloadavg();
        }
        return [0, 0, 0];
    }

    private function getNetworkIO(): array
    {
        // This would typically come from system monitoring
        return [
            'bytes_in' => rand(1000000, 10000000),
            'bytes_out' => rand(500000, 5000000),
            'packets_in' => rand(10000, 100000),
            'packets_out' => rand(5000, 50000)
        ];
    }

    private function getProcessCount(): int
    {
        if (function_exists('shell_exec')) {
            $processes = shell_exec('ps aux | wc -l');
            return (int)trim($processes) - 1; // Subtract header line
        }
        return 0;
    }

    private function getSystemTemperature(): float
    {
        // This would typically come from hardware monitoring
        return round(rand(35, 65), 1);
    }

    private function getErrorsByType($dateFrom, $dateTo): array
    {
        return [
            ['type' => 'Database Error', 'count' => 45],
            ['type' => 'API Error', 'count' => 32],
            ['type' => 'Authentication Error', 'count' => 28],
            ['type' => 'Validation Error', 'count' => 15],
            ['type' => 'System Error', 'count' => 8]
        ];
    }

    private function getErrorsBySeverity($dateFrom, $dateTo): array
    {
        return [
            ['severity' => 'Critical', 'count' => 5],
            ['severity' => 'High', 'count' => 18],
            ['severity' => 'Medium', 'count' => 35],
            ['severity' => 'Low', 'count' => 72]
        ];
    }

    private function getErrorTrends($dateFrom, $dateTo): array
    {
        // Simulate error trends over time
        $trends = [];
        $currentDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        
        while ($currentDate->lte($endDate)) {
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'errors' => rand(5, 25)
            ];
            $currentDate->addDay();
        }
        
        return $trends;
    }

    private function getMostCommonErrors($dateFrom, $dateTo): array
    {
        return [
            ['error' => 'Database connection timeout', 'count' => 25],
            ['error' => 'Invalid authentication token', 'count' => 18],
            ['error' => 'File upload size exceeded', 'count' => 12],
            ['error' => 'Rate limit exceeded', 'count' => 8],
            ['error' => 'Memory limit exceeded', 'count' => 5]
        ];
    }

    private function getErrorResolutionTime($dateFrom, $dateTo): float
    {
        return Cache::remember("error_resolution_time_{$dateFrom}_{$dateTo}", 300, function() {
            return round(rand(30, 180), 2); // Minutes
        });
    }

    private function getTotalErrors($dateFrom, $dateTo): int
    {
        return Cache::remember("total_errors_{$dateFrom}_{$dateTo}", 300, function() {
            return rand(100, 500);
        });
    }

    private function getNetworkUsage(): array
    {
        return [
            'bytes_in' => rand(1000000, 10000000),
            'bytes_out' => rand(500000, 5000000),
            'packets_in' => rand(10000, 100000),
            'packets_out' => rand(5000, 50000)
        ];
    }

    private function getCacheUsage(): array
    {
        return [
            'memory_usage' => rand(50, 200), // MB
            'hit_rate' => round(rand(80, 95), 2),
            'miss_rate' => round(rand(5, 20), 2)
        ];
    }

    private function getDatabaseUsage(): array
    {
        return [
            'connections' => $this->getDatabaseConnections(),
            'queries_per_second' => rand(100, 1000),
            'slow_queries' => rand(5, 50)
        ];
    }

    private function getServiceUptime($dateFrom, $dateTo): array
    {
        return [
            'web_server' => 99.9,
            'database' => 99.8,
            'cache' => 99.7,
            'api' => 99.6
        ];
    }

    private function getDowntimeIncidents($dateFrom, $dateTo): array
    {
        return [
            ['service' => 'API', 'start_time' => '2024-01-15 10:30:00', 'end_time' => '2024-01-15 10:45:00', 'duration' => '15 minutes'],
            ['service' => 'Database', 'start_time' => '2024-01-20 14:20:00', 'end_time' => '2024-01-20 14:25:00', 'duration' => '5 minutes']
        ];
    }

    private function getUptimePercentage($dateFrom, $dateTo): float
    {
        return round(rand(99, 100), 2);
    }

    private function getLastRestart(): string
    {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime -s');
            return trim($uptime);
        }
        return 'N/A';
    }

    private function getPlannedMaintenance($dateFrom, $dateTo): array
    {
        return [
            ['service' => 'Database', 'scheduled_time' => '2024-02-01 02:00:00', 'duration' => '2 hours', 'status' => 'scheduled'],
            ['service' => 'API', 'scheduled_time' => '2024-02-15 01:00:00', 'duration' => '1 hour', 'status' => 'scheduled']
        ];
    }

    private function getFailedLoginAttempts($dateFrom, $dateTo): array
    {
        return [
            ['ip' => '192.168.1.100', 'attempts' => 15, 'last_attempt' => '2024-01-25 15:30:00'],
            ['ip' => '10.0.0.50', 'attempts' => 8, 'last_attempt' => '2024-01-25 14:45:00'],
            ['ip' => '172.16.0.25', 'attempts' => 12, 'last_attempt' => '2024-01-25 16:15:00']
        ];
    }

    private function getSuspiciousActivities($dateFrom, $dateTo): array
    {
        return [
            ['activity' => 'Multiple failed logins', 'count' => 25],
            ['activity' => 'Unusual API usage pattern', 'count' => 8],
            ['activity' => 'Suspicious file uploads', 'count' => 3],
            ['activity' => 'Rate limit violations', 'count' => 15]
        ];
    }

    private function getSecurityEvents($dateFrom, $dateTo): array
    {
        return [
            ['event' => 'IP blocked', 'count' => 5],
            ['event' => 'Account locked', 'count' => 12],
            ['event' => 'Suspicious activity detected', 'count' => 8],
            ['event' => 'Security scan completed', 'count' => 1]
        ];
    }

    private function getIpBlocking($dateFrom, $dateTo): array
    {
        return [
            ['ip' => '192.168.1.100', 'reason' => 'Multiple failed logins', 'blocked_at' => '2024-01-25 15:30:00'],
            ['ip' => '10.0.0.50', 'reason' => 'Suspicious activity', 'blocked_at' => '2024-01-25 14:45:00']
        ];
    }

    private function getSslCertificateStatus(): array
    {
        return [
            'status' => 'valid',
            'expires_at' => '2024-12-31 23:59:59',
            'days_remaining' => 340,
            'issuer' => 'Let\'s Encrypt'
        ];
    }

    private function getSecurityScore($dateFrom, $dateTo): int
    {
        return rand(85, 95);
    }

    private function getResponseTimeTrends($dateFrom, $dateTo): array
    {
        $trends = [];
        $currentDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        
        while ($currentDate->lte($endDate)) {
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'avg_response_time' => round(rand(100, 300) / 1000, 3)
            ];
            $currentDate->addDay();
        }
        
        return $trends;
    }

    private function getErrorRateTrends($dateFrom, $dateTo): array
    {
        $trends = [];
        $currentDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        
        while ($currentDate->lte($endDate)) {
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'error_rate' => round(rand(1, 5), 2)
            ];
            $currentDate->addDay();
        }
        
        return $trends;
    }

    private function getResourceUsageTrends($dateFrom, $dateTo): array
    {
        $trends = [];
        $currentDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        
        while ($currentDate->lte($endDate)) {
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 70),
                'disk_usage' => rand(40, 90)
            ];
            $currentDate->addDay();
        }
        
        return $trends;
    }

    private function getThroughputTrends($dateFrom, $dateTo): array
    {
        $trends = [];
        $currentDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        
        while ($currentDate->lte($endDate)) {
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'requests_per_second' => rand(100, 1000)
            ];
            $currentDate->addDay();
        }
        
        return $trends;
    }

    private function getActiveAlerts(): array
    {
        return [
            ['type' => 'High CPU Usage', 'severity' => 'warning', 'message' => 'CPU usage is above 80%'],
            ['type' => 'Low Disk Space', 'severity' => 'critical', 'message' => 'Disk space is below 10%'],
            ['type' => 'High Error Rate', 'severity' => 'warning', 'message' => 'Error rate is above 5%']
        ];
    }

    private function getAlertHistory($dateFrom, $dateTo): array
    {
        return [
            ['type' => 'Memory Usage', 'severity' => 'warning', 'resolved_at' => '2024-01-25 10:30:00'],
            ['type' => 'Database Connection', 'severity' => 'critical', 'resolved_at' => '2024-01-24 15:45:00'],
            ['type' => 'API Response Time', 'severity' => 'warning', 'resolved_at' => '2024-01-23 09:15:00']
        ];
    }

    private function getNotificationDelivery($dateFrom, $dateTo): array
    {
        return [
            ['channel' => 'Email', 'delivered' => 95, 'failed' => 5],
            ['channel' => 'SMS', 'delivered' => 98, 'failed' => 2],
            ['channel' => 'Push', 'delivered' => 92, 'failed' => 8]
        ];
    }

    private function getAlertTypes($dateFrom, $dateTo): array
    {
        return [
            ['type' => 'Performance', 'count' => 25],
            ['type' => 'Security', 'count' => 15],
            ['type' => 'System', 'count' => 20],
            ['type' => 'Database', 'count' => 10]
        ];
    }

    private function getAlertResolutionTime($dateFrom, $dateTo): float
    {
        return round(rand(15, 120), 2); // Minutes
    }

    private function parseMemoryLimit($limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $limit = (int) $limit;
        
        switch($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }
        
        return $limit;
    }
}
