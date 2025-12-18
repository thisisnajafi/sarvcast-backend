<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;

class BackupService
{
    /**
     * Create a complete backup of the system
     */
    public function createFullBackup(): array
    {
        $backupId = 'backup_' . now()->format('Y_m_d_H_i_s');
        $backupPath = "backups/{$backupId}";
        
        try {
            // Create backup directory
            Storage::makeDirectory($backupPath);
            
            // Backup database
            $dbBackup = $this->backupDatabase($backupPath);
            
            // Backup new feature data
            $timelineBackup = $this->backupImageTimelines($backupPath);
            $commentsBackup = $this->backupStoryComments($backupPath);
            $performanceBackup = $this->backupPerformanceMetrics($backupPath);
            
            // Backup configuration files
            $configBackup = $this->backupConfiguration($backupPath);
            
            // Create backup manifest
            $manifest = $this->createBackupManifest($backupPath, [
                'database' => $dbBackup,
                'image_timelines' => $timelineBackup,
                'story_comments' => $commentsBackup,
                'performance_metrics' => $performanceBackup,
                'configuration' => $configBackup
            ]);
            
            // Create compressed archive
            $archivePath = $this->createCompressedArchive($backupPath, $backupId);
            
            Log::info("Full backup created successfully: {$backupId}");
            
            return [
                'success' => true,
                'backup_id' => $backupId,
                'backup_path' => $archivePath,
                'size' => Storage::size($archivePath),
                'created_at' => now(),
                'manifest' => $manifest
            ];
            
        } catch (\Exception $e) {
            Log::error("Backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_id' => $backupId
            ];
        }
    }
    
    /**
     * Create incremental backup for new features
     */
    public function createIncrementalBackup(Carbon $since): array
    {
        $backupId = 'incremental_' . now()->format('Y_m_d_H_i_s');
        $backupPath = "backups/{$backupId}";
        
        try {
            Storage::makeDirectory($backupPath);
            
            $changes = [];
            
            // Backup new image timelines
            $timelineChanges = $this->backupImageTimelinesSince($backupPath, $since);
            if ($timelineChanges['count'] > 0) {
                $changes['image_timelines'] = $timelineChanges;
            }
            
            // Backup new story comments
            $commentChanges = $this->backupStoryCommentsSince($backupPath, $since);
            if ($commentChanges['count'] > 0) {
                $changes['story_comments'] = $commentChanges;
            }
            
            // Backup new performance metrics
            $performanceChanges = $this->backupPerformanceMetricsSince($backupPath, $since);
            if ($performanceChanges['count'] > 0) {
                $changes['performance_metrics'] = $performanceChanges;
            }
            
            if (empty($changes)) {
                return [
                    'success' => true,
                    'backup_id' => $backupId,
                    'message' => 'No changes to backup',
                    'changes_count' => 0
                ];
            }
            
            // Create manifest
            $manifest = $this->createBackupManifest($backupPath, $changes);
            
            // Create compressed archive
            $archivePath = $this->createCompressedArchive($backupPath, $backupId);
            
            Log::info("Incremental backup created: {$backupId}");
            
            return [
                'success' => true,
                'backup_id' => $backupId,
                'backup_path' => $archivePath,
                'size' => Storage::size($archivePath),
                'created_at' => now(),
                'changes' => $changes,
                'manifest' => $manifest
            ];
            
        } catch (\Exception $e) {
            Log::error("Incremental backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_id' => $backupId
            ];
        }
    }
    
    /**
     * Backup image timelines
     */
    private function backupImageTimelines(string $backupPath): array
    {
        $timelines = DB::table('image_timelines')
            ->join('episodes', 'image_timelines.episode_id', '=', 'episodes.id')
            ->select('image_timelines.*', 'episodes.title as episode_title')
            ->get();
        
        $filePath = "{$backupPath}/image_timelines.json";
        Storage::put($filePath, json_encode($timelines, JSON_PRETTY_PRINT));
        
        return [
            'file' => $filePath,
            'count' => $timelines->count(),
            'size' => Storage::size($filePath)
        ];
    }
    
    /**
     * Backup image timelines since date
     */
    private function backupImageTimelinesSince(string $backupPath, Carbon $since): array
    {
        $timelines = DB::table('image_timelines')
            ->join('episodes', 'image_timelines.episode_id', '=', 'episodes.id')
            ->select('image_timelines.*', 'episodes.title as episode_title')
            ->where('image_timelines.created_at', '>=', $since)
            ->orWhere('image_timelines.updated_at', '>=', $since)
            ->get();
        
        if ($timelines->isEmpty()) {
            return ['count' => 0];
        }
        
        $filePath = "{$backupPath}/image_timelines_incremental.json";
        Storage::put($filePath, json_encode($timelines, JSON_PRETTY_PRINT));
        
        return [
            'file' => $filePath,
            'count' => $timelines->count(),
            'size' => Storage::size($filePath)
        ];
    }
    
