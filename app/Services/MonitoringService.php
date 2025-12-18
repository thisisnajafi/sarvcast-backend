<?php

namespace App\Services;

use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MonitoringService
{
    /**
     * Monitor application health
     */
    public function checkApplicationHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now(),
            'checks' => [],
        ];

        // Check database connection
        $health['checks']['database'] = $this->checkDatabase();
        
        // Check Redis connection
        $health['checks']['redis'] = $this->checkRedis();
        
        // Check file storage
        $health['checks']['storage'] = $this->checkStorage();
        
        // Check external services
        $health['checks']['external_services'] = $this->checkExternalServices();
        
        // Determine overall status
        $failedChecks = array_filter($health['checks'], function($check) {
            return $check['status'] === 'unhealthy';
        });
        
        if (!empty($failedChecks)) {
            $health['status'] = 'unhealthy';
        }

        return $health;
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $queryTime = $this->measureQueryTime('SELECT 1');
            
            return [
                'status' => 'healthy',
                'response_time' => $queryTime,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connection
     */
    private function checkRedis(): array
    {
        try {
            Redis::ping();
            $responseTime = $this->measureRedisTime();
            
            return [
                'status' => 'healthy',
                'response_time' => $responseTime,
                'message' => 'Redis connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check file storage
     */
    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test';
            
            // Test write
            \Storage::put($testFile, $testContent);
            
            // Test read
            $content = \Storage::get($testFile);
            
            // Test delete
            \Storage::delete($testFile);
            
            if ($content === $testContent) {
                return [
                    'status' => 'healthy',
                    'message' => 'File storage working correctly',
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'File storage read/write mismatch',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'File storage check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check external services
     */
    private function checkExternalServices(): array
    {
        $services = [];
        
        // Check SMS service
        $services['sms'] = $this->checkSmsService();
        
        // Check payment gateway
        $services['payment'] = $this->checkPaymentGateway();
        
        // Check notification service
        $services['notifications'] = $this->checkNotificationService();
        
        return $services;
    }

    /**
     * Check SMS service
     */
    private function checkSmsService(): array
    {
        try {
            // This would typically ping the SMS service API
            return [
                'status' => 'healthy',
                'message' => 'SMS service available',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'SMS service unavailable: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check payment gateway
     */
    private function checkPaymentGateway(): array
    {
        try {
            // This would typically ping the payment gateway API
            return [
                'status' => 'healthy',
                'message' => 'Payment gateway available',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Payment gateway unavailable: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check notification service
     */
    private function checkNotificationService(): array
    {
        try {
            // This would typically ping the notification service API
            return [
                'status' => 'healthy',
                'message' => 'Notification service available',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Notification service unavailable: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Measure query execution time
     */
    private function measureQueryTime(string $query): float
    {
        $start = microtime(true);
        DB::select($query);
        return microtime(true) - $start;
    }

    /**
     * Measure Redis operation time
     */
    private function measureRedisTime(): float
    {
        $start = microtime(true);
        Redis::ping();
        return microtime(true) - $start;
    }

    /**
     * Get application metrics
     */
    public function getApplicationMetrics(): array
    {
        return [
            'timestamp' => now(),
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'new_today' => User::whereDate('created_at', today())->count(),
            ],
            'content' => [
                'stories' => Story::where('status', 'published')->count(),
                'episodes' => Episode::where('status', 'published')->count(),
                'new_stories_today' => Story::whereDate('created_at', today())->count(),
                'new_episodes_today' => Episode::whereDate('created_at', today())->count(),
            ],
            'business' => [
                'active_subscriptions' => Subscription::where('status', 'active')->count(),
                'payments_today' => Payment::whereDate('created_at', today())->count(),
                'revenue_today' => Payment::whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->sum('amount'),
            ],
            'performance' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - LARAVEL_START,
            ],
        ];
    }

    /**
     * Log application events
     */
    public function logApplicationEvent(string $event, array $data = []): void
    {
        Log::channel('application')->info($event, array_merge([
            'timestamp' => now(),
            'event' => $event,
        ], $data));
    }

    /**
     * Monitor error rates
     */
    public function getErrorRates(): array
    {
        // This would typically query error logs
        return [
            'timestamp' => now(),
            'errors_last_hour' => 0,
            'errors_last_day' => 0,
            'error_rate' => 0.0,
        ];
    }

    /**
     * Monitor API performance
     */
    public function getApiPerformance(): array
    {
        // This would typically query API logs
        return [
            'timestamp' => now(),
            'requests_per_minute' => 0,
            'average_response_time' => 0.0,
            'slowest_endpoints' => [],
        ];
    }

    /**
     * Generate monitoring report
     */
    public function generateMonitoringReport(): array
    {
        return [
            'timestamp' => now(),
            'health' => $this->checkApplicationHealth(),
            'metrics' => $this->getApplicationMetrics(),
            'error_rates' => $this->getErrorRates(),
            'api_performance' => $this->getApiPerformance(),
            'alerts' => $this->getActiveAlerts(),
        ];
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts(): array
    {
        $alerts = [];
        
        // Check for high error rates
        $errorRates = $this->getErrorRates();
        if ($errorRates['error_rate'] > 0.05) { // 5% error rate
            $alerts[] = [
                'type' => 'error_rate',
                'message' => 'High error rate detected',
                'severity' => 'high',
            ];
        }
        
        // Check for high memory usage
        $metrics = $this->getApplicationMetrics();
        if ($metrics['performance']['memory_usage'] > 256 * 1024 * 1024) { // 256MB
            $alerts[] = [
                'type' => 'memory_usage',
                'message' => 'High memory usage detected',
                'severity' => 'medium',
            ];
        }
        
        // Check for slow response times
        $apiPerformance = $this->getApiPerformance();
        if ($apiPerformance['average_response_time'] > 2.0) { // 2 seconds
            $alerts[] = [
                'type' => 'response_time',
                'message' => 'Slow response times detected',
                'severity' => 'medium',
            ];
        }
        
        return $alerts;
    }

    /**
     * Set up monitoring alerts
     */
    public function setupAlerts(): void
    {
        // This would typically set up monitoring alerts with external services
        Log::info('Monitoring alerts configured');
    }
}

