<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Episode;

class TestEpisodesEndpoint extends Command
{
    protected $signature = 'test:episodes-endpoint {story_id?}';
    protected $description = 'Test episodes endpoint to see what episodes are returned';

    public function handle()
    {
        $storyId = $this->argument('story_id') ?? 75;
        $story = Story::find($storyId);
        
        if (!$story) {
            $this->error("Story with ID {$storyId} not found");
            return 1;
        }
        
        $this->info("Story: {$story->title}");
        $this->info("Total episodes in database: " . $story->episodes()->count());
        $this->info("Published episodes: " . $story->episodes()->published()->count());
        $this->info("Draft episodes: " . $story->episodes()->where('status', 'draft')->count());
        
        // Test as regular user (published only)
        $publishedEpisodes = $story->episodes()->published()->get();
        $this->info("\n📊 Published episodes (regular user view): " . $publishedEpisodes->count());
        foreach ($publishedEpisodes as $ep) {
            $this->line("   - Episode {$ep->episode_number}: {$ep->title} (Status: {$ep->status})");
        }
        
        // Test as admin (all episodes)
        $allEpisodes = $story->episodes()->get();
        $this->info("\n📊 All episodes (admin view): " . $allEpisodes->count());
        foreach ($allEpisodes as $ep) {
            $this->line("   - Episode {$ep->episode_number}: {$ep->title} (Status: {$ep->status})");
        }
        
        return 0;
    }
}

