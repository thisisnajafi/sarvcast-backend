<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BackfillPushNotificationPreferences extends Command
{
    protected $signature = 'users:backfill-push-notification-preferences
                            {--chunk=200 : Number of users to process per chunk}
                            {--dry-run : Show how many users would be updated without writing}';

    protected $description = 'Backfill missing push_notifications_enabled=true into users.preferences';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $isDryRun = (bool) $this->option('dry-run');
        $scanned = 0;
        $updated = 0;

        if ($isDryRun) {
            $this->info('DRY RUN — no preferences will be written.');
        }

        User::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($users) use ($isDryRun, &$scanned, &$updated) {
                foreach ($users as $user) {
                    $scanned++;
                    $preferences = is_array($user->preferences) ? $user->preferences : [];

                    if (array_key_exists('push_notifications_enabled', $preferences)) {
                        continue;
                    }

                    $preferences['push_notifications_enabled'] = true;
                    $updated++;

                    if (! $isDryRun) {
                        $user->preferences = $preferences;
                        $user->saveQuietly();
                    }
                }
            });

        $verb = $isDryRun ? 'Would update' : 'Updated';
        $this->info("Scanned {$scanned} users. {$verb} {$updated} users.");

        return self::SUCCESS;
    }
}
