<?php

namespace App\Listeners;

use App\Events\InfluencerCommissionEvent;
use App\Services\TelegramNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTelegramInfluencerCommissionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $telegramService;

    /**
     * Create the event listener.
     */
    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle the event.
     */
    public function handle(InfluencerCommissionEvent $event): void
    {
        try {
            $this->telegramService->sendInfluencerCommissionNotification($event->commission);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram influencer commission notification: ' . $e->getMessage(), [
                'commission_id' => $event->commission->id
            ]);
        }
    }
}
