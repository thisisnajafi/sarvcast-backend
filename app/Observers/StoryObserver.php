<?php

namespace App\Observers;

use App\Models\Story;
use App\Models\Category;

class StoryObserver
{
    /**
     * Handle the Story "created" event.
     */
    public function created(Story $story): void
    {
        $this->updateCategoryStatistics($story);
    }

    /**
     * Handle the Story "updated" event.
     */
    public function updated(Story $story): void
    {
        // Check if category_id or status changed
        if ($story->isDirty(['category_id', 'status'])) {
            $this->updateCategoryStatistics($story);
            
            // If category changed, update the old category too
            if ($story->isDirty('category_id')) {
                $oldCategoryId = $story->getOriginal('category_id');
                if ($oldCategoryId) {
                    $this->updateCategoryStatisticsById($oldCategoryId);
                }
            }
        }
    }

    /**
     * Handle the Story "deleted" event.
     */
    public function deleted(Story $story): void
    {
        $this->updateCategoryStatistics($story);
    }

    /**
     * Handle the Story "restored" event.
     */
    public function restored(Story $story): void
    {
        $this->updateCategoryStatistics($story);
    }

    /**
     * Handle the Story "force deleted" event.
     */
    public function forceDeleted(Story $story): void
    {
        $this->updateCategoryStatistics($story);
    }

    /**
     * Update category statistics based on stories
     */
    private function updateCategoryStatistics(Story $story): void
    {
        if ($story->category_id) {
            $this->updateCategoryStatisticsById($story->category_id);
        }
    }

    /**
     * Update category statistics by category ID
     */
    private function updateCategoryStatisticsById(int $categoryId): void
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return;
        }

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

        // Update category statistics
        $category->update([
            'story_count' => $publishedStoriesCount,
            'total_episodes' => $totalEpisodes,
            'total_duration' => $totalDuration,
            'average_rating' => round($averageRating, 2),
        ]);
    }
}