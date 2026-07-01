<?php

namespace App\Listeners;

use App\Events\InfluencerCommissionEvent;
use App\Services\AdminPushNotificationService;
use Illuminate\Support\Facades\Log;

class SendAdminPushInfluencerCommissionNotification
{
    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {}

    public function handle(InfluencerCommissionEvent $event): void
    {
        try {
            $sent = $this->adminPushService->sendInfluencerCommissionNotification($event->commission);

            if ($sent > 0) {
                Log::info('Admin push commission notification sent', [
                    'commission_id' => $event->commission->id,
                    'admin_count' => $sent,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin push commission notification: ' . $e->getMessage(), [
                'commission_id' => $event->commission->id,
            ]);
        }
    }
}
