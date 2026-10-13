<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => '1month',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price' => 100000,
            'currency' => 'IRR',
            'auto_renew' => false,
        ];
    }
}
