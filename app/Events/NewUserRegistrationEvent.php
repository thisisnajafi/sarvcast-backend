<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Support\NewUserNotificationLogger;

class NewUserRegistrationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $queueConnection = (string) config('queue.default');
        NewUserNotificationLogger::debug('event_fired', [
            'event' => self::class,
            'new_user_id' => (int) $user->id,
            'listeners' => [
                [
                    'listener' => 'App\\Listeners\\SendAdminPushNewUserNotification',
                    'queued' => true,
                    'queue_connection' => $queueConnection,
                    'after_commit' => true,
                ],
                [
                    'listener' => 'App\\Listeners\\SendWelcomeInAppNotification',
                    'queued' => false,
                    'queue_connection' => null,
                ],
                [
                    'listener' => 'App\\Listeners\\SendTelegramNewUserNotification',
                    'queued' => true,
                    'queue_connection' => $queueConnection,
                ],
            ],
        ]);
    }
}
