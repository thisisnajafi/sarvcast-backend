<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollSmsLogDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public function __construct(
        public int $smsLogId,
    ) {
        $this->tries = (int) config('sms.delivery.job_tries', 5);
    }

    public function backoff(): int
    {
        return (int) config('sms.delivery.job_backoff_seconds', 120);
    }

    public function handle(SmsService $smsService): void
    {
        $log = SmsLog::query()->find($this->smsLogId);

        if (! $log || $log->status === 'delivered' || empty($log->message_id)) {
            return;
        }

        $smsService->pollSmsLogDelivery($log->fresh());

        if ($log->fresh()->status !== 'delivered' && $this->attempts() < $this->tries) {
            $this->release($this->backoff());
        }
    }
}
