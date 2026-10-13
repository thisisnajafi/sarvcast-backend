<?php

namespace Database\Factories;

use App\Models\Story;
use App\Models\StoryComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoryComment>
 */
class StoryCommentFactory extends Factory
{
    protected $model = StoryComment::class;

    public function definition(): array
    {
        return [
            'story_id' => Story::factory(),
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'is_approved' => false,
            'is_visible' => true,
        ];
    }
}
