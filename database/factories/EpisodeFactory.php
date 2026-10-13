<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        return [
            'story_id' => Story::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'duration' => 10,
            'episode_number' => fake()->unique()->numberBetween(1, 50000),
            'status' => 'draft',
            'is_premium' => false,
        ];
    }
}
