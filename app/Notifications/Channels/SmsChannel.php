<?php

namespace App\Notifications\Channels;

use App\Jobs\SendSmsNotification;
use App\Support\NewUserNotificationLogger;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        $data = $notification->toSms($notifiable);

        $isWelcome = ($data['template_key'] ?? null) === 'welcome';
        if ($isWelcome && ! isset($data['phone_number'])) {
            NewUserNotificationLogger::warning('welcome_sms_early_return', [
                'source' => self::class,
                'reason' => 'phone_number_missing',
                'channel' => 'sms',
            ]);
        }

        if (isset($data['phone_number'])) {
            if ($isWelcome) {
                NewUserNotificationLogger::debug('welcome_sms_dispatched', [
                    'source' => self::class,
                    'template_key' => 'welcome',
                    'channel' => 'sms',
                    'queued' => true,
                    'queue_connection' => (string) config('queue.default'),
                    'provider' => $data['provider'] ?? null,
                ]);
            }
            SendSmsNotification::dispatch(
                $data['phone_number'],
                $data['message'] ?? null,
                $data['provider'] ?? null,
                $data['template_key'] ?? null,
                $data['variables'] ?? []
            );
        }
    }
}
