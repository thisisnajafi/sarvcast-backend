<?php

namespace App\Listeners;

use App\Events\NewUserRegistrationEvent;
use App\Models\User;
use App\Services\AdminPushNotificationService;
use App\Support\NewUserNotificationLogger;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAdminPushNewUserNotification implements ShouldHandleEventsAfterCommit
{
    private const NEW_USER_NOTIFICATION_EVENT_MARKER = 'new_user_registration_notification';
    private const NOTIFICATIONS_LOG_CHANNEL = 'notifications';
    private const SYNC_ATTEMPT = 1;

    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {}

    public function handle(NewUserRegistrationEvent $event): void
    {
        $queueContext = NewUserNotificationLogger::queueContext($this);
        $newUserId = (int) $event->user->id;
        NewUserNotificationLogger::debug('listener_started', array_merge($queueContext, [
            'listener' => self::class,
            'new_user_id' => $newUserId,
            'after_commit' => true,
            'attempt' => self::SYNC_ATTEMPT,
            'queued' => false,
            'queue_connection' => null,
        ]));
        try {
            $user = $event->user;
            // Hold a long-lived marker so a second dispatch for the same user
            // cannot send again (do not release after send).
            $dedupeKey = "admin_push_new_user_{$user->id}";
            if (! Cache::add($dedupeKey, true, now()->addDays(30))) {
                $recipientAdminIds = $this->resolveActiveAdminIds();
                NewUserNotificationLogger::debug('recipients_resolved', [
                    'listener' => self::class,
                    'new_user_id' => $newUserId,
                    'recipient_admin_ids' => $recipientAdminIds,
                    'recipient_count' => count($recipientAdminIds),
                ]);
                $this->logNewUserRegistrationChannel(
                    (int) $user->id,
                    $recipientAdminIds,
                    'db',
                    'skip',
                    'duplicate_event_deduped'
                );
                $this->logNewUserRegistrationChannel(
                    (int) $user->id,
                    $recipientAdminIds,
                    'push',
                    'skip',
                    'duplicate_event_deduped'
                );
                NewUserNotificationLogger::warning('listener_early_return', array_merge($queueContext, [
                    'listener' => self::class,
                    'new_user_id' => $newUserId,
                    'reason' => 'duplicate_event_deduped',
                    'recipient_admin_ids' => $recipientAdminIds,
                ]));

                return;
            }

            NewUserNotificationLogger::debug('notification_dispatched', array_merge($queueContext, [
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'channel' => 'admin_push',
            ]));
            $sentCount = $this->adminPushService->sendNewUserNotification($user);
            NewUserNotificationLogger::debug('listener_succeeded', [
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'sent_count' => $sentCount,
            ]);
        } catch (Throwable $throwable) {
            $recipientAdminIds = $this->resolveActiveAdminIds();
            $this->logNewUserRegistrationChannel(
                (int) $event->user->id,
                $recipientAdminIds,
                'db',
                'fail',
                'listener_exception'
            );
            $this->logNewUserRegistrationChannel(
                (int) $event->user->id,
                $recipientAdminIds,
                'push',
                'fail',
                'listener_exception'
            );
            NewUserNotificationLogger::error('listener_failed', array_merge([
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'recipient_admin_ids' => $recipientAdminIds,
            ], NewUserNotificationLogger::exceptionContext($throwable)));
            Log::error('Failed to handle admin new user notification listener', [
                'user_id' => $event->user->id,
                'exception_class' => get_class($throwable),
            ]);
        }
    }

    private function resolveActiveAdminIds(): array
    {
        return User::query()
            ->admins()
            ->whereIn('status', User::loginAllowedStatuses())
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function logNewUserRegistrationChannel(
        int $newUserId,
        array $recipientAdminIds,
        string $channel,
        string $result,
        string $reason
    ): void {
        try {
            Log::channel(self::NOTIFICATIONS_LOG_CHANNEL)->info(self::NEW_USER_NOTIFICATION_EVENT_MARKER, [
                'event_marker' => self::NEW_USER_NOTIFICATION_EVENT_MARKER,
                'new_user_id' => $newUserId,
                'recipient_admin_ids' => $recipientAdminIds,
                'channel' => $channel,
                'result' => $result,
                'reason' => $reason,
            ]);
        } catch (Throwable $throwable) {
            // Logging issues must never interrupt notification flow.
        }
    }
}
