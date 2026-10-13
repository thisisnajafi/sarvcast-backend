<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 */
class StoryFactory extends Factory
{
    protected $model = Story::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'image_url' => 'stories/default.jpg',
            'category_id' => Category::factory(),
            'age_group' => '6-8',
            'language' => 'fa',
            'duration' => 12,
            'status' => 'draft',
            'workflow_status' => Story::WORKFLOW_WRITTEN,
        ];
    }
}
