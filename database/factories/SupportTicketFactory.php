<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'user_id'     => null,
            'subject'     => $this->faker->sentence(4),
            'status'      => $this->faker->randomElement(['open', 'pending', 'closed', 'resolved']),
            'priority'    => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'channel'     => 'web',
            'assigned_to' => null,
            'closed_at'   => null,
        ];
    }
}
