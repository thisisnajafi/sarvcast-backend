<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class PollPendingSmsDeliveriesCommand extends Command
{
    protected $signature = 'sms:poll-deliveries {--limit= : Maximum number of logs to poll}';

    protected $description = 'Poll Melipayamak delivery status for recently sent SMS logs';

    public function handle(SmsService $smsService): int
    {
        if (! config('sms.delivery.polling_enabled', true)) {
            $this->warn('SMS delivery polling is disabled.');

            return self::SUCCESS;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $count = $smsService->pollPendingDeliveries($limit);

        $this->info("Marked {$count} SMS log(s) as delivered.");

        return self::SUCCESS;
    }
}
