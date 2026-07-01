<?php

namespace App\Listeners;

use App\Events\NewUserRegistrationEvent;
use App\Services\InAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendWelcomeInAppNotification
{
    public function __construct(
        protected InAppNotificationService $notificationService
    ) {
    }

    public function handle(NewUserRegistrationEvent $event): void
    {
        try {
            $this->notificationService->createWelcomeNotification($event->user->id);
        } catch (\Exception $e) {
            Log::warning('Welcome notification failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
