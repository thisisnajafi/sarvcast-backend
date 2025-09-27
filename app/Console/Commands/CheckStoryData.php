<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;

class CheckStoryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:check-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check story and episode data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking story and episode data...');
        
        $storiesCount = Story::count();
        $episodesCount = Episode::count();
        $categoriesCount = Category::count();
        
        $this->info("Stories: {$storiesCount}");
        $this->info("Episodes: {$episodesCount}");
        $this->info("Categories: {$categoriesCount}");
        
        if ($storiesCount > 0) {
            $this->info("\nStory Details:");
            $stories = Story::with(['episodes', 'category'])->get();
            
            foreach ($stories as $story) {
                $this->info("Story: {$story->title}");
                $this->info("  - Status: {$story->status}");
                $this->info("  - Duration: {$story->duration} minutes");
                $this->info("  - Category: " . ($story->category ? $story->category->name : 'None'));
                $this->info("  - Episodes: " . $story->episodes->count());
                
                foreach ($story->episodes as $episode) {
                    $this->info("    Episode: {$episode->title}");
                    $this->info("      - Duration: {$episode->duration} seconds");
                    $this->info("      - Status: {$episode->status}");
                }
                $this->info("");
            }
        }
        
        if ($categoriesCount > 0) {
            $this->info("Category Details:");
            $categories = Category::withCount(['stories', 'stories as published_stories_count' => function($q) {
                $q->where('status', 'published');
            }])->get();
            
            foreach ($categories as $category) {
                $this->info("Category: {$category->name}");
                $this->info("  - Active: " . ($category->is_active ? 'Yes' : 'No'));
                $this->info("  - Total Stories: {$category->stories_count}");
                $this->info("  - Published Stories: {$category->published_stories_count}");
                $this->info("");
            }
        }
        
        return Command::SUCCESS;
    }
}