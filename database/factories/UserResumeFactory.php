<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserResume;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserResume>
 */
class UserResumeFactory extends Factory
{
    protected $model = UserResume::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'headline' => 'صداپیشه و راوی',
            'years_of_experience' => 5,
            'about' => 'رزومه آزمایشی مانجی',
            'specialties' => ['راوی', 'شخصیت کودک'],
            'experience' => [],
            'education' => [],
            'awards' => [],
            'languages' => [['name' => 'فارسی', 'level' => 'native']],
            'demo_url' => null,
            'social_links' => ['instagram' => null, 'website' => null, 'aparat' => null],
            'is_public' => false,
            'show_in_talent_directory' => false,
            'published_at' => null,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'published_at' => now(),
        ]);
    }
}
