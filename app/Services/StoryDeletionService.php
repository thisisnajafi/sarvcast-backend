<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Story;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoryDeletionService
{
    public function __construct(
        private readonly EpisodeAssetCleanupService $assetCleanup,
        private readonly StoryEditorRepository $editorRepository,
    ) {}

    /**
     * Fully delete a story: episodes (scripts/audio), characters, editor MD folders, then the story row.
     */
    public function delete(Story $story): void
    {
        DB::transaction(function () use ($story) {
            $storyId = (int) $story->id;
            $editorSlugs = $this->editorRepository->resolveStoryEditorSlugs($storyId);

            $story->episodes()
                ->get()
                ->each(function (Episode $episode) {
                    $episode->delete();
                });

            $story->characters()
                ->get()
                ->each(function (Character $character) {
                    $this->assetCleanup->cleanupCharacterMedia($character);
                    $character->delete();
                });

            $this->assetCleanup->cleanupStoryMedia($story);
            $this->assetCleanup->cleanupStoryProductionRecords($storyId);

            foreach ($editorSlugs as $slug) {
                try {
                    $this->editorRepository->deleteStoryDirectory($slug);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete story-editor directory during story delete', [
                        'story_id' => $storyId,
                        'story_slug' => $slug,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $story->delete();
        });
    }

    /**
     * @param  list<int>  $storyIds
     */
    public function deleteMany(array $storyIds): void
    {
        if ($storyIds === []) {
            return;
        }

        Story::query()
            ->whereIn('id', $storyIds)
            ->get()
            ->each(fn (Story $story) => $this->delete($story));
    }
}
