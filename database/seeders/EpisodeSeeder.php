<?php

namespace Database\Seeders;

use App\Models\Episode;
use App\Models\Story;
use App\Models\Person;
use Illuminate\Database\Seeder;

class EpisodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stories = Story::all();
        $narrators = Person::where('role', 'narrator')->get();

        foreach ($stories as $story) {
            // Create 3-5 episodes for each story
            $episodeCount = rand(3, 5);
            
            for ($i = 1; $i <= $episodeCount; $i++) {
                Episode::create([
                    'story_id' => $story->id,
                    'title' => "قسمت {$i}: " . $this->generateEpisodeTitle($story->title, $i),
                    'episode_number' => $i,
                    'description' => "قسمت {$i} از داستان {$story->title}",
                    'narrator_id' => $narrators->random()->id,
                    'duration' => rand(15, 45), // Random duration between 15-45 minutes
                    'file_size' => rand(10, 50), // Random file size between 10-50 MB
                    'order' => $i,
                    'status' => 'published',
                    'is_premium' => $story->is_premium,
                    'is_free' => !$story->is_premium,
                    'play_count' => rand(50, 500),
                    'published_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }

    /**
     * Generate episode title based on story title and episode number
     */
    private function generateEpisodeTitle(string $storyTitle, int $episodeNumber): string
    {
        $titles = [
            'شروع ماجرا',
            'در میانه راه',
            'نقطه اوج',
            'پایان خوش',
            'ماجراهای جدید',
            'راز آشکار',
            'نبرد نهایی',
            'پیروزی',
            'بازگشت',
            'آغاز جدید',
        ];

        return $titles[($episodeNumber - 1) % count($titles)];
    }
}