<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Person;
use App\Models\EpisodeVoiceActor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EpisodeVoiceActor>
 */
class EpisodeVoiceActorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EpisodeVoiceActor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['narrator', 'character', 'voice_over', 'background'];
        $characterNames = ['راوی', 'شاهزاده', 'شاه', 'ملکه', 'جنگجو', 'جادوگر', 'کشاورز', 'کودک'];
        
        return [
            'episode_id' => Episode::factory(),
            'person_id' => Person::factory(),
            'role' => $this->faker->randomElement($roles),
            'character_name' => $this->faker->optional(0.7)->randomElement($characterNames),
            'start_time' => $this->faker->numberBetween(0, 100),
            'end_time' => $this->faker->numberBetween(101, 300),
            'voice_description' => $this->faker->optional(0.8)->sentence(),
            'is_primary' => $this->faker->boolean(20), // 20% chance of being primary
        ];
    }

    /**
     * Indicate that the voice actor is a narrator.
     */
    public function narrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'narrator',
            'character_name' => null,
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the voice actor is a character.
     */
    public function character(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'character',
            'character_name' => $this->faker->randomElement(['شاهزاده', 'شاه', 'ملکه', 'جنگجو']),
            'is_primary' => false,
        ]);
    }

    /**
     * Indicate that the voice actor is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Set specific time range for the voice actor.
     */
    public function timeRange(int $startTime, int $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
