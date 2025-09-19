<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\AffiliateCommission;

class InfluencerCommissionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $commission;

    /**
     * Create a new event instance.
     */
    public function __construct(AffiliateCommission $commission)
    {
        $this->commission = $commission;
    }
}
