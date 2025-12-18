<?php

namespace App\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduledBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backupType;
    protected $cleanupOld;

    /**
     * Create a new job instance.
     */
    public function __construct(string $backupType = 'full', bool $cleanupOld = true)
    {
        $this->backupType = $backupType;
        $this->cleanupOld = $cleanupOld;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $backupService = app(BackupService::class);

        Log::info('Starting scheduled backup', [
            'type' => $this->backupType,
            'cleanup_old' => $this->cleanupOld,
        ]);

        try {
            // Create backup
            switch ($this->backupType) {
                case 'database':
                    $result = $backupService->createDatabaseBackup();
                    break;
                case 'files':
                    $result = $backupService->createFileBackup();
                    break;
                case 'config':
                    $result = $backupService->createConfigBackup();
                    break;
                case 'full':
                    $result = $backupService->createFullBackup();
                    break;
                default:
                    throw new \Exception('Invalid backup type: ' . $this->backupType);
            }

            if ($result['status'] === 'success') {
                Log::info('Scheduled backup completed successfully', $result);
            } else {
                Log::error('Scheduled backup failed', $result);
            }

            // Cleanup old backups if requested
            if ($this->cleanupOld) {
                $deletedBackups = $backupService->cleanupOldBackups(30); // Keep 30 days
                
                if (!empty($deletedBackups)) {
                    Log::info('Old backups cleaned up', [
                        'deleted_count' => count($deletedBackups),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Scheduled backup job failed', [
                'error' => $e->getMessage(),
                'type' => $this->backupType,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Scheduled backup job failed permanently', [
            'error' => $exception->getMessage(),
            'type' => $this->backupType,
        ]);
    }
}