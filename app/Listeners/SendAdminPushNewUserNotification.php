<?php

namespace App\Listeners;

use App\Events\NewUserRegistrationEvent;
use App\Services\AdminPushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendAdminPushNewUserNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {}

    public function handle(NewUserRegistrationEvent $event): void
    {
        try {
            $user = $event->user;
            $lockKey = "admin_push_new_user_{$user->id}";
            $lock = Cache::lock($lockKey, 10);

            if (!$lock->get()) {
                return;
            }

            try {
                $sent = $this->adminPushService->sendNewUserNotification($user);

                if ($sent > 0) {
                    Log::info('Admin push new user notification sent', [
                        'user_id' => $user->id,
                        'admin_count' => $sent,
                    ]);
                }
            } finally {
                $lock->release();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin push new user notification: ' . $e->getMessage(), [
                'user_id' => $event->user->id,
            ]);
        }
    }
}
