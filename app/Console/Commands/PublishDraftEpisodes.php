<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Episode;
use Illuminate\Support\Facades\DB;

class PublishDraftEpisodes extends Command
{
    protected $signature = 'episodes:publish-drafts
                            {--dry-run : Show what would be published without making changes}
                            {--story-id= : Only publish episodes for a specific story}';

    protected $description = 'Publish all draft episodes (or episodes for a specific story)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $storyId = $this->option('story-id');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        $query = Episode::where('status', 'draft');

        if ($storyId) {
            $query->where('story_id', $storyId);
            $this->info("📖 Publishing draft episodes for story ID: {$storyId}");
        } else {
            $this->info("📚 Publishing all draft episodes");
        }

        $draftEpisodes = $query->get();

        if ($draftEpisodes->isEmpty()) {
            $this->info('✅ No draft episodes found.');
            return 0;
        }

        $this->info("📊 Found {$draftEpisodes->count()} draft episode(s):");

        $tableData = [];
        foreach ($draftEpisodes as $episode) {
            $tableData[] = [
                'ID' => $episode->id,
                'Story ID' => $episode->story_id,
                'Episode #' => $episode->episode_number,
                'Title' => $episode->title,
            ];
        }

        $this->table(['ID', 'Story ID', 'Episode #', 'Title'], $tableData);

        if ($dryRun) {
            $this->warn("\n⚠️  Would publish {$draftEpisodes->count()} episodes (dry run)");
            return 0;
        }

        if (!$this->confirm("\n❓ Do you want to publish these {$draftEpisodes->count()} draft episodes?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        DB::beginTransaction();

        try {
            $published = 0;
            $now = now();

            foreach ($draftEpisodes as $episode) {
                $episode->update([
                    'status' => 'published',
                    'published_at' => $now,
                ]);

                $this->info("   ✅ Published: Episode {$episode->episode_number} - {$episode->title}");
                $published++;
            }

            DB::commit();

            $this->info("\n✅ Episodes published successfully!");
            $this->info("   - Published {$published} episodes");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error publishing episodes: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

