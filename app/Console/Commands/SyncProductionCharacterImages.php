<?php

namespace App\Console\Commands;

use App\Models\StoryProductionAsset;
use App\Services\StoryProductionImportService;
use Illuminate\Console\Command;

class SyncProductionCharacterImages extends Command
{
    protected $signature = 'stories:sync-production-character-images {slug? : Story editor slug (e.g. 31-romyna-dr-sfr)}';

    protected $description = 'Copy character images from story_production_assets onto characters.image_url for the dashboard and Flutter app';

    public function handle(StoryProductionImportService $importService): int
    {
        $slug = $this->argument('slug');

        if (is_string($slug) && $slug !== '') {
            $this->info("Syncing character images for story slug: {$slug}");
            $result = $importService->syncCharacterImagesFromProductionAssets($slug);
            $this->line(sprintf(
                'synced=%d linked=%d skipped=%d',
                $result['synced'],
                $result['linked'],
                $result['skipped'],
            ));

            return self::SUCCESS;
        }

        $slugs = StoryProductionAsset::query()
            ->where('asset_type', StoryProductionAsset::TYPE_CHARACTER)
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->distinct()
            ->pluck('story_slug');

        if ($slugs->isEmpty()) {
            $this->warn('No character production assets with images found.');

            return self::SUCCESS;
        }

        $totalSynced = 0;
        foreach ($slugs as $storySlug) {
            $result = $importService->syncCharacterImagesFromProductionAssets((string) $storySlug);
            $totalSynced += $result['synced'];
            $this->line(sprintf(
                '%s → synced=%d linked=%d skipped=%d',
                $storySlug,
                $result['synced'],
                $result['linked'],
                $result['skipped'],
            ));
        }

        $this->info("Done. Stories: {$slugs->count()}, characters updated: {$totalSynced}");

        return self::SUCCESS;
    }
}
