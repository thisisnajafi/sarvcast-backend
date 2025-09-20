<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Episode;

class UpdateStoryStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:update-statistics {--story-id= : Update specific story by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update story statistics (episode count, duration, etc.) based on actual episodes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storyId = $this->option('story-id');
        
        if ($storyId) {
            $story = Story::find($storyId);
            if (!$story) {
                $this->error("Story with ID {$storyId} not found.");
                return 1;
            }
            
            $this->updateStoryStatistics($story);
            $this->info("Updated statistics for story: {$story->title}");
        } else {
            $this->info('Updating statistics for all stories...');
            
            $stories = Story::all();
            $bar = $this->output->createProgressBar($stories->count());
            $bar->start();
            
            foreach ($stories as $story) {
                $this->updateStoryStatistics($story);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("Updated statistics for {$stories->count()} stories.");
        }
        
        return 0;
    }

    /**
     * Update statistics for a specific story
     */
    private function updateStoryStatistics(Story $story): void
    {
        // Calculate dynamic values
        $totalEpisodes = $story->episodes()->count();
        $publishedEpisodes = $story->episodes()->where('status', 'published')->count();
        $freeEpisodes = $story->episodes()
            ->where('status', 'published')
            ->where('is_premium', false)
            ->count();
        $totalDuration = $story->episodes()
            ->where('status', 'published')
            ->sum('duration') ?? 0;

        // Update the story
        $story->update([
            'total_episodes' => $totalEpisodes,
            'duration' => $totalDuration,
            'free_episodes' => $freeEpisodes,
        ]);

        // Log the changes
        $this->line("Story '{$story->title}': {$totalEpisodes} episodes, {$publishedEpisodes} published, {$freeEpisodes} free, {$totalDuration} seconds");
    }
}