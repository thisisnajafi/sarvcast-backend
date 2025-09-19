<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Create a full backup
     */
    public function createFullBackup(): JsonResponse
    {
        try {
            $result = $this->backupService->createFullBackup();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'پشتیبان‌گیری کامل با موفقیت انجام شد',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در پشتیبان‌گیری: ' . $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در پشتیبان‌گیری: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create an incremental backup
     */
    public function createIncrementalBackup(Request $request): JsonResponse
    {
        try {
            $since = $request->input('since');
            $sinceDate = $since ? \Carbon\Carbon::parse($since) : now()->subDay();

            $result = $this->backupService->createIncrementalBackup($sinceDate);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'پشتیبان‌گیری افزایشی با موفقیت انجام شد',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در پشتیبان‌گیری: ' . $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در پشتیبان‌گیری: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List available backups
     */
    public function listBackups(): JsonResponse
    {
        try {
            $backups = $this->backupService->listBackups();

            return response()->json([
                'success' => true,
                'message' => 'فهرست پشتیبان‌ها دریافت شد',
                'data' => [
                    'backups' => $backups,
                    'total_count' => count($backups),
                    'total_size' => array_sum(array_column($backups, 'size'))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فهرست پشتیبان‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        try {
            $backupId = $request->input('backup_id');
            
            if (!$backupId) {
                return response()->json([
                    'success' => false,
                    'message' => 'شناسه پشتیبان الزامی است'
                ], 400);
            }

            $result = $this->backupService->restoreFromBackup($backupId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'بازیابی پشتیبان با موفقیت انجام شد',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در بازیابی: ' . $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بازیابی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download backup file
     */
    public function downloadBackup(string $backupId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $backupPath = "backups/{$backupId}.zip";
            
            if (!Storage::exists($backupPath)) {
                abort(404, 'فایل پشتیبان یافت نشد');
            }

            return Storage::download($backupPath, "backup_{$backupId}.zip");

        } catch (\Exception $e) {
            abort(500, 'خطا در دانلود پشتیبان: ' . $e->getMessage());
        }
    }

    /**
     * Delete backup
     */
    public function deleteBackup(string $backupId): JsonResponse
    {
        try {
            $backupPath = "backups/{$backupId}.zip";
            
            if (!Storage::exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل پشتیبان یافت نشد'
                ], 404);
            }

            Storage::delete($backupPath);

            return response()->json([
                'success' => true,
                'message' => 'پشتیبان با موفقیت حذف شد',
                'data' => [
                    'backup_id' => $backupId,
                    'deleted_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف پشتیبان: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup old backups
     */
    public function cleanupOldBackups(Request $request): JsonResponse
    {
        try {
            $daysToKeep = $request->input('days_to_keep', 30);
            
            $result = $this->backupService->cleanupOldBackups($daysToKeep);

            return response()->json([
                'success' => true,
                'message' => 'پاکسازی پشتیبان‌های قدیمی انجام شد',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در پاکسازی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats(): JsonResponse
    {
        try {
            $backups = $this->backupService->listBackups();
            
            $stats = [
                'total_backups' => count($backups),
                'total_size' => array_sum(array_column($backups, 'size')),
                'oldest_backup' => count($backups) > 0 ? min(array_column($backups, 'created_at')) : null,
                'newest_backup' => count($backups) > 0 ? max(array_column($backups, 'created_at')) : null,
                'average_size' => count($backups) > 0 ? array_sum(array_column($backups, 'size')) / count($backups) : 0
            ];

            return response()->json([
                'success' => true,
                'message' => 'آمار پشتیبان‌ها دریافت شد',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule automatic backups
     */
    public function scheduleBackups(Request $request): JsonResponse
    {
        try {
            $schedule = $request->validate([
                'full_backup_frequency' => 'required|in:daily,weekly,monthly',
                'incremental_backup_frequency' => 'required|in:hourly,daily',
                'retention_days' => 'required|integer|min:1|max:365',
                'enabled' => 'required|boolean'
            ]);

            // Store schedule in cache or database
            \Cache::put('backup_schedule', $schedule, 86400 * 30); // 30 days

            return response()->json([
                'success' => true,
                'message' => 'زمان‌بندی پشتیبان‌گیری تنظیم شد',
                'data' => $schedule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در تنظیم زمان‌بندی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup schedule
     */
    public function getBackupSchedule(): JsonResponse
    {
        try {
            $schedule = \Cache::get('backup_schedule', [
                'full_backup_frequency' => 'weekly',
                'incremental_backup_frequency' => 'daily',
                'retention_days' => 30,
                'enabled' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'زمان‌بندی پشتیبان‌گیری دریافت شد',
                'data' => $schedule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت زمان‌بندی: ' . $e->getMessage()
            ], 500);
        }
    }
}