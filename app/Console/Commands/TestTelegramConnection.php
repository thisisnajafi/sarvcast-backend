<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramNotificationService;

class TestTelegramConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram bot connection and send a test message';

    protected $telegramService;

    /**
     * Create a new command instance.
     */
    public function __construct(TelegramNotificationService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Telegram bot connection...');
        
        try {
            // Test connection
            $connectionSuccess = $this->telegramService->testConnection();
            
            if ($connectionSuccess) {
                $this->info('✅ Telegram bot connection successful!');
                
                // Send test message
                $this->info('Sending test message...');
                $testMessage = "🧪 <b>تست اتصال تلگرام</b>\n\n";
                $testMessage .= "✅ اتصال به ربات تلگرام با موفقیت برقرار شد!\n";
                $testMessage .= "🕐 زمان تست: " . now()->format('Y-m-d H:i:s') . "\n";
                $testMessage .= "🤖 ربات آماده دریافت اعلان‌ها است.";
                
                $messageSuccess = $this->telegramService->sendMessage($testMessage);
                
                if ($messageSuccess) {
                    $this->info('✅ Test message sent successfully!');
                } else {
                    $this->error('❌ Failed to send test message.');
                }
            } else {
                $this->error('❌ Telegram bot connection failed!');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error testing Telegram connection: ' . $e->getMessage());
        }
    }
}
