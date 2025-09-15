<?php

namespace App\Notifications\Channels;

use App\Jobs\SendSmsNotification;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        $data = $notification->toSms($notifiable);

        if (isset($data['phone_number'])) {
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
