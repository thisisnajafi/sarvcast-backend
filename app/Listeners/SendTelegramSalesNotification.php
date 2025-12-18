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
            if ($payment->status === 'completed') {
                // Use database lock to prevent race conditions
                $lockKey = "telegram_notification_{$payment->id}";

                // Try to acquire a lock for 10 seconds
                $lock = \Cache::lock($lockKey, 10);

                if ($lock->get()) {
                    try {
                        // Double-check the metadata after acquiring lock
                        $payment->refresh();
                        $metadata = $payment->metadata ?? [];

                        if (($metadata['telegram_sales_notified'] ?? false) === true) {
                            Log::info('Skipping Telegram notification - already sent for this payment', [
                                'payment_id' => $payment->id,
                            ]);
                            return;
                        }

                        Log::info('Sending Telegram notification', [
                            'payment_id' => $payment->id,
                            'user_id' => $payment->user_id,
                            'amount' => $payment->amount,
                            'subscription_type' => $event->subscription?->type
                        ]);

                        $success = $this->telegramService->sendSalesNotification($payment, $event->subscription);

                        if ($success) {
                            // Mark notification as sent to prevent duplicates
                            $metadata['telegram_sales_notified'] = true;
                            $metadata['telegram_notified_at'] = now()->toISOString();
                            $payment->metadata = $metadata;
                            $payment->save();

                            Log::info('Telegram sales notification sent successfully', [
                                'payment_id' => $payment->id,
                                'subscription_id' => $event->subscription?->id,
                                'telegram_notified_at' => $metadata['telegram_notified_at']
                            ]);
                        } else {
                            Log::error('Failed to send Telegram sales notification', [
                                'payment_id' => $payment->id,
                                'subscription_id' => $event->subscription?->id,
                                'telegram_service_error' => true
                            ]);
                        }
                    } finally {
                        // Always release the lock
                        $lock->release();
                    }
                } else {
                    Log::info('Skipping Telegram notification - lock could not be acquired (another process handling)', [
                        'payment_id' => $payment->id,
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
