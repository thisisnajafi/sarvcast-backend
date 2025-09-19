<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BackupController extends Controller
{
    /**
     * Display a listing of backups.
     */
    public function index(Request $request)
    {
        $query = Backup::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $backups = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => Backup::count(),
            'successful' => Backup::where('status', 'completed')->count(),
            'failed' => Backup::where('status', 'failed')->count(),
            'in_progress' => Backup::where('status', 'in_progress')->count(),
            'database_backups' => Backup::where('type', 'database')->count(),
            'files_backups' => Backup::where('type', 'files')->count(),
            'full_backups' => Backup::where('type', 'full')->count(),
            'total_size' => Backup::where('status', 'completed')->sum('size'),
        ];

        return view('admin.backup.index', compact('backups', 'stats'));
    }

    /**
     * Show the form for creating a new backup.
     */
    public function create()
    {
        return view('admin.backup.create');
    }

    /**
     * Store a newly created backup.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:database,files,full',
            'include_files' => 'nullable|array',
            'include_files.*' => 'string',
            'exclude_files' => 'nullable|array',
            'exclude_files.*' => 'string',
            'compression' => 'required|boolean',
            'encryption' => 'required|boolean',
            'schedule' => 'nullable|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $backup = Backup::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'include_files' => $request->include_files ? json_encode($request->include_files) : null,
                'exclude_files' => $request->exclude_files ? json_encode($request->exclude_files) : null,
                'compression' => $request->has('compression'),
                'encryption' => $request->has('encryption'),
                'schedule' => $request->schedule,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Start backup process
            $this->startBackupProcess($backup);

            DB::commit();

            return redirect()->route('admin.backup.index')
                ->with('success', 'پشتیبان‌گیری با موفقیت شروع شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در شروع پشتیبان‌گیری: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified backup.
     */
    public function show(Backup $backup)
    {
        // Get backup logs
        $logs = $this->getBackupLogs($backup);
        
        // Get related backups
        $relatedBackups = Backup::where('type', $backup->type)
            ->where('id', '!=', $backup->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.backup.show', compact('backup', 'logs', 'relatedBackups'));
    }

    /**
     * Download backup file.
     */
    public function download(Backup $backup)
    {
        if ($backup->status !== 'completed' || !$backup->file_path) {
            return redirect()->back()
                ->withErrors(['error' => 'فایل پشتیبان در دسترس نیست.']);
        }

        if (!Storage::disk('local')->exists($backup->file_path)) {
            return redirect()->back()
                ->withErrors(['error' => 'فایل پشتیبان یافت نشد.']);
        }

        return Storage::disk('local')->download($backup->file_path, $backup->name . '.zip');
    }

    /**
     * Restore from backup.
     */
    public function restore(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return redirect()->back()
                ->withErrors(['error' => 'فقط پشتیبان‌های تکمیل شده قابل بازیابی هستند.']);
        }

        try {
            DB::beginTransaction();

            $backup->update([
                'status' => 'restoring',
                'restored_at' => null,
            ]);

            // Start restore process
            $this->startRestoreProcess($backup);

            DB::commit();

            return redirect()->back()
                ->with('success', 'فرآیند بازیابی شروع شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در شروع بازیابی: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified backup.
     */
    public function destroy(Backup $backup)
    {
        try {
            // Delete backup file if exists
            if ($backup->file_path && Storage::disk('local')->exists($backup->file_path)) {
                Storage::disk('local')->delete($backup->file_path);
            }

            $backup->delete();
            return redirect()->route('admin.backup.index')
                ->with('success', 'پشتیبان با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف پشتیبان: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel backup process.
     */
    public function cancel(Backup $backup)
    {
        try {
            $backup->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            return redirect()->back()
                ->with('success', 'پشتیبان‌گیری لغو شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در لغو پشتیبان‌گیری: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on backups.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete,download',
            'backup_ids' => 'required|array|min:1',
            'backup_ids.*' => 'exists:backups,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک پشتیبان را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $backups = Backup::whereIn('id', $request->backup_ids);

            switch ($request->action) {
                case 'delete':
                    foreach ($backups->get() as $backup) {
                        if ($backup->file_path && Storage::disk('local')->exists($backup->file_path)) {
                            Storage::disk('local')->delete($backup->file_path);
                        }
                    }
                    $backups->delete();
                    break;

                case 'download':
                    // For multiple downloads, we'll create a zip file
                    $this->createMultipleDownloadZip($backups->get());
                    break;
            }

            DB::commit();

            $actionLabels = [
                'delete' => 'حذف',
                'download' => 'دانلود',
            ];

            return redirect()->back()
                ->with('success', 'عملیات ' . $actionLabels[$request->action] . ' با موفقیت انجام شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در انجام عملیات: ' . $e->getMessage()]);
        }
    }

    /**
     * Create automatic backup.
     */
    public function createAutomatic()
    {
        try {
            DB::beginTransaction();

            $backup = Backup::create([
                'name' => 'پشتیبان خودکار - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'پشتیبان‌گیری خودکار سیستم',
                'type' => 'full',
                'compression' => true,
                'encryption' => false,
                'status' => 'pending',
                'created_by' => auth()->id(),
                'is_automatic' => true,
            ]);

            // Start backup process
            $this->startBackupProcess($backup);

            DB::commit();

            return redirect()->back()
                ->with('success', 'پشتیبان‌گیری خودکار شروع شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در شروع پشتیبان‌گیری خودکار: ' . $e->getMessage()]);
        }
    }

    /**
     * Get backup statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_backups' => Backup::count(),
            'successful_backups' => Backup::where('status', 'completed')->count(),
            'failed_backups' => Backup::where('status', 'failed')->count(),
            'in_progress_backups' => Backup::where('status', 'in_progress')->count(),
            'by_type' => Backup::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'by_status' => Backup::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'total_size' => Backup::where('status', 'completed')->sum('size'),
            'average_size' => Backup::where('status', 'completed')->avg('size'),
            'backups_by_month' => Backup::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_backups' => Backup::orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return view('admin.backup.statistics', compact('stats'));
    }

    /**
     * Start backup process.
     */
    private function startBackupProcess(Backup $backup)
    {
        $backup->update(['status' => 'in_progress']);

        // In a real application, you would dispatch a job here
        // For now, we'll simulate the process
        try {
            $backupPath = 'backups/' . $backup->id . '_' . time() . '.zip';
            
            // Simulate backup creation
            $this->createBackupFile($backup, $backupPath);
            
            $backup->update([
                'status' => 'completed',
                'file_path' => $backupPath,
                'size' => Storage::disk('local')->size($backupPath),
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Start restore process.
     */
    private function startRestoreProcess(Backup $backup)
    {
        // In a real application, you would dispatch a job here
        // For now, we'll simulate the process
        try {
            // Simulate restore process
            sleep(2); // Simulate processing time
            
            $backup->update([
                'status' => 'completed',
                'restored_at' => now(),
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create backup file.
     */
    private function createBackupFile(Backup $backup, $path)
    {
        // In a real application, you would create actual backup files here
        // For now, we'll create a dummy file
        $content = "Backup created at: " . now() . "\n";
        $content .= "Type: " . $backup->type . "\n";
        $content .= "Compression: " . ($backup->compression ? 'Yes' : 'No') . "\n";
        $content .= "Encryption: " . ($backup->encryption ? 'Yes' : 'No') . "\n";
        
        Storage::disk('local')->put($path, $content);
    }

    /**
     * Get backup logs.
     */
    private function getBackupLogs(Backup $backup)
    {
        // In a real application, you would read actual log files
        return [
            ['time' => $backup->created_at, 'message' => 'پشتیبان‌گیری شروع شد'],
            ['time' => $backup->completed_at, 'message' => 'پشتیبان‌گیری تکمیل شد'],
        ];
    }

    /**
     * Create multiple download zip.
     */
    private function createMultipleDownloadZip($backups)
    {
        // In a real application, you would create a zip file with multiple backups
        // For now, we'll just return success
        return true;
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = Backup::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by compression
        if ($request->filled('compression')) {
            $query->where('compression', $request->boolean('compression'));
        }

        // Filter by encryption
        if ($request->filled('encryption')) {
            $query->where('encryption', $request->boolean('encryption'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $backups = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $backups->items(),
            'pagination' => [
                'current_page' => $backups->currentPage(),
                'last_page' => $backups->lastPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
            ]
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:full,incremental,differential',
            'description' => 'nullable|string|max:1000',
            'compression' => 'boolean',
            'encryption' => 'boolean',
            'schedule' => 'nullable|string|max:100',
            'retention_days' => 'nullable|integer|min:1|max:365',
            'include_files' => 'boolean',
            'include_database' => 'boolean',
            'include_media' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $backup = Backup::create([
                'name' => $request->name,
                'type' => $request->type,
                'description' => $request->description,
                'status' => 'pending',
                'compression' => $request->boolean('compression', true),
                'encryption' => $request->boolean('encryption', false),
                'schedule' => $request->schedule,
                'retention_days' => $request->retention_days,
                'include_files' => $request->boolean('include_files', true),
                'include_database' => $request->boolean('include_database', true),
                'include_media' => $request->boolean('include_media', true),
            ]);

            // Start backup process
            $this->startBackupProcess($backup);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'پشتیبان‌گیری با موفقیت ایجاد شد.',
                'data' => $backup
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating backup: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد پشتیبان‌گیری: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShow(Backup $backup)
    {
        $backup->logs = $this->getBackupLogs($backup);

        return response()->json([
            'success' => true,
            'data' => $backup
        ]);
    }

    public function apiUpdate(Request $request, Backup $backup)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:full,incremental,differential',
            'description' => 'nullable|string|max:1000',
            'compression' => 'boolean',
            'encryption' => 'boolean',
            'schedule' => 'nullable|string|max:100',
            'retention_days' => 'nullable|integer|min:1|max:365',
            'include_files' => 'boolean',
            'include_database' => 'boolean',
            'include_media' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $backup->update([
                'name' => $request->name,
                'type' => $request->type,
                'description' => $request->description,
                'compression' => $request->boolean('compression'),
                'encryption' => $request->boolean('encryption'),
                'schedule' => $request->schedule,
                'retention_days' => $request->retention_days,
                'include_files' => $request->boolean('include_files'),
                'include_database' => $request->boolean('include_database'),
                'include_media' => $request->boolean('include_media'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'پشتیبان‌گیری با موفقیت به‌روزرسانی شد.',
                'data' => $backup
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating backup: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی پشتیبان‌گیری: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDestroy(Backup $backup)
    {
        try {
            DB::beginTransaction();

            // Delete backup file if exists
            if ($backup->file_path && Storage::disk('local')->exists($backup->file_path)) {
                Storage::disk('local')->delete($backup->file_path);
            }

            $backup->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'پشتیبان‌گیری با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting backup: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف پشتیبان‌گیری: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:download,delete,restore',
            'backup_ids' => 'required|array|min:1',
            'backup_ids.*' => 'integer|exists:backups,id',
        ]);

        try {
            DB::beginTransaction();

            $backupIds = $request->backup_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($backupIds as $backupId) {
                try {
                    $backup = Backup::findOrFail($backupId);

                    switch ($action) {
                        case 'download':
                            // In a real application, you would prepare files for download
                            break;

                        case 'delete':
                            // Delete backup file if exists
                            if ($backup->file_path && Storage::disk('local')->exists($backup->file_path)) {
                                Storage::disk('local')->delete($backup->file_path);
                            }
                            $backup->delete();
                            break;

                        case 'restore':
                            $this->startRestoreProcess($backup);
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for backup', [
                        'backup_id' => $backupId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $actionLabels = [
                'download' => 'دانلود',
                'delete' => 'حذف',
                'restore' => 'بازیابی',
            ];

            $message = "عملیات {$actionLabels[$action]} روی {$successCount} پشتیبان‌گیری انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} پشتیبان‌گیری ناموفق بود";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'backup_ids' => $request->backup_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiStatistics()
    {
        $stats = [
            'total_backups' => Backup::count(),
            'completed_backups' => Backup::where('status', 'completed')->count(),
            'failed_backups' => Backup::where('status', 'failed')->count(),
            'in_progress_backups' => Backup::where('status', 'in_progress')->count(),
            'pending_backups' => Backup::where('status', 'pending')->count(),
            'backups_by_type' => Backup::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'backups_by_status' => Backup::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'total_size' => Backup::where('status', 'completed')->sum('size'),
            'average_size' => Backup::where('status', 'completed')->avg('size'),
            'backups_by_month' => Backup::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_backups' => Backup::orderBy('created_at', 'desc')->limit(10)->get(),
            'compression_enabled' => Backup::where('compression', true)->count(),
            'encryption_enabled' => Backup::where('encryption', true)->count(),
            'scheduled_backups' => Backup::whereNotNull('schedule')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function apiDownload(Backup $backup)
    {
        if (!$backup->file_path || !Storage::disk('local')->exists($backup->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'فایل پشتیبان‌گیری یافت نشد.'
            ], 404);
        }

        return Storage::disk('local')->download($backup->file_path, $backup->name . '.zip');
    }

    public function apiRestore(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'فقط پشتیبان‌گیری‌های تکمیل شده قابل بازیابی هستند.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $backup->update(['status' => 'restoring']);
            $this->startRestoreProcess($backup);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'فرآیند بازیابی شروع شد.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error starting restore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در شروع بازیابی: ' . $e->getMessage()
            ], 500);
        }
    }
}