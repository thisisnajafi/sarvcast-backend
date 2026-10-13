<?php

namespace Database\Factories;

use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoinTransaction>
 */
class CoinTransactionFactory extends Factory
{
    protected $model = CoinTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_type' => 'earned',
            'amount' => 10,
            'description' => fake()->sentence(),
            'transacted_at' => now(),
        ];
    }
}
