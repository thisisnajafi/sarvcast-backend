<?php

namespace App\Listeners;

use App\Events\SalesNotificationEvent;
use App\Services\TelegramNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTelegramSalesNotification implements ShouldQueue
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
    public function handle(SalesNotificationEvent $event): void
    {
        try {
            // Only send notification for completed payments
            if ($event->payment->status === 'completed') {
                $this->telegramService->sendSalesNotification($event->payment, $event->subscription);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram sales notification: ' . $e->getMessage(), [
                'payment_id' => $event->payment->id,
                'subscription_id' => $event->subscription?->id
            ]);
        }
    }
}
