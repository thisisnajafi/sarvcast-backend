<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCronJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test cron job functionality and log output';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $this->info("Cron job test started at: {$timestamp}");
        
        // Log to Laravel log
        Log::info("Cron job test executed at: {$timestamp}");
        
        // Log to scheduler log
        Log::channel('single')->info("Scheduler test: {$timestamp}");
        
        // Test database connection
        try {
            \DB::connection()->getPdo();
            $this->info("✅ Database connection: OK");
            Log::info("Cron job test: Database connection successful");
        } catch (\Exception $e) {
            $this->error("❌ Database connection: FAILED - " . $e->getMessage());
            Log::error("Cron job test: Database connection failed - " . $e->getMessage());
        }
        
        // Test Telegram service
        try {
            $telegramService = new \App\Services\TelegramNotificationService();
            $this->info("✅ Telegram service: OK");
            Log::info("Cron job test: Telegram service initialized successfully");
        } catch (\Exception $e) {
            $this->error("❌ Telegram service: FAILED - " . $e->getMessage());
            Log::error("Cron job test: Telegram service failed - " . $e->getMessage());
        }
        
        $this->info("Cron job test completed at: " . now()->format('Y-m-d H:i:s'));
        
        return Command::SUCCESS;
    }
}