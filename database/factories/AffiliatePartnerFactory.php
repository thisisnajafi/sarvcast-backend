<?php

namespace Database\Factories;

use App\Models\AffiliatePartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliatePartner>
 */
class AffiliatePartnerFactory extends Factory
{
    protected $model = AffiliatePartner::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'type' => 'influencer',
            'status' => 'pending',
            'commission_rate' => 10,
            'is_verified' => false,
        ];
    }
}
