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
            $this->telegramService->sendNewUserNotification($event->user);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram new user notification: ' . $e->getMessage(), [
                'user_id' => $event->user->id
            ]);
        }
    }
}
