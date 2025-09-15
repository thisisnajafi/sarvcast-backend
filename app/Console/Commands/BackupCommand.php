<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sarvcast:backup {--type=full : Backup type (database, files, config, full)} {--cleanup : Clean up old backups} {--list : List available backups} {--restore= : Restore from backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage SarvCast application backups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backupService = app(BackupService::class);

        $this->info('💾 SarvCast Backup Management');
        $this->line('');

        // List backups
        if ($this->option('list')) {
            $this->listBackups($backupService);
            return;
        }

        // Cleanup old backups
        if ($this->option('cleanup')) {
            $this->cleanupBackups($backupService);
            return;
        }

        // Restore from backup
        if ($backupPath = $this->option('restore')) {
            $this->restoreBackup($backupService, $backupPath);
            return;
        }

        // Create backup
        $type = $this->option('type');
        $this->createBackup($backupService, $type);
    }

    /**
     * Create backup
     */
    private function createBackup(BackupService $backupService, string $type): void
    {
        $this->info("🔄 Creating {$type} backup...");

        switch ($type) {
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
                $this->error('Invalid backup type. Use: database, files, config, or full');
                return;
        }

        if ($result['status'] === 'success') {
            $this->info('✅ Backup created successfully!');
            $this->displayBackupInfo($result);
        } else {
            $this->error('❌ Backup failed!');
            if (isset($result['error'])) {
                $this->error('Error: ' . $result['error']);
            }
        }
    }

    /**
     * List available backups
     */
    private function listBackups(BackupService $backupService): void
    {
        $this->info('📋 Available Backups:');
        $this->line('');

        $backups = $backupService->listBackups();

        if (empty($backups)) {
            $this->warn('No backups found.');
            return;
        }

        $headers = ['Type', 'Filename', 'Size', 'Created At'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                ucfirst($backup['type']),
                $backup['filename'],
                $this->formatBytes($backup['size']),
                $backup['created_at']->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Cleanup old backups
     */
    private function cleanupBackups(BackupService $backupService): void
    {
        $this->info('🧹 Cleaning up old backups...');

        $keepDays = $this->ask('How many days to keep backups?', 30);
        
        if (!is_numeric($keepDays) || $keepDays < 1) {
            $this->error('Invalid number of days.');
            return;
        }

        $deletedBackups = $backupService->cleanupOldBackups((int) $keepDays);

        if (empty($deletedBackups)) {
            $this->info('No old backups to clean up.');
        } else {
            $this->info('✅ Cleaned up ' . count($deletedBackups) . ' old backups.');
            
            $this->line('');
            $this->info('Deleted backups:');
            foreach ($deletedBackups as $backup) {
                $this->line("  • {$backup['type']}: {$backup['filename']}");
            }
        }
    }

    /**
     * Restore from backup
     */
    private function restoreBackup(BackupService $backupService, string $backupPath): void
    {
        $this->warn('⚠️ This will restore from backup and may overwrite current data!');
        
        if (!$this->confirm('Are you sure you want to continue?')) {
            $this->info('Restore cancelled.');
            return;
        }

        $this->info("🔄 Restoring from backup: {$backupPath}");

        // Determine backup type from path
        $type = 'database'; // default
        if (strpos($backupPath, 'files_backup_') !== false) {
            $type = 'files';
        } elseif (strpos($backupPath, 'config_backup_') !== false) {
            $type = 'config';
        }

        $result = $backupService->restoreFromBackup($backupPath, $type);

        if ($result['status'] === 'success') {
            $this->info('✅ Backup restored successfully!');
        } else {
            $this->error('❌ Backup restore failed!');
            if (isset($result['error'])) {
                $this->error('Error: ' . $result['error']);
            }
        }
    }

    /**
     * Display backup information
     */
    private function displayBackupInfo(array $result): void
    {
        if (isset($result['filename'])) {
            $this->line("  📁 Filename: {$result['filename']}");
        }
        
        if (isset($result['size'])) {
            $this->line("  📏 Size: " . $this->formatBytes($result['size']));
        }
        
        if (isset($result['path'])) {
            $this->line("  📍 Path: {$result['path']}");
        }
        
        if (isset($result['created_at'])) {
            $this->line("  🕒 Created: {$result['created_at']}");
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}