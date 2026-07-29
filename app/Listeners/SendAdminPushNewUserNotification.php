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
            // Hold a long-lived marker so a second dispatch for the same user
            // cannot send again (do not release after send).
            $dedupeKey = "admin_push_new_user_{$user->id}";
            if (! Cache::add($dedupeKey, true, now()->addMinutes(10))) {
                Log::info('Skipping duplicate admin push new user notification', [
                    'user_id' => $user->id,
                ]);

                return;
            }

            $sent = $this->adminPushService->sendNewUserNotification($user);

            if ($sent > 0) {
                Log::info('Admin push new user notification sent', [
                    'user_id' => $user->id,
                    'admin_count' => $sent,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin push new user notification: ' . $e->getMessage(), [
                'user_id' => $event->user->id,
            ]);
        }
    }
}
