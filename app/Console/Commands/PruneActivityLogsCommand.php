<?php

namespace App\Console\Commands;

use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class PruneActivityLogsCommand extends Command
{
    protected $signature = 'activity-logs:prune';

    protected $description = 'Delete activity log rows older than per-channel retention policy';

    public function handle(ActivityLogService $activityLog): int
    {
        $deleted = $activityLog->pruneExpiredLogs();
        $total = array_sum($deleted);

        foreach ($deleted as $channel => $count) {
            $this->line("{$channel}: {$count} row(s) pruned");
        }

        $this->info("Pruned {$total} activity log row(s) in total.");

        return self::SUCCESS;
    }
}
