<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Process the request
        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Calculate performance metrics
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        $peakMemory = memory_get_peak_usage();

        // Add performance headers
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        $response->headers->set('X-Peak-Memory', $this->formatBytes($peakMemory));

        // Log slow requests
        if ($executionTime > 1000) { // More than 1 second
            \Log::warning('Slow Request Detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime . 'ms',
                'memory_usage' => $this->formatBytes($memoryUsage),
                'peak_memory' => $this->formatBytes($peakMemory),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
        }

        // Log high memory usage
        if ($memoryUsage > 50 * 1024 * 1024) { // More than 50MB
            \Log::warning('High Memory Usage Detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'memory_usage' => $this->formatBytes($memoryUsage),
                'peak_memory' => $this->formatBytes($peakMemory),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
        }

        // Store performance metrics in cache for monitoring
        $this->storePerformanceMetrics($request, $executionTime, $memoryUsage, $peakMemory);

        return $response;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Store performance metrics for monitoring
     */
    private function storePerformanceMetrics(Request $request, $executionTime, $memoryUsage, $peakMemory)
    {
        try {
            $metrics = [
                'timestamp' => now(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'route_name' => $request->route() ? $request->route()->getName() : null,
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'peak_memory' => $peakMemory,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ];

            // Store in cache for real-time monitoring
            $cacheKey = 'performance_metrics:' . now()->format('Y-m-d-H');
            $existingMetrics = \Cache::get($cacheKey, []);
            $existingMetrics[] = $metrics;
            
            // Keep only last 1000 metrics per hour
            if (count($existingMetrics) > 1000) {
                $existingMetrics = array_slice($existingMetrics, -1000);
            }
            
            \Cache::put($cacheKey, $existingMetrics, 3600); // Store for 1 hour

            // Store in database for historical analysis
            \DB::table('performance_metrics')->insert([
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'route_name' => $request->route() ? $request->route()->getName() : null,
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'peak_memory' => $peakMemory,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            // If storing metrics fails, continue without throwing error
            \Log::error('Failed to store performance metrics: ' . $e->getMessage());
        }
    }
}