    /**
     * Backup story comments
     */
    private function backupStoryComments(string $backupPath): array
    {
        $comments = DB::table('story_comments')
            ->join('stories', 'story_comments.story_id', '=', 'stories.id')
            ->join('users', 'story_comments.user_id', '=', 'users.id')
            ->select(
                'story_comments.*',
                'stories.title as story_title',
                'users.first_name',
                'users.last_name',
                'users.phone_number'
            )
            ->get();
        
        $filePath = "{$backupPath}/story_comments.json";
        Storage::put($filePath, json_encode($comments, JSON_PRETTY_PRINT));
        
        return [
            'file' => $filePath,
            'count' => $comments->count(),
            'size' => Storage::size($filePath)
        ];
    }
    
    /**
     * Backup story comments since date
     */
    private function backupStoryCommentsSince(string $backupPath, Carbon $since): array
    {
        $comments = DB::table('story_comments')
            ->join('stories', 'story_comments.story_id', '=', 'stories.id')
            ->join('users', 'story_comments.user_id', '=', 'users.id')
            ->select(
                'story_comments.*',
                'stories.title as story_title',
                'users.first_name',
                'users.last_name',
                'users.phone_number'
            )
            ->where('story_comments.created_at', '>=', $since)
            ->orWhere('story_comments.updated_at', '>=', $since)
            ->get();
        
        if ($comments->isEmpty()) {
            return ['count' => 0];
        }
        
        $filePath = "{$backupPath}/story_comments_incremental.json";
        Storage::put($filePath, json_encode($comments, JSON_PRETTY_PRINT));
        
        return [
            'file' => $filePath,
            'count' => $comments->count(),
            'size' => Storage::size($filePath)
        ];
    }
    
    /**
     * Backup performance metrics
     */
    private function backupPerformanceMetrics(string $backupPath): array
    {
        $metrics = DB::table('performance_metrics')
            ->where('created_at', '>=', now()->subDays(30)) // Last 30 days
            ->get();
        
        $filePath = "{$backupPath}/performance_metrics.json";
        Storage::put($filePath, json_encode($metrics, JSON_PRETTY_PRINT));
        
        return [
            'file' => $filePath,
            'count' => $metrics->count(),
            'size' => Storage::size($filePath)
        ];
    }
    
    /**
     * Backup performance metrics since date
     */
    private function backupPerformanceMetricsSince(string $backupPath, Carbon $since): array
    {
        $metrics = DB::table('performance_metrics')
            ->where('created_at', '>=', $since)
            ->get();
        
        if ($metrics->isEmpty()) {
            return ['count' => 0];
        }
        
        $filePath = "{$backupPath}/performance_metrics_incremental.json";
        Storage::put($filePath, json_encode($metrics, JSON_PRETTY_PRINT));
        
        return [
            'file' => $filePath,
            'count' => $metrics->count(),
            'size' => Storage::size($filePath)
        ];
    }
    
    /**
     * Backup database
     */
    private function backupDatabase(string $backupPath): array
    {
        $dbName = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        
        $fileName = "{$backupPath}/database.sql";
        $command = "mysqldump -h {$host} -u {$username} -p{$password} {$dbName} > " . storage_path("app/{$fileName}");
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Database backup failed with return code: {$returnCode}");
        }
        
