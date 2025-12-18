<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Story;

class UpdateCategoryStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:update-statistics {--force : Force update even if statistics seem correct}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update category statistics (story_count, total_episodes, total_duration, average_rating)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting category statistics update...');
        
        $categories = Category::all();
        $force = $this->option('force');
        
        $progressBar = $this->output->createProgressBar($categories->count());
        $progressBar->start();
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($categories as $category) {
            // Count published stories
            $publishedStoriesCount = $category->stories()
                ->where('status', 'published')
                ->count();

            // Count total stories
            $totalStoriesCount = $category->stories()->count();

            // Calculate total episodes from published stories
            $totalEpisodes = $category->stories()
                ->where('status', 'published')
                ->sum('total_episodes');

            // Calculate total duration from published stories
            $totalDuration = $category->stories()
                ->where('status', 'published')
                ->sum('duration');

            // Calculate average rating from published stories
            $averageRating = $category->stories()
                ->where('status', 'published')
                ->where('rating', '>', 0)
                ->avg('rating') ?? 0;

            // Check if update is needed
            $needsUpdate = $force || 
                $category->story_count != $publishedStoriesCount ||
                $category->total_episodes != $totalEpisodes ||
                $category->total_duration != $totalDuration ||
                abs($category->average_rating - round($averageRating, 2)) > 0.01;

            if ($needsUpdate) {
                $category->update([
                    'story_count' => $publishedStoriesCount,
                    'total_episodes' => $totalEpisodes,
                    'total_duration' => $totalDuration,
                    'average_rating' => round($averageRating, 2),
                ]);
                
                $updated++;
                
                if ($this->option('verbose')) {
                    $this->line("\nUpdated category: {$category->name}");
                    $this->line("  - Story count: {$category->story_count} → {$publishedStoriesCount}");
                    $this->line("  - Total episodes: {$category->total_episodes} → {$totalEpisodes}");
                    $this->line("  - Total duration: {$category->total_duration} → {$totalDuration}");
                    $this->line("  - Average rating: {$category->average_rating} → " . round($averageRating, 2));
                }
            } else {
                $skipped++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("Category statistics update completed!");
        $this->info("Updated: {$updated} categories");
        $this->info("Skipped: {$skipped} categories (no changes needed)");
        
        // Show summary statistics
        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Categories', $categories->count()],
                ['Total Stories', Story::count()],
                ['Published Stories', Story::where('status', 'published')->count()],
                ['Total Episodes', Story::where('status', 'published')->sum('total_episodes')],
                ['Total Duration (minutes)', Story::where('status', 'published')->sum('duration')],
                ['Average Rating', round(Story::where('status', 'published')->where('rating', '>', 0)->avg('rating') ?? 0, 2)],
            ]
        );
        
        return Command::SUCCESS;
    }
}