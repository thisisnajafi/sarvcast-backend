<?php

namespace App\Listeners;

use App\Events\NewUserRegistrationEvent;
use App\Services\InAppNotificationService;
use App\Support\NewUserNotificationLogger;
use Illuminate\Support\Facades\Log;

class SendWelcomeInAppNotification
{
    public function __construct(
        protected InAppNotificationService $notificationService
    ) {
    }

    public function handle(NewUserRegistrationEvent $event): void
    {
        $newUserId = (int) $event->user->id;
        NewUserNotificationLogger::debug('listener_started', [
            'listener' => self::class,
            'new_user_id' => $newUserId,
            'queued' => false,
            'queue_connection' => null,
        ]);
        NewUserNotificationLogger::debug('recipients_resolved', [
            'listener' => self::class,
            'new_user_id' => $newUserId,
            'recipient_user_ids' => [$newUserId],
            'recipient_count' => 1,
        ]);
        try {
            NewUserNotificationLogger::debug('notification_dispatched', [
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'channel' => 'in_app',
                'queued' => false,
                'queue_connection' => null,
            ]);
            $this->notificationService->createWelcomeNotification($newUserId);
            NewUserNotificationLogger::debug('listener_succeeded', [
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'channel' => 'in_app',
            ]);
        } catch (\Exception $e) {
            NewUserNotificationLogger::error('listener_failed', array_merge([
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'channel' => 'in_app',
            ], NewUserNotificationLogger::exceptionContext($e)));
            Log::warning('Welcome notification failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
