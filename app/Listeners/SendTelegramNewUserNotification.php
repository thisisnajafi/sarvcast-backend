<?php

namespace App\Listeners;

use App\Events\NewUserRegistrationEvent;
use App\Services\TelegramNotificationService;
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
        try {
            $user = $event->user;

            Log::info('Processing Telegram new user notification', [
                'user_id' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'user_phone' => $user->phone_number,
                'user_role' => $user->role
            ]);

            // Use cache lock to prevent duplicate notifications (rare but possible)
            $lockKey = "telegram_new_user_{$user->id}";
            $lock = \Cache::lock($lockKey, 10);

            if ($lock->get()) {
                try {
                    $success = $this->telegramService->sendNewUserNotification($user);

                    if ($success) {
                        Log::info('Telegram new user notification sent successfully', [
                            'user_id' => $user->id,
                            'user_name' => $user->first_name . ' ' . $user->last_name
                        ]);
                    } else {
                        Log::error('Failed to send Telegram new user notification', [
                            'user_id' => $user->id,
                            'telegram_service_error' => true
                        ]);
                    }
                } finally {
                    $lock->release();
                }
            } else {
                Log::info('Skipping Telegram new user notification - lock could not be acquired', [
                    'user_id' => $user->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram new user notification: ' . $e->getMessage(), [
                'user_id' => $event->user->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
