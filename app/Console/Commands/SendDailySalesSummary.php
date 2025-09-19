<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramNotificationService;

class SendDailySalesSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:daily-sales-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily sales summary to Telegram';

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
        $this->info('Sending daily sales summary to Telegram...');
        
        try {
            $success = $this->telegramService->sendDailySalesSummary();
            
            if ($success) {
                $this->info('Daily sales summary sent successfully!');
            } else {
                $this->error('Failed to send daily sales summary.');
            }
        } catch (\Exception $e) {
            $this->error('Error sending daily sales summary: ' . $e->getMessage());
        }
    }
}
