<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Models\StoryProductionAsset;
use App\Models\StoryProductionFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncTimelineFromScenesService
{
    /**
     * Rebuild episode image timeline frames from uploaded scene production assets,
     * evenly splitting the audio duration across scenes that have images.
     *
     * @return array{frames: list<ImageTimeline>, duration_seconds: int, scene_count: int, replaced: int}
     */
    public function sync(Episode $episode, int $durationSeconds, bool $replace = true): array
    {
        $durationSeconds = max(1, $durationSeconds);

        $scenes = $this->resolveSceneAssetsWithImages($episode);

        if ($scenes->isEmpty()) {
            throw ValidationException::withMessages([
                'episode_id' => ['هیچ تصویر صحنه‌ای برای این اپیزود پیدا نشد. ابتدا تصاویر صحنه را آپلود کنید.'],
            ]);
        }

        $count = $scenes->count();
        if ($durationSeconds < $count) {
            throw ValidationException::withMessages([
                'duration_seconds' => ["مدت صوت ({$durationSeconds} ثانیه) از تعداد صحنه‌ها ({$count}) کمتر است."],
            ]);
        }

        $existingCount = ImageTimeline::query()->where('episode_id', $episode->id)->count();
        if ($existingCount > 0 && ! $replace) {
            throw ValidationException::withMessages([
                'replace' => ['برای این اپیزود فریم تایم‌لاین وجود دارد. برای بازسازی، replace=true ارسال کنید.'],
            ]);
        }

        $created = [];

        DB::transaction(function () use ($episode, $scenes, $durationSeconds, $count, &$created) {
            ImageTimeline::query()->where('episode_id', $episode->id)->delete();

            $storyId = (int) $episode->story_id;

            foreach ($scenes->values() as $index => $asset) {
                $start = (int) floor(($index * $durationSeconds) / $count);
                $end = (int) floor((($index + 1) * $durationSeconds) / $count);
                if ($end <= $start) {
                    $end = $start + 1;
                }
                if ($index === $count - 1) {
                    $end = $durationSeconds;
                }

                $imageUrl = $asset->image_url
                    ? ($asset->getImageUrlFromPath($asset->image_url) ?? $asset->image_url)
                    : '';

                $created[] = ImageTimeline::create([
                    'story_id' => $storyId,
                    'episode_id' => $episode->id,
                    'start_time' => $start,
                    'end_time' => $end,
                    'image_url' => $imageUrl,
                    'image_order' => $index + 1,
                    'scene_description' => $asset->asset_key,
                    'transition_type' => 'fade',
                    'is_key_frame' => $index === 0,
                ]);
            }

            if (! $episode->use_image_timeline) {
                $episode->forceFill(['use_image_timeline' => true])->save();
            }
        });

        Cache::forget("episode_timeline_{$episode->id}");
        Cache::forget("episode_timeline_{$episode->id}_with_voice_actors");

        return [
            'frames' => $created,
            'duration_seconds' => $durationSeconds,
            'scene_count' => $count,
            'replaced' => $existingCount,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, StoryProductionAsset>
     */
    private function resolveSceneAssetsWithImages(Episode $episode)
    {
        $query = StoryProductionAsset::query()
            ->where('asset_type', StoryProductionAsset::TYPE_SCENE)
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '');

        $byEpisodeId = (clone $query)->where('episode_id', $episode->id)->get();

        if ($byEpisodeId->isNotEmpty()) {
            return $this->naturalSortByAssetKey($byEpisodeId);
        }

        $episodeSlug = StoryProductionFile::query()
            ->where('episode_id', $episode->id)
            ->whereNotNull('episode_slug')
            ->value('episode_slug');

        if ($episodeSlug) {
            $bySlug = (clone $query)->where('episode_slug', $episodeSlug)->get();
            if ($bySlug->isNotEmpty()) {
                return $this->naturalSortByAssetKey($bySlug);
            }
        }

        return collect();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, StoryProductionAsset>  $assets
     * @return \Illuminate\Support\Collection<int, StoryProductionAsset>
     */
    private function naturalSortByAssetKey($assets)
    {
        return $assets->sort(function (StoryProductionAsset $a, StoryProductionAsset $b) {
            return strnatcasecmp((string) $a->asset_key, (string) $b->asset_key);
        })->values();
    }
}
