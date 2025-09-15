<?php

namespace App\Notifications;

use App\Jobs\SendSmsNotification;
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
