<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Payment;
use App\Models\Subscription;

class SalesNotificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $subscription;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, Subscription $subscription = null)
    {
        $this->payment = $payment;
        $this->subscription = $subscription;
    }
}
