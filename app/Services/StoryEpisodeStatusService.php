<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Story;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class StoryEpisodeStatusService
{
    /**
     * When a story status changes, enforce episode status rules.
     */
    public function cascadeEpisodesFromStory(Story $story): void
    {
        $storyStatus = $story->status;

        if ($storyStatus === 'draft') {
            $story->episodes()->update([
                'status' => 'draft',
                'published_at' => null,
            ]);

            return;
        }

        if ($storyStatus === 'archived') {
            $story->episodes()->update([
                'status' => 'archived',
                'published_at' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function storyStatusAttributes(string $status): array
    {
        $attributes = ['status' => $status];

        if ($status === 'published') {
            $attributes['published_at'] = now();
        }

        if (in_array($status, ['draft', 'archived'], true)) {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function episodeStatusAttributes(string $status): array
    {
        $attributes = ['status' => $status];

        if ($status === 'published') {
            $attributes['published_at'] = now();
        }

        if (in_array($status, ['draft', 'archived'], true)) {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }

    public function assertEpisodeStatusAllowed(Story $story, string $newStatus): void
    {
        if ($newStatus !== 'published') {
            return;
        }

        if ($story->status !== 'published') {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'اپیزود را نمی‌توان منتشر کرد مگر اینکه داستان والد منتشر شده باشد.',
                'error' => 'STORY_NOT_PUBLISHED',
            ], 422));
        }
    }

    public function applyStoryStatus(Story $story, string $status): void
    {
        $story->update($this->storyStatusAttributes($status));
        $story->refresh();
        $this->cascadeEpisodesFromStory($story);
    }

    /**
     * Fix episodes that still show as published while their parent story is draft/archived.
     *
     * @return array{stories_processed: int, episodes_updated: int}
     */
    public function syncEpisodesToStoryStatuses(): array
    {
        $storiesProcessed = 0;
        $episodesUpdated = 0;

        Story::query()
            ->whereIn('status', ['draft', 'archived'])
            ->chunkById(100, function ($stories) use (&$storiesProcessed, &$episodesUpdated) {
                foreach ($stories as $story) {
                    if ($story->status === 'draft') {
                        $count = $story->episodes()->where('status', '!=', 'draft')->count();
                    } else {
                        $count = $story->episodes()->where('status', '!=', 'archived')->count();
                    }

                    if ($count === 0) {
                        continue;
                    }

                    $this->cascadeEpisodesFromStory($story);
                    $storiesProcessed++;
                    $episodesUpdated += $count;
                }
            });

        return [
            'stories_processed' => $storiesProcessed,
            'episodes_updated' => $episodesUpdated,
        ];
    }

    public function applyEpisodeStatus(Episode $episode, string $status): void
    {
        $story = $episode->story ?? Story::find($episode->story_id);

        if (! $story) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'داستان والد یافت نشد.',
                'error' => 'STORY_NOT_FOUND',
            ], 422));
        }

        $this->assertEpisodeStatusAllowed($story, $status);

        $episode->update($this->episodeStatusAttributes($status));
    }

    /**
     * @param  iterable<int, Story>  $stories
     */
    public function bulkApplyStoryStatus(iterable $stories, string $status): void
    {
        DB::transaction(function () use ($stories, $status) {
            foreach ($stories as $story) {
                $this->applyStoryStatus($story, $status);
            }
        });
    }

    /**
     * @param  iterable<int, Episode>  $episodes
     */
    public function bulkApplyEpisodeStatus(iterable $episodes, string $status): void
    {
        DB::transaction(function () use ($episodes, $status) {
            foreach ($episodes as $episode) {
                $this->applyEpisodeStatus($episode->loadMissing('story'), $status);
            }
        });
    }
}
