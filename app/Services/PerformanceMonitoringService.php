<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PerformanceMonitoringService
{
    /**
     * Monitor timeline performance metrics
     */
    public function monitorTimelinePerformance(int $episodeId, array $metrics = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Record timeline access
            $this->recordTimelineAccess($episodeId);
            
            // Calculate performance metrics
            $performanceData = [
                'episode_id' => $episodeId,
                'timestamp' => now(),
                'response_time' => (microtime(true) - $startTime) * 1000, // Convert to milliseconds
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'cache_hits' => $this->getCacheHits(),
                'cache_misses' => $this->getCacheMisses(),
                'database_queries' => $this->getDatabaseQueryCount(),
                'custom_metrics' => $metrics
            ];
            
            // Store performance data
            $this->storePerformanceData('timeline', $performanceData);
            
            // Check for performance alerts
            $this->checkPerformanceAlerts('timeline', $performanceData);
            
            return $performanceData;
            
        } catch (\Exception $e) {
            Log::error('Timeline performance monitoring error: ' . $e->getMessage());
            return [
                'episode_id' => $episodeId,
                'timestamp' => now(),
                'error' => $e->getMessage(),
                'response_time' => (microtime(true) - $startTime) * 1000
            ];
        }
    }
    
    /**
     * Monitor comment performance metrics
     */
    public function monitorCommentPerformance(int $storyId, string $action, array $metrics = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Record comment action
            $this->recordCommentAction($storyId, $action);
            
            // Calculate performance metrics
            $performanceData = [
                'story_id' => $storyId,
                'action' => $action,
                'timestamp' => now(),
                'response_time' => (microtime(true) - $startTime) * 1000,
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'cache_hits' => $this->getCacheHits(),
                'cache_misses' => $this->getCacheMisses(),
                'database_queries' => $this->getDatabaseQueryCount(),
                'custom_metrics' => $metrics
            ];
            
            // Store performance data
            $this->storePerformanceData('comment', $performanceData);
            
            // Check for performance alerts
            $this->checkPerformanceAlerts('comment', $performanceData);
            
            return $performanceData;
            
        } catch (\Exception $e) {
            Log::error('Comment performance monitoring error: ' . $e->getMessage());
            return [
                'story_id' => $storyId,
                'action' => $action,
                'timestamp' => now(),
                'error' => $e->getMessage(),
                'response_time' => (microtime(true) - $startTime) * 1000
            ];
        }
    }
    
    /**
     * Get performance statistics
     */
    public function getPerformanceStats(string $feature = null, int $hours = 24): array
    {
        $cacheKey = "performance_stats_{$feature}_{$hours}";
        
        return Cache::remember($cacheKey, 300, function () use ($feature, $hours) {
            $query = DB::table('performance_metrics')
                ->where('created_at', '>=', now()->subHours($hours));
            
            if ($feature) {
                $query->where('feature', $feature);
            }
            
            $stats = $query->selectRaw('
                feature,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                MIN(response_time) as min_response_time,
                AVG(memory_usage) as avg_memory_usage,
                MAX(memory_usage) as max_memory_usage,
                AVG(database_queries) as avg_database_queries,
                MAX(database_queries) as max_database_queries,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN response_time > 1000 THEN 1 END) as slow_requests
            ')->groupBy('feature')->get();
            
            return $stats->toArray();
        });
    }
    
    /**
     * Get real-time performance metrics
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'system' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'cpu_usage' => $this->getCpuUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'active_connections' => $this->getActiveConnections()
            ],
            'cache' => [
                'hits' => $this->getCacheHits(),
                'misses' => $this->getCacheMisses(),
                'hit_ratio' => $this->getCacheHitRatio()
            ],
            'database' => [
                'queries' => $this->getDatabaseQueryCount(),
                'connections' => $this->getDatabaseConnections(),
                'slow_queries' => $this->getSlowQueries()
            ],
            'features' => [
                'timeline_requests' => $this->getFeatureRequestCount('timeline'),
                'comment_requests' => $this->getFeatureRequestCount('comment'),
                'error_rate' => $this->getErrorRate()
            ]
        ];
    }
    
    /**
     * Record timeline access
     */
    private function recordTimelineAccess(int $episodeId): void
    {
        $cacheKey = "timeline_access_{$episodeId}";
        $accessCount = Cache::increment($cacheKey, 1);
        
        // Store daily access count
        $dailyKey = "timeline_daily_{$episodeId}_" . now()->format('Y-m-d');
        Cache::increment($dailyKey, 1);
        Cache::expire($dailyKey, 86400); // 24 hours
    }
    
    /**
     * Record comment action
     */
    private function recordCommentAction(int $storyId, string $action): void
    {
        $cacheKey = "comment_action_{$storyId}_{$action}";
        Cache::increment($cacheKey, 1);
        
        // Store daily action count
        $dailyKey = "comment_daily_{$storyId}_{$action}_" . now()->format('Y-m-d');
        Cache::increment($dailyKey, 1);
        Cache::expire($dailyKey, 86400); // 24 hours
    }
    
    /**
     * Store performance data
     */
    private function storePerformanceData(string $feature, array $data): void
    {
        try {
            DB::table('performance_metrics')->insert([
                'feature' => $feature,
                'data' => json_encode($data),
                'response_time' => $data['response_time'],
                'memory_usage' => $data['memory_usage'],
                'database_queries' => $data['database_queries'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store performance data: ' . $e->getMessage());
        }
    }
    
    /**
     * Check for performance alerts
     */
    private function checkPerformanceAlerts(string $feature, array $data): void
    {
        $alerts = [];
        
        // Response time alert
        if ($data['response_time'] > 2000) { // 2 seconds
            $alerts[] = [
                'type' => 'slow_response',
                'feature' => $feature,
                'value' => $data['response_time'],
                'threshold' => 2000,
                'message' => "Slow response time detected: {$data['response_time']}ms"
            ];
        }
        
        // Memory usage alert
        if ($data['memory_usage'] > 128 * 1024 * 1024) { // 128MB
            $alerts[] = [
                'type' => 'high_memory',
                'feature' => $feature,
                'value' => $data['memory_usage'],
                'threshold' => 128 * 1024 * 1024,
                'message' => "High memory usage detected: " . round($data['memory_usage'] / 1024 / 1024, 2) . "MB"
            ];
        }
        
        // Database queries alert
        if ($data['database_queries'] > 10) {
            $alerts[] = [
                'type' => 'too_many_queries',
                'feature' => $feature,
                'value' => $data['database_queries'],
                'threshold' => 10,
                'message' => "Too many database queries: {$data['database_queries']}"
            ];
        }
        
        // Send alerts if any
        if (!empty($alerts)) {
            $this->sendPerformanceAlerts($alerts);
        }
    }
    
    /**
     * Send performance alerts
     */
    private function sendPerformanceAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            Log::warning('Performance Alert: ' . $alert['message'], $alert);
            
            // Store alert in cache for admin dashboard
            $alertKey = 'performance_alert_' . now()->timestamp;
            Cache::put($alertKey, $alert, 3600); // 1 hour
        }
    }
    
    /**
     * Get cache hits
     */
    private function getCacheHits(): int
    {
        return Cache::get('cache_hits', 0);
    }
    
    /**
     * Get cache misses
     */
    private function getCacheMisses(): int
    {
        return Cache::get('cache_misses', 0);
    }
    
    /**
     * Get cache hit ratio
     */
    private function getCacheHitRatio(): float
    {
        $hits = $this->getCacheHits();
        $misses = $this->getCacheMisses();
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }
    
    /**
     * Get database query count
     */
    private function getDatabaseQueryCount(): int
    {
        return count(DB::getQueryLog());
    }
    
    /**
     * Get database connections
     */
    private function getDatabaseConnections(): int
    {
        try {
            return DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get slow queries
     */
    private function getSlowQueries(): int
    {
        try {
            return DB::select('SHOW STATUS LIKE "Slow_queries"')[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get CPU usage
     */
    private function getCpuUsage(): float
    {
        try {
            $load = sys_getloadavg();
            return $load[0] * 100; // Convert to percentage
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get disk usage
     */
    private function getDiskUsage(): array
    {
        try {
            $total = disk_total_space('/');
            $free = disk_free_space('/');
            $used = $total - $free;
            
            return [
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'percentage' => ($used / $total) * 100
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'used' => 0, 'free' => 0, 'percentage' => 0];
        }
    }
    
    /**
     * Get active connections
     */
    private function getActiveConnections(): int
    {
        try {
            $connections = DB::select('SHOW STATUS LIKE "Threads_connected"');
            return $connections[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get feature request count
     */
    private function getFeatureRequestCount(string $feature): int
    {
        return Cache::get("feature_requests_{$feature}", 0);
    }
    
    /**
     * Get error rate
     */
    private function getErrorRate(): float
    {
        $totalRequests = Cache::get('total_requests', 0);
        $errors = Cache::get('total_errors', 0);
        
        return $totalRequests > 0 ? ($errors / $totalRequests) * 100 : 0;
    }
    
    /**
     * Clean old performance data
     */
    public function cleanOldData(int $days = 30): int
    {
        $deleted = DB::table('performance_metrics')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
        
        Log::info("Cleaned {$deleted} old performance records");
        
        return $deleted;
    }
    
    /**
     * Generate performance report
     */
    public function generateReport(int $days = 7): array
    {
        $stats = $this->getPerformanceStats(null, $days * 24);
        $realTime = $this->getRealTimeMetrics();
        
        return [
            'period' => "{$days} days",
            'generated_at' => now(),
            'summary' => [
                'total_requests' => array_sum(array_column($stats, 'total_requests')),
                'avg_response_time' => array_sum(array_column($stats, 'avg_response_time')) / count($stats),
                'slow_requests' => array_sum(array_column($stats, 'slow_requests')),
                'error_rate' => $this->getErrorRate()
            ],
            'features' => $stats,
            'real_time' => $realTime,
            'recommendations' => $this->generateRecommendations($stats)
        ];
    }
    
    /**
     * Generate performance recommendations
     */
    private function generateRecommendations(array $stats): array
    {
        $recommendations = [];
        
        foreach ($stats as $stat) {
            if ($stat->avg_response_time > 500) {
                $recommendations[] = "Consider optimizing {$stat->feature} - average response time is {$stat->avg_response_time}ms";
            }
            
            if ($stat->avg_database_queries > 5) {
                $recommendations[] = "Consider adding caching for {$stat->feature} - average {$stat->avg_database_queries} database queries per request";
            }
            
            if ($stat->slow_requests > $stat->total_requests * 0.1) {
                $recommendations[] = "High number of slow requests for {$stat->feature} - {$stat->slow_requests} out of {$stat->total_requests}";
            }
        }
        
        return $recommendations;
    }
}
