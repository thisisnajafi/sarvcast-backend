<?php

namespace App\Console\Commands;

use App\Services\PerformanceService;
use Illuminate\Console\Command;

class OptimizePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sarvcast:optimize-performance {--warm-cache : Warm up cache} {--clear-cache : Clear expired cache} {--optimize-db : Optimize database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize SarvCast application performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $performanceService = app(PerformanceService::class);

        $this->info('🚀 Starting SarvCast performance optimization...');

        // Warm up cache
        if ($this->option('warm-cache')) {
            $this->info('🔥 Warming up cache...');
            $performanceService->warmUpCache();
            $this->info('✅ Cache warmed up successfully');
        }

        // Clear expired cache
        if ($this->option('clear-cache')) {
            $this->info('🧹 Clearing expired cache...');
            $performanceService->clearExpiredCache();
            $this->info('✅ Expired cache cleared');
        }

        // Optimize database
        if ($this->option('optimize-db')) {
            $this->info('🗄️ Optimizing database...');
            $performanceService->optimizeQueries();
            $this->info('✅ Database optimized');
        }

        // Generate performance report
        $this->info('📊 Generating performance report...');
        $report = $performanceService->generatePerformanceReport();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Hit Rate', $report['metrics']['cache_hit_rate'] . '%'],
                ['Average Query Time', $report['metrics']['database_query_time'] . 's'],
                ['Memory Usage', $this->formatBytes($report['metrics']['memory_usage'])],
                ['Peak Memory Usage', $this->formatBytes($report['metrics']['peak_memory_usage'])],
                ['Execution Time', $report['metrics']['execution_time'] . 's'],
            ]
        );

        // Show cache status
        $this->info('💾 Cache Status:');
        foreach ($report['cache_status'] as $key => $status) {
            $statusIcon = $status ? '✅' : '❌';
            $this->line("  {$statusIcon} " . str_replace('_', ' ', ucfirst($key)));
        }

        // Show recommendations
        if (!empty($report['recommendations'])) {
            $this->warn('⚠️ Recommendations:');
            foreach ($report['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }

        $this->info('🎉 Performance optimization completed!');
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