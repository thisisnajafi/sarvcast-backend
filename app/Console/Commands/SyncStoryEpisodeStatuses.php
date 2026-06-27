<?php

namespace App\Console\Commands;

use App\Services\StoryEpisodeStatusService;
use Illuminate\Console\Command;

class SyncStoryEpisodeStatuses extends Command
{
    protected $signature = 'stories:sync-episode-status';

    protected $description = 'Align episode statuses with draft/archived parent stories';

    public function handle(StoryEpisodeStatusService $statusService): int
    {
        $this->info('Syncing episode statuses with parent stories...');

        $result = $statusService->syncEpisodesToStoryStatuses();

        $this->info(sprintf(
            'Done. Stories processed: %d, episodes updated: %d',
            $result['stories_processed'],
            $result['episodes_updated']
        ));

        return self::SUCCESS;
    }
}
