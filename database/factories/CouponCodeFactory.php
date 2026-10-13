<?php

namespace Database\Factories;

use App\Models\CouponCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponCode>
 */
class CouponCodeFactory extends Factory
{
    protected $model = CouponCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('MANJI-####')),
            'name' => fake()->words(2, true),
            'type' => 'percentage',
            'discount_value' => 10,
            'created_by' => User::factory(),
            'is_active' => true,
        ];
    }
}
