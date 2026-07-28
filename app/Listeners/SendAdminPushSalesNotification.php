<?php

namespace App\Listeners;

use App\Events\SalesNotificationEvent;
use App\Models\Notification;
use App\Services\AdminPushNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendAdminPushSalesNotification
{
    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {}

    public function handle(SalesNotificationEvent $event): void
    {
        try {
            $payment = $event->payment->fresh();

            if ($payment->status !== 'completed') {
                return;
            }

            $idempotencyKey = "admin_push_sales_notified:{$payment->id}";
            if (Cache::get($idempotencyKey) === true) {
                return;
            }

            $lockKey = "admin_push_sales_{$payment->id}";
            $lock = Cache::lock($lockKey, 10);

            if (!$lock->get()) {
                return;
            }

            try {
                $payment->refresh();
                $metadata = array_merge(
                    $payment->payment_metadata ?? [],
                    $payment->metadata ?? []
                );

                if (($metadata['admin_push_sales_notified'] ?? false) === true) {
                    Cache::forever($idempotencyKey, true);
                    return;
                }

                if (
                    Notification::query()
                        ->where('data->alert_kind', 'sale')
                        ->where('data->payment_id', $payment->id)
                        ->exists()
                ) {
                    Cache::forever($idempotencyKey, true);
                    return;
                }

                $sent = $this->adminPushService->sendSalesNotification($payment, $event->subscription);

                if ($sent > 0) {
                    $flag = [
                        'admin_push_sales_notified' => true,
                        'admin_push_notified_at' => now()->toISOString(),
                    ];

                    $payment->metadata = array_merge($payment->metadata ?? [], $flag);
                    $payment->payment_metadata = array_merge($payment->payment_metadata ?? [], $flag);
                    $payment->save();
                    Cache::forever($idempotencyKey, true);

                    Log::info('Admin push sales notification sent', [
                        'payment_id' => $payment->id,
                        'admin_count' => $sent,
                    ]);
                }
            } finally {
                $lock->release();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin push sales notification: ' . $e->getMessage(), [
                'payment_id' => $event->payment->id,
            ]);
        }
    }
}
