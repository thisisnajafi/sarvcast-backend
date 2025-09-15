<?php

namespace App\Console\Commands;

use App\Jobs\LearnUserPreferences;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LearnAllUserPreferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'personalization:learn-all-preferences 
                            {--batch-size=100 : Number of users to process in each batch}
                            {--queue=default : Queue name to dispatch jobs to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Learn preferences for all users by dispatching background jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $queue = $this->option('queue');

        $this->info('Starting preference learning for all users...');

        // Get total user count
        $totalUsers = User::count();
        $this->info("Total users to process: {$totalUsers}");

        if ($totalUsers === 0) {
            $this->warn('No users found to process.');
            return 0;
        }

        // Process users in batches
        $processed = 0;
        $batches = ceil($totalUsers / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $offset = $batch * $batchSize;
            
            $users = User::skip($offset)
                ->take($batchSize)
                ->pluck('id')
                ->toArray();

            $this->info("Processing batch " . ($batch + 1) . "/{$batches} ({$offset}-" . ($offset + count($users) - 1) . ")");

            foreach ($users as $userId) {
                try {
                    LearnUserPreferences::dispatch($userId)->onQueue($queue);
                    $processed++;
                } catch (\Exception $e) {
                    $this->error("Failed to dispatch job for user {$userId}: " . $e->getMessage());
                    Log::error("Failed to dispatch LearnUserPreferences job for user {$userId}: " . $e->getMessage());
                }
            }

            // Small delay between batches to avoid overwhelming the queue
            if ($batch < $batches - 1) {
                sleep(1);
            }
        }

        $this->info("Successfully dispatched {$processed} preference learning jobs.");
        $this->info("Jobs are queued on '{$queue}' queue.");

        return 0;
    }
}