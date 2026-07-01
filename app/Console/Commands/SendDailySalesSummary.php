<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdminPushNotificationService;

class SendDailySalesSummary extends Command
{
    protected $signature = 'notifications:daily-sales-summary';

    protected $description = 'Send daily sales summary push to admin users';

    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Sending daily sales summary to admins...');

        try {
            $sent = $this->adminPushService->sendDailySalesSummary();

            if ($sent > 0) {
                $this->info("Daily sales summary sent to {$sent} admin(s).");
            } else {
                $this->warn('No admin users received the daily sales summary.');
            }
        } catch (\Exception $e) {
            $this->error('Error sending daily sales summary: ' . $e->getMessage());
        }
    }
}
