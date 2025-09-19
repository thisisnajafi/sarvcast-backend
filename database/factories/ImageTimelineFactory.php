<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\EpisodeVoiceActor;
use App\Models\ImageTimeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImageTimeline>
 */
class ImageTimelineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ImageTimeline::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transitionTypes = ['fade', 'slide', 'cut', 'dissolve', 'wipe'];
        $sceneDescriptions = [
            'شروع داستان در جنگل',
            'ملاقات با شاهزاده',
            'سفر به قصر',
            'نبرد با اژدها',
            'پایان خوش داستان'
        ];

        return [
            'episode_id' => Episode::factory(),
            'voice_actor_id' => EpisodeVoiceActor::factory(),
            'start_time' => $this->faker->numberBetween(0, 100),
            'end_time' => $this->faker->numberBetween(101, 300),
            'image_url' => $this->faker->imageUrl(800, 600, 'fantasy'),
            'image_order' => $this->faker->numberBetween(1, 10),
            'scene_description' => $this->faker->randomElement($sceneDescriptions),
            'transition_type' => $this->faker->randomElement($transitionTypes),
            'is_key_frame' => $this->faker->boolean(30), // 30% chance of being key frame
        ];
    }

    /**
     * Indicate that the timeline is a key frame.
     */
    public function keyFrame(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_key_frame' => true,
        ]);
    }

    /**
     * Indicate that the timeline has a specific transition type.
     */
    public function transitionType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'transition_type' => $type,
        ]);
    }

    /**
     * Set specific time range for the timeline.
     */
    public function timeRange(int $startTime, int $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Indicate that the timeline has no voice actor.
     */
    public function withoutVoiceActor(): static
    {
        return $this->state(fn (array $attributes) => [
            'voice_actor_id' => null,
        ]);
    }
}
