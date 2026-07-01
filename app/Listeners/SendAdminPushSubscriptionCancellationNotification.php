<?php

namespace App\Listeners;

use App\Events\SubscriptionCancellationEvent;
use App\Services\AdminPushNotificationService;
use Illuminate\Support\Facades\Log;

class SendAdminPushSubscriptionCancellationNotification
{
    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {}

    public function handle(SubscriptionCancellationEvent $event): void
    {
        try {
            $sent = $this->adminPushService->sendSubscriptionCancellationNotification($event->subscription);

            if ($sent > 0) {
                Log::info('Admin push subscription cancellation notification sent', [
                    'subscription_id' => $event->subscription->id,
                    'admin_count' => $sent,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin push cancellation notification: ' . $e->getMessage(), [
                'subscription_id' => $event->subscription->id,
            ]);
        }
    }
}
