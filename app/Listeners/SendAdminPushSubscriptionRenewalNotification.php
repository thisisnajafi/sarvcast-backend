<?php

namespace App\Listeners;

use App\Events\SubscriptionRenewalEvent;
use App\Services\AdminPushNotificationService;
use Illuminate\Support\Facades\Log;

class SendAdminPushSubscriptionRenewalNotification
{
    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {}

    public function handle(SubscriptionRenewalEvent $event): void
    {
        try {
            $sent = $this->adminPushService->sendSubscriptionRenewalNotification($event->subscription);

            if ($sent > 0) {
                Log::info('Admin push subscription renewal notification sent', [
                    'subscription_id' => $event->subscription->id,
                    'admin_count' => $sent,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin push renewal notification: ' . $e->getMessage(), [
                'subscription_id' => $event->subscription->id,
            ]);
        }
    }
}