        return [
            'file' => $fileName,
            'size' => Storage::size($fileName)
        ];
    }
    
    /**
     * Backup configuration files
     */
    private function backupConfiguration(string $backupPath): array
    {
        $configFiles = [
            '.env',
            'config/app.php',
            'config/database.php',
            'config/cache.php',
            'config/queue.php'
        ];
        
        $backedUpFiles = [];
        
        foreach ($configFiles as $file) {
            if (file_exists(base_path($file))) {
                $content = file_get_contents(base_path($file));
                $fileName = "{$backupPath}/config/" . basename($file);
                Storage::put($fileName, $content);
                $backedUpFiles[] = $fileName;
            }
        }
        
        return [
            'files' => $backedUpFiles,
            'count' => count($backedUpFiles)
        ];
    }
    
    /**
     * Create backup manifest
     */
    private function createBackupManifest(string $backupPath, array $components): array
    {
        $manifest = [
            'backup_id' => basename($backupPath),
            'created_at' => now()->toISOString(),
            'version' => '1.0',
            'components' => $components,
            'total_size' => 0
        ];
        
        // Calculate total size
        foreach ($components as $component) {
            if (isset($component['size'])) {
                $manifest['total_size'] += $component['size'];
            } elseif (isset($component['files'])) {
                foreach ($component['files'] as $file) {
                    $manifest['total_size'] += Storage::size($file);
                }
            }
        }
        
        $manifestPath = "{$backupPath}/manifest.json";
        Storage::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        
        return $manifest;
    }
    
    /**
     * Create compressed archive
     */
    private function createCompressedArchive(string $backupPath, string $backupId): string
    {
        $zipPath = "backups/{$backupId}.zip";
        $zip = new ZipArchive();
        
        if ($zip->open(storage_path("app/{$zipPath}"), ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create zip archive: {$zipPath}");
        }
        
        $this->addDirectoryToZip($zip, storage_path("app/{$backupPath}"), $backupId);
        $zip->close();
        
        // Remove uncompressed directory
        Storage::deleteDirectory($backupPath);
        
        return $zipPath;
    }
    
    /**
     * Add directory to zip archive
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $baseName): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $baseName . '/' . substr($filePath, strlen($dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Restore from backup
     */
    public function restoreFromBackup(string $backupId): array
    {
        try {
            $backupPath = "backups/{$backupId}.zip";
            
            if (!Storage::exists($backupPath)) {
                throw new \Exception("Backup file not found: {$backupPath}");
            }
            
            // Extract backup
            $extractPath = "backups/restore_{$backupId}";
            $this->extractBackup($backupPath, $extractPath);
            
            // Read manifest
            $manifestPath = "{$extractPath}/manifest.json";
            $manifest = json_decode(Storage::get($manifestPath), true);
            
            // Restore components
            $restored = [];
            
            if (isset($manifest['components']['image_timelines'])) {
                $restored['image_timelines'] = $this->restoreImageTimelines($extractPath);
            }
            
            if (isset($manifest['components']['story_comments'])) {
                $restored['story_comments'] = $this->restoreStoryComments($extractPath);
            }
            
            if (isset($manifest['components']['performance_metrics'])) {
                $restored['performance_metrics'] = $this->restorePerformanceMetrics($extractPath);
            }
            
            // Clean up
            Storage::deleteDirectory($extractPath);
            
            Log::info("Backup restored successfully: {$backupId}");
            
            return [
                'success' => true,
                'backup_id' => $backupId,
                'restored_at' => now(),
                'restored_components' => $restored
            ];
            
        } catch (\Exception $e) {
            Log::error("Restore failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_id' => $backupId
            ];
        }
    }
    
    /**
     * Extract backup archive
     */
    private function extractBackup(string $backupPath, string $extractPath): void
    {
        $zip = new ZipArchive();
        
        if ($zip->open(storage_path("app/{$backupPath}")) !== TRUE) {
            throw new \Exception("Cannot open backup archive: {$backupPath}");
        }
        
        $zip->extractTo(storage_path("app/{$extractPath}"));
        $zip->close();
    }
    
    /**
     * Restore image timelines
     */
    private function restoreImageTimelines(string $extractPath): array
    {
        $filePath = "{$extractPath}/image_timelines.json";
        $data = json_decode(Storage::get($filePath), true);
        
        $restored = 0;
        foreach ($data as $timeline) {
            DB::table('image_timelines')->updateOrInsert(
                ['id' => $timeline['id']],
                $timeline
            );
            $restored++;
        }
        
        return ['restored_count' => $restored];
    }
    
    /**
     * Restore story comments
     */
    private function restoreStoryComments(string $extractPath): array
    {
        $filePath = "{$extractPath}/story_comments.json";
        $data = json_decode(Storage::get($filePath), true);
        
        $restored = 0;
        foreach ($data as $comment) {
            DB::table('story_comments')->updateOrInsert(
                ['id' => $comment['id']],
                $comment
            );
            $restored++;
        }
        
        return ['restored_count' => $restored];
    }
    
    /**
     * Restore performance metrics
     */
    private function restorePerformanceMetrics(string $extractPath): array
    {
        $filePath = "{$extractPath}/performance_metrics.json";
        $data = json_decode(Storage::get($filePath), true);
        
        $restored = 0;
        foreach ($data as $metric) {
            DB::table('performance_metrics')->updateOrInsert(
                ['id' => $metric['id']],
                $metric
            );
            $restored++;
        }
        
        return ['restored_count' => $restored];
    }
    
    /**
     * List available backups
     */
    public function listBackups(): array
    {
        $backups = Storage::files('backups');
        $backupList = [];
        
        foreach ($backups as $backup) {
            if (str_ends_with($backup, '.zip')) {
                $backupList[] = [
                    'file' => $backup,
                    'size' => Storage::size($backup),
                    'created_at' => Storage::lastModified($backup),
                    'backup_id' => basename($backup, '.zip')
                ];
            }
        }
        
        // Sort by creation date (newest first)
        usort($backupList, function($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });
        
        return $backupList;
    }
    
    /**
     * Delete old backups
     */
    public function cleanupOldBackups(int $daysToKeep = 30): array
    {
        $cutoffDate = now()->subDays($daysToKeep)->timestamp;
        $deleted = [];
        
        $backups = $this->listBackups();
        
        foreach ($backups as $backup) {
            if ($backup['created_at'] < $cutoffDate) {
                Storage::delete($backup['file']);
                $deleted[] = $backup['backup_id'];
            }
        }
        
        Log::info("Cleaned up " . count($deleted) . " old backups");
        
        return [
            'deleted_count' => count($deleted),
            'deleted_backups' => $deleted
        ];
    }
}