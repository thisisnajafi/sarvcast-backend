<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\PerformanceMonitoringService;
use Illuminate\Support\Facades\Log;

class PerformanceMonitoringMiddleware
{
    protected $performanceService;

    public function __construct(PerformanceMonitoringService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Enable query logging
        \DB::enableQueryLog();
        
        // Process the request
        $response = $next($request);
        
        // Calculate metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $metrics = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'response_time' => ($endTime - $startTime) * 1000, // Convert to milliseconds
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(true),
            'database_queries' => count(\DB::getQueryLog()),
            'status_code' => $response->getStatusCode()
        ];
        
        // Monitor specific features
        $this->monitorFeaturePerformance($request, $metrics);
        
        // Log performance data
        $this->logPerformanceData($metrics);
        
        return $response;
    }
    
    /**
     * Monitor performance for specific features
     */
    private function monitorFeaturePerformance(Request $request, array $metrics): void
    {
        $path = $request->path();
        
        // Monitor timeline requests
        if (str_contains($path, 'image-timeline') || str_contains($path, 'image-for-time')) {
            $episodeId = $this->extractEpisodeId($request);
            if ($episodeId) {
                $this->performanceService->monitorTimelinePerformance($episodeId, $metrics);
            }
        }
        
        // Monitor comment requests
        if (str_contains($path, 'comments')) {
            $storyId = $this->extractStoryId($request);
            if ($storyId) {
                $action = $this->determineCommentAction($request);
                $this->performanceService->monitorCommentPerformance($storyId, $action, $metrics);
            }
        }
    }
    
    /**
     * Extract episode ID from request
     */
    private function extractEpisodeId(Request $request): ?int
    {
        $path = $request->path();
        
        // Match patterns like: episodes/{id}/image-timeline, episodes/{id}/image-for-time
        if (preg_match('/episodes\/([^\/]+)/', $path, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extract story ID from request
     */
    private function extractStoryId(Request $request): ?int
    {
        $path = $request->path();
        
        // Match patterns like: stories/{id}/comments
        if (preg_match('/stories\/([^\/]+)/', $path, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }
    
    /**
     * Determine comment action from request
     */
    private function determineCommentAction(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();
        
        if ($method === 'GET') {
            return 'view';
        } elseif ($method === 'POST') {
            return 'create';
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            return 'update';
        } elseif ($method === 'DELETE') {
            return 'delete';
        }
        
        return 'unknown';
    }
    
    /**
     * Log performance data
     */
    private function logPerformanceData(array $metrics): void
    {
        // Only log if response time is above threshold or if there's an error
        if ($metrics['response_time'] > 1000 || $metrics['status_code'] >= 400) {
            Log::info('Performance Monitoring', $metrics);
        }
        
        // Update cache counters
        $this->updateCacheCounters($metrics);
    }
    
    /**
     * Update cache counters
     */
    private function updateCacheCounters(array $metrics): void
    {
        // Update total requests counter
        \Cache::increment('total_requests', 1);
        
        // Update error counter if status code indicates error
        if ($metrics['status_code'] >= 400) {
            \Cache::increment('total_errors', 1);
        }
        
        // Update feature-specific counters
        if (str_contains($metrics['url'], 'image-timeline')) {
            \Cache::increment('feature_requests_timeline', 1);
        } elseif (str_contains($metrics['url'], 'comments')) {
            \Cache::increment('feature_requests_comment', 1);
        }
        
        // Update cache hit/miss counters (if available)
        if (isset($metrics['cache_hits'])) {
            \Cache::increment('cache_hits', $metrics['cache_hits']);
        }
        if (isset($metrics['cache_misses'])) {
            \Cache::increment('cache_misses', $metrics['cache_misses']);
        }
    }
}