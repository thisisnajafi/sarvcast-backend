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
            $payment = $event->payment->fresh();

            Log::info('Processing Telegram sales notification', [
                'payment_id' => $payment->id,
                'payment_status' => $payment->status,
                'subscription_id' => $event->subscription?->id
            ]);

            // Only send notification for completed payments and avoid duplicates
            $metadata = $payment->metadata ?? [];
            if (($metadata['telegram_sales_notified'] ?? false) === true) {
                Log::info('Skipping Telegram notification - already sent for this payment', [
                    'payment_id' => $payment->id,
                ]);
                return;
            }

            if ($payment->status === 'completed') {
                $success = $this->telegramService->sendSalesNotification($payment, $event->subscription);

                if ($success) {
                    // Mark notification as sent to prevent duplicates
                    $metadata['telegram_sales_notified'] = true;
                    $payment->metadata = $metadata;
                    $payment->save();

                    Log::info('Telegram sales notification sent successfully', [
                        'payment_id' => $payment->id,
                        'subscription_id' => $event->subscription?->id
                    ]);
                } else {
                    Log::error('Failed to send Telegram sales notification', [
                        'payment_id' => $payment->id,
                        'subscription_id' => $event->subscription?->id
                    ]);
                }
            } else {
                Log::info('Skipping Telegram notification - payment not completed', [
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status
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
