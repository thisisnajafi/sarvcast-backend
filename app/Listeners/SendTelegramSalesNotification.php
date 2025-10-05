<?php

namespace App\Listeners;

use App\Events\SalesNotificationEvent;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;

class SendTelegramSalesNotification
{
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
            Log::info('Processing Telegram sales notification', [
                'payment_id' => $event->payment->id,
                'payment_status' => $event->payment->status,
                'subscription_id' => $event->subscription?->id
            ]);
            
            // Only send notification for completed payments
            if ($event->payment->status === 'completed') {
                $success = $this->telegramService->sendSalesNotification($event->payment, $event->subscription);
                
                if ($success) {
                    Log::info('Telegram sales notification sent successfully', [
                        'payment_id' => $event->payment->id,
                        'subscription_id' => $event->subscription?->id
                    ]);
                } else {
                    Log::error('Failed to send Telegram sales notification', [
                        'payment_id' => $event->payment->id,
                        'subscription_id' => $event->subscription?->id
                    ]);
                }
            } else {
                Log::info('Skipping Telegram notification - payment not completed', [
                    'payment_id' => $event->payment->id,
                    'payment_status' => $event->payment->status
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram sales notification: ' . $e->getMessage(), [
                'payment_id' => $event->payment->id,
                'subscription_id' => $event->subscription?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
