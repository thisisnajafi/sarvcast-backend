<?php

namespace App\Notifications;

use App\Jobs\SendSmsNotification;
use App\Support\NewUserNotificationLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;
    protected $templateKey;
    protected $variables;
    protected $provider;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $message = null, string $templateKey = null, array $variables = [], string $provider = null)
    {
        $this->message = $message;
        $this->templateKey = $templateKey;
        $this->variables = $variables;
        $this->provider = $provider;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        if ($this->templateKey === 'welcome') {
            NewUserNotificationLogger::debug('welcome_sms_notification_via', [
                'listener' => self::class,
                'template_key' => 'welcome',
                'channel' => 'sms',
                'queued' => true,
                'queue_connection' => $this->connection ?: (string) config('queue.default'),
                'queue' => $this->queue,
            ]);
        }

        return ['sms'];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable): array
    {
        return [
            'phone_number' => $notifiable->phone_number,
            'message' => $this->message,
            'template_key' => $this->templateKey,
            'variables' => $this->variables,
            'provider' => $this->provider
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => $this->message,
            'template_key' => $this->templateKey,
            'variables' => $this->variables,
            'provider' => $this->provider
        ];
    }
}
