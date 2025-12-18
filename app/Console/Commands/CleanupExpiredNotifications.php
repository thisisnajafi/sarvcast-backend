<?php

namespace App\Console\Commands;

use App\Services\InAppNotificationService;
use Illuminate\Console\Command;

class CleanupExpiredNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup-expired {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired in-app notifications';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(InAppNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cleanup of expired notifications...');

        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE - No notifications will be deleted');
            
            $expiredCount = \App\Models\Notification::where('expires_at', '<=', now())->count();
            
            if ($expiredCount > 0) {
                $this->info("Found {$expiredCount} expired notifications that would be deleted:");
                
                $expiredNotifications = \App\Models\Notification::where('expires_at', '<=', now())
                    ->with('user')
                    ->limit(10)
                    ->get();
                
                foreach ($expiredNotifications as $notification) {
                    $this->line("- ID: {$notification->id}, User: {$notification->user->name}, Title: {$notification->title}, Expires: {$notification->expires_at}");
                }
                
                if ($expiredCount > 10) {
                    $this->line("... and " . ($expiredCount - 10) . " more");
                }
            } else {
                $this->info('No expired notifications found');
            }
            
            return 0;
        }

        try {
            $count = $this->notificationService->cleanupExpired();
            
            if ($count > 0) {
                $this->info("Successfully cleaned up {$count} expired notifications");
            } else {
                $this->info('No expired notifications found');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to cleanup expired notifications: ' . $e->getMessage());
            return 1;
        }
    }
}
