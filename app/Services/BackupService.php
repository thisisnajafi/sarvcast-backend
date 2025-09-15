<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class BackupService
{
    /**
     * Create database backup
     */
    public function createDatabaseBackup(): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupFileName = "database_backup_{$timestamp}.sql";
            $backupPath = "backups/database/{$backupFileName}";
            
            // Get database configuration
            $config = config('database.connections.mysql');
            $host = $config['host'];
            $port = $config['port'];
            $database = $config['database'];
            $username = $config['username'];
            $password = $config['password'];
            
            // Create mysqldump command
            $command = "mysqldump -h {$host} -P {$port} -u {$username} -p{$password} {$database}";
            
            // Execute backup command
            $result = Process::run($command);
            
            if ($result->successful()) {
                // Store backup file
                Storage::put($backupPath, $result->output());
                
                $backupInfo = [
                    'type' => 'database',
                    'filename' => $backupFileName,
                    'path' => $backupPath,
                    'size' => Storage::size($backupPath),
                    'created_at' => now(),
                    'status' => 'success',
                ];
                
                // Log backup creation
                Log::info('Database backup created successfully', $backupInfo);
                
                return $backupInfo;
            } else {
                throw new \Exception('Database backup failed: ' . $result->errorOutput());
            }
        } catch (\Exception $e) {
            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);
            
            return [
                'type' => 'database',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now(),
            ];
        }
    }

    /**
     * Create file storage backup
     */
    public function createFileBackup(): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupFileName = "files_backup_{$timestamp}.zip";
            $backupPath = "backups/files/{$backupFileName}";
            
            // Get storage directories to backup
            $directories = [
                'public',
                'app/public',
            ];
            
            $tempDir = storage_path('app/temp_backup');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Copy files to temp directory
            foreach ($directories as $dir) {
                $sourcePath = storage_path($dir);
                if (is_dir($sourcePath)) {
                    $destPath = $tempDir . '/' . basename($dir);
                    $this->copyDirectory($sourcePath, $destPath);
                }
            }
            
            // Create zip archive
            $zipPath = $tempDir . '/' . $backupFileName;
            $this->createZipArchive($tempDir, $zipPath, $directories);
            
            // Store backup file
            Storage::put($backupPath, file_get_contents($zipPath));
            
            // Clean up temp directory
            $this->deleteDirectory($tempDir);
            
            $backupInfo = [
                'type' => 'files',
                'filename' => $backupFileName,
                'path' => $backupPath,
                'size' => Storage::size($backupPath),
                'created_at' => now(),
                'status' => 'success',
            ];
            
            Log::info('File backup created successfully', $backupInfo);
            
            return $backupInfo;
        } catch (\Exception $e) {
            Log::error('File backup failed', [
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);
            
            return [
                'type' => 'files',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now(),
            ];
        }
    }

    /**
     * Create configuration backup
     */
    public function createConfigBackup(): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupFileName = "config_backup_{$timestamp}.zip";
            $backupPath = "backups/config/{$backupFileName}";
            
            $tempDir = storage_path('app/temp_config_backup');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Copy configuration files
            $configFiles = [
                '.env',
                'config/app.php',
                'config/database.php',
                'config/mail.php',
                'config/cache.php',
                'config/session.php',
                'config/filesystems.php',
                'config/payment.php',
                'config/sms.php',
                'config/notification.php',
            ];
            
            foreach ($configFiles as $file) {
                $sourcePath = base_path($file);
                if (file_exists($sourcePath)) {
                    $destPath = $tempDir . '/' . basename($file);
                    copy($sourcePath, $destPath);
                }
            }
            
            // Create zip archive
            $zipPath = $tempDir . '/' . $backupFileName;
            $this->createZipArchive($tempDir, $zipPath, array_map('basename', $configFiles));
            
            // Store backup file
            Storage::put($backupPath, file_get_contents($zipPath));
            
            // Clean up temp directory
            $this->deleteDirectory($tempDir);
            
            $backupInfo = [
                'type' => 'config',
                'filename' => $backupFileName,
                'path' => $backupPath,
                'size' => Storage::size($backupPath),
                'created_at' => now(),
                'status' => 'success',
            ];
            
            Log::info('Configuration backup created successfully', $backupInfo);
            
            return $backupInfo;
        } catch (\Exception $e) {
            Log::error('Configuration backup failed', [
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);
            
            return [
                'type' => 'config',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now(),
            ];
        }
    }

    /**
     * Create full system backup
     */
    public function createFullBackup(): array
    {
        $results = [];
        
        // Create database backup
        $results['database'] = $this->createDatabaseBackup();
        
        // Create file backup
        $results['files'] = $this->createFileBackup();
        
        // Create config backup
        $results['config'] = $this->createConfigBackup();
        
        // Check if all backups were successful
        $allSuccessful = collect($results)->every(function ($result) {
            return $result['status'] === 'success';
        });
        
        $overallStatus = $allSuccessful ? 'success' : 'partial';
        
        Log::info('Full system backup completed', [
            'status' => $overallStatus,
            'results' => $results,
        ]);
        
        return [
            'status' => $overallStatus,
            'timestamp' => now(),
            'results' => $results,
        ];
    }

    /**
     * List available backups
     */
    public function listBackups(string $type = null): array
    {
        $backups = [];
        
        $types = $type ? [$type] : ['database', 'files', 'config'];
        
        foreach ($types as $backupType) {
            $files = Storage::files("backups/{$backupType}");
            
            foreach ($files as $file) {
                $backups[] = [
                    'type' => $backupType,
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => Storage::size($file),
                    'created_at' => Carbon::createFromTimestamp(Storage::lastModified($file)),
                ];
            }
        }
        
        // Sort by creation date (newest first)
        usort($backups, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
        
        return $backups;
    }

    /**
     * Delete old backups
     */
    public function cleanupOldBackups(int $keepDays = 30): array
    {
        $deletedBackups = [];
        $cutoffDate = Carbon::now()->subDays($keepDays);
        
        $backupTypes = ['database', 'files', 'config'];
        
        foreach ($backupTypes as $type) {
            $files = Storage::files("backups/{$type}");
            
            foreach ($files as $file) {
                $lastModified = Carbon::createFromTimestamp(Storage::lastModified($file));
                
                if ($lastModified->lt($cutoffDate)) {
                    Storage::delete($file);
                    $deletedBackups[] = [
                        'type' => $type,
                        'filename' => basename($file),
                        'deleted_at' => now(),
                    ];
                }
            }
        }
        
        Log::info('Old backups cleaned up', [
            'deleted_count' => count($deletedBackups),
            'keep_days' => $keepDays,
        ]);
        
        return $deletedBackups;
    }

    /**
     * Restore from backup
     */
    public function restoreFromBackup(string $backupPath, string $type): array
    {
        try {
            if (!Storage::exists($backupPath)) {
                throw new \Exception('Backup file not found');
            }
            
            switch ($type) {
                case 'database':
                    return $this->restoreDatabaseBackup($backupPath);
                case 'files':
                    return $this->restoreFileBackup($backupPath);
                case 'config':
                    return $this->restoreConfigBackup($backupPath);
                default:
                    throw new \Exception('Invalid backup type');
            }
        } catch (\Exception $e) {
            Log::error('Backup restore failed', [
                'backup_path' => $backupPath,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'restored_at' => now(),
            ];
        }
    }

    /**
     * Restore database backup
     */
    private function restoreDatabaseBackup(string $backupPath): array
    {
        $config = config('database.connections.mysql');
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        
        $backupContent = Storage::get($backupPath);
        
        // Create temporary file
        $tempFile = storage_path('app/temp_restore.sql');
        file_put_contents($tempFile, $backupContent);
        
        // Execute restore command
        $command = "mysql -h {$host} -P {$port} -u {$username} -p{$password} {$database} < {$tempFile}";
        $result = Process::run($command);
        
        // Clean up temp file
        unlink($tempFile);
        
        if ($result->successful()) {
            return [
                'status' => 'success',
                'restored_at' => now(),
            ];
        } else {
            throw new \Exception('Database restore failed: ' . $result->errorOutput());
        }
    }

    /**
     * Restore file backup
     */
    private function restoreFileBackup(string $backupPath): array
    {
        $backupContent = Storage::get($backupPath);
        
        // Create temporary file
        $tempFile = storage_path('app/temp_restore.zip');
        file_put_contents($tempFile, $backupContent);
        
        // Extract zip file
        $zip = new \ZipArchive();
        if ($zip->open($tempFile) === TRUE) {
            $zip->extractTo(storage_path('app/'));
            $zip->close();
        } else {
            throw new \Exception('Failed to extract backup file');
        }
        
        // Clean up temp file
        unlink($tempFile);
        
        return [
            'status' => 'success',
            'restored_at' => now(),
        ];
    }

    /**
     * Restore config backup
     */
    private function restoreConfigBackup(string $backupPath): array
    {
        $backupContent = Storage::get($backupPath);
        
        // Create temporary file
        $tempFile = storage_path('app/temp_config_restore.zip');
        file_put_contents($tempFile, $backupContent);
        
        // Extract zip file
        $zip = new \ZipArchive();
        if ($zip->open($tempFile) === TRUE) {
            $zip->extractTo(base_path());
            $zip->close();
        } else {
            throw new \Exception('Failed to extract config backup file');
        }
        
        // Clean up temp file
        unlink($tempFile);
        
        return [
            'status' => 'success',
            'restored_at' => now(),
        ];
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

    /**
     * Create zip archive
     */
    private function createZipArchive(string $sourceDir, string $zipPath, array $files): void
    {
        $zip = new \ZipArchive();
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create zip file');
        }
        
        foreach ($files as $file) {
            $filePath = $sourceDir . '/' . $file;
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }
        
        $zip->close();
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
