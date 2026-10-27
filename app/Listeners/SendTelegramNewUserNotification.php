<?php

namespace App\Listeners;

use App\Events\NewUserRegistrationEvent;
use App\Services\TelegramNotificationService;
use App\Support\NewUserNotificationLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTelegramNewUserNotification implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(NewUserRegistrationEvent $event): void
    {
        $queueContext = NewUserNotificationLogger::queueContext($this);
        $newUserId = (int) $event->user->id;
        NewUserNotificationLogger::debug('listener_started', array_merge($queueContext, [
            'listener' => self::class,
            'new_user_id' => $newUserId,
            'attempt' => $this->attempts(),
        ]));
        try {
            $user = $event->user;

            Log::info('Processing Telegram new user notification', [
                'user_id' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'user_phone' => $user->phone_number,
                'user_role' => $user->role
            ]);
            NewUserNotificationLogger::debug('recipients_resolved', [
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'recipient' => 'telegram_chat',
                'recipient_count' => 1,
            ]);

            // Hold a long-lived marker so a second dispatch for the same user
            // cannot send again (do not release after send). Match admin push TTL.
            $dedupeKey = "telegram_new_user_{$user->id}";
            if (! \Cache::add($dedupeKey, true, now()->addDays(30))) {
                Log::info('Skipping Telegram new user notification - already sent', [
                    'user_id' => $user->id,
                ]);
                NewUserNotificationLogger::warning('listener_early_return', array_merge($queueContext, [
                    'listener' => self::class,
                    'new_user_id' => $newUserId,
                    'reason' => 'duplicate_event_deduped',
                ]));

                return;
            }

            NewUserNotificationLogger::debug('notification_dispatched', array_merge($queueContext, [
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'channel' => 'telegram',
            ]));
            $success = $this->telegramService->sendNewUserNotification($user);

            if ($success) {
                Log::info('Telegram new user notification sent successfully', [
                    'user_id' => $user->id,
                    'user_name' => $user->first_name . ' ' . $user->last_name
                ]);
                NewUserNotificationLogger::debug('listener_succeeded', [
                    'listener' => self::class,
                    'new_user_id' => $newUserId,
                    'channel' => 'telegram',
                ]);
            } else {
                Log::error('Failed to send Telegram new user notification', [
                    'user_id' => $user->id,
                    'telegram_service_error' => true
                ]);
                NewUserNotificationLogger::error('listener_failed', [
                    'listener' => self::class,
                    'new_user_id' => $newUserId,
                    'channel' => 'telegram',
                    'reason' => 'telegram_service_returned_false',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram new user notification: ' . $e->getMessage(), [
                'user_id' => $event->user->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            NewUserNotificationLogger::error('listener_failed', array_merge([
                'listener' => self::class,
                'new_user_id' => $newUserId,
                'channel' => 'telegram',
            ], NewUserNotificationLogger::exceptionContext($e)));
        }
    }
}
