<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 100000,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'transaction_id' => fake()->unique()->uuid(),
            'status' => 'pending',
        ];
    }
}
