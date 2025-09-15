<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

class MonitorApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sarvcast:monitor {--report : Generate monitoring report} {--health : Check application health} {--metrics : Get application metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor SarvCast application health and performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitoringService = app(MonitoringService::class);

        $this->info('📊 SarvCast Application Monitoring');
        $this->line('');

        // Check application health
        if ($this->option('health') || $this->option('report')) {
            $this->info('🏥 Checking Application Health...');
            $health = $monitoringService->checkApplicationHealth();
            
            $statusIcon = $health['status'] === 'healthy' ? '✅' : '❌';
            $this->line("Status: {$statusIcon} {$health['status']}");
            
            foreach ($health['checks'] as $checkName => $check) {
                $checkIcon = $check['status'] === 'healthy' ? '✅' : '❌';
                $this->line("  {$checkIcon} " . ucfirst($checkName) . ": {$check['message']}");
                
                if (isset($check['response_time'])) {
                    $this->line("    Response time: " . round($check['response_time'] * 1000, 2) . "ms");
                }
            }
            $this->line('');
        }

        // Get application metrics
        if ($this->option('metrics') || $this->option('report')) {
            $this->info('📈 Application Metrics...');
            $metrics = $monitoringService->getApplicationMetrics();
            
            $this->table(
                ['Category', 'Metric', 'Value'],
                [
                    ['Users', 'Total Users', number_format($metrics['users']['total'])],
                    ['Users', 'Active Users', number_format($metrics['users']['active'])],
                    ['Users', 'New Today', number_format($metrics['users']['new_today'])],
                    ['Content', 'Published Stories', number_format($metrics['content']['stories'])],
                    ['Content', 'Published Episodes', number_format($metrics['content']['episodes'])],
                    ['Content', 'New Stories Today', number_format($metrics['content']['new_stories_today'])],
                    ['Content', 'New Episodes Today', number_format($metrics['content']['new_episodes_today'])],
                    ['Business', 'Active Subscriptions', number_format($metrics['business']['active_subscriptions'])],
                    ['Business', 'Payments Today', number_format($metrics['business']['payments_today'])],
                    ['Business', 'Revenue Today', number_format($metrics['business']['revenue_today']) . ' IRR'],
                    ['Performance', 'Memory Usage', $this->formatBytes($metrics['performance']['memory_usage'])],
                    ['Performance', 'Peak Memory', $this->formatBytes($metrics['performance']['peak_memory'])],
                    ['Performance', 'Execution Time', round($metrics['performance']['execution_time'], 3) . 's'],
                ]
            );
            $this->line('');
        }

        // Generate full report
        if ($this->option('report')) {
            $this->info('📋 Generating Full Monitoring Report...');
            $report = $monitoringService->generateMonitoringReport();
            
            // Show alerts
            if (!empty($report['alerts'])) {
                $this->warn('⚠️ Active Alerts:');
                foreach ($report['alerts'] as $alert) {
                    $severityIcon = $alert['severity'] === 'high' ? '🔴' : '🟡';
                    $this->line("  {$severityIcon} [{$alert['severity']}] {$alert['message']}");
                }
                $this->line('');
            } else {
                $this->info('✅ No active alerts');
                $this->line('');
            }
            
            // Show error rates
            $this->info('📊 Error Rates:');
            $errorRates = $report['error_rates'];
            $this->line("  Errors last hour: {$errorRates['errors_last_hour']}");
            $this->line("  Errors last day: {$errorRates['errors_last_day']}");
            $this->line("  Error rate: " . round($errorRates['error_rate'] * 100, 2) . "%");
            $this->line('');
            
            // Show API performance
            $this->info('🚀 API Performance:');
            $apiPerformance = $report['api_performance'];
            $this->line("  Requests per minute: {$apiPerformance['requests_per_minute']}");
            $this->line("  Average response time: " . round($apiPerformance['average_response_time'], 3) . "s");
            $this->line('');
        }

        $this->info('🎉 Monitoring completed!');
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}