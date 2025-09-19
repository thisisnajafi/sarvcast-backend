<?php

namespace App\Listeners;

use App\Events\SubscriptionCancellationEvent;
use App\Services\TelegramNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTelegramSubscriptionCancellationNotification implements ShouldQueue
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
    public function handle(SubscriptionCancellationEvent $event): void
    {
        try {
            $this->telegramService->sendSubscriptionCancellationNotification($event->subscription);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram subscription cancellation notification: ' . $e->getMessage(), [
                'subscription_id' => $event->subscription->id
            ]);
        }
    }
}
