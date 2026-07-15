<?php

namespace App\Observers;

use App\Models\Episode;
use App\Models\Story;
use App\Services\EpisodeAssetCleanupService;

class EpisodeObserver
{
    public function __construct(
        private readonly EpisodeAssetCleanupService $assetCleanup,
    ) {}

    /**
     * Handle the Episode "deleting" event.
     */
    public function deleting(Episode $episode): void
    {
        $this->assetCleanup->cleanupEpisodeAssets($episode);
    }
    /**
     * Handle the Episode "created" event.
     */
    public function created(Episode $episode): void
    {
        $this->updateStoryStatistics($episode->story);
    }

    /**
     * Handle the Episode "updated" event.
     */
    public function updated(Episode $episode): void
    {
        // Check if status or duration changed
        if ($episode->isDirty(['status', 'duration', 'is_premium'])) {
            $this->updateStoryStatistics($episode->story);
        }
    }

    /**
     * Handle the Episode "deleted" event.
     */
    public function deleted(Episode $episode): void
    {
        $this->updateStoryStatistics($episode->story);
    }

    /**
     * Handle the Episode "restored" event.
     */
    public function restored(Episode $episode): void
    {
        $this->updateStoryStatistics($episode->story);
    }

    /**
     * Handle the Episode "force deleted" event.
     */
    public function forceDeleted(Episode $episode): void
    {
        $this->updateStoryStatistics($episode->story);
    }

    /**
     * Update story statistics based on episodes
     */
    private function updateStoryStatistics(Story $story): void
    {
        $story->updateStatistics();
    }
}