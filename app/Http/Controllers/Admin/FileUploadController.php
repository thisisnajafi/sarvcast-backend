<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    /**
     * Display a listing of uploaded files.
     */
    public function index(Request $request)
    {
        $query = FileUpload::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by file type
        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by uploader
        if ($request->filled('uploaded_by')) {
            $query->where('uploaded_by', $request->uploaded_by);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $files = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => FileUpload::count(),
            'active' => FileUpload::where('status', 'active')->count(),
            'inactive' => FileUpload::where('status', 'inactive')->count(),
            'pending' => FileUpload::where('status', 'pending')->count(),
            'images' => FileUpload::where('file_type', 'image')->count(),
            'documents' => FileUpload::where('file_type', 'document')->count(),
            'videos' => FileUpload::where('file_type', 'video')->count(),
            'audio' => FileUpload::where('file_type', 'audio')->count(),
            'total_size' => FileUpload::sum('file_size'),
        ];

        return view('admin.file-upload.index', compact('files', 'stats'));
    }

    /**
     * Show the form for uploading a new file.
     */
    public function upload()
    {
        return view('admin.file-upload.upload');
    }

    /**
     * Store a newly uploaded file.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'title' => 'required|string|max:255',
            'category' => 'required|in:content,media,documents,assets,other',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'tags' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $originalName;
            $fileSize = $file->getSize();
            $fileType = $this->getFileType($file->getMimeType());
            $fileExtension = $file->getClientOriginalExtension();
            
            // Determine storage path based on file type
            $storagePath = $this->getStoragePath($fileType, $request->category);
            $filePath = $file->storeAs($storagePath, $fileName, 'public');

            $fileUpload = FileUpload::create([
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'file_type' => $fileType,
                'file_extension' => $fileExtension,
                'mime_type' => $file->getMimeType(),
                'title' => $request->title,
                'category' => $request->category,
                'description' => $request->description,
                'is_public' => $request->has('is_public'),
                'tags' => $request->tags,
                'status' => 'active',
                'uploaded_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.file-upload.index')
                ->with('success', 'فایل با موفقیت آپلود شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در آپلود فایل: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified file.
     */
    public function show(FileUpload $fileUpload)
    {
        $fileUpload->load(['uploadedBy']);
        
        // Get related files
        $relatedFiles = FileUpload::where('category', $fileUpload->category)
            ->where('id', '!=', $fileUpload->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.file-upload.show', compact('fileUpload', 'relatedFiles'));
    }

    /**
     * Show the form for editing the specified file.
     */
    public function edit(FileUpload $fileUpload)
    {
        return view('admin.file-upload.edit', compact('fileUpload'));
    }

    /**
     * Update the specified file.
     */
    public function update(Request $request, FileUpload $fileUpload)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category' => 'required|in:content,media,documents,assets,other',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'tags' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,pending',
            'file' => 'nullable|file|max:102400',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'title' => $request->title,
                'category' => $request->category,
                'description' => $request->description,
                'is_public' => $request->has('is_public'),
                'tags' => $request->tags,
                'status' => $request->status,
            ];

            // Handle new file upload
            if ($request->hasFile('file')) {
                // Delete old file
                if ($fileUpload->file_path && Storage::disk('public')->exists($fileUpload->file_path)) {
                    Storage::disk('public')->delete($fileUpload->file_path);
                }

                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $fileName = time() . '_' . $originalName;
                $fileSize = $file->getSize();
                $fileType = $this->getFileType($file->getMimeType());
                $fileExtension = $file->getClientOriginalExtension();
                
                $storagePath = $this->getStoragePath($fileType, $request->category);
                $filePath = $file->storeAs($storagePath, $fileName, 'public');

                $data = array_merge($data, [
                    'original_name' => $originalName,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_type' => $fileType,
                    'file_extension' => $fileExtension,
                    'mime_type' => $file->getMimeType(),
                ]);
            }

            $fileUpload->update($data);

            DB::commit();

            return redirect()->route('admin.file-upload.index')
                ->with('success', 'فایل با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در به‌روزرسانی فایل: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified file.
     */
    public function destroy(FileUpload $fileUpload)
    {
        try {
            // Delete file from storage
            if ($fileUpload->file_path && Storage::disk('public')->exists($fileUpload->file_path)) {
                Storage::disk('public')->delete($fileUpload->file_path);
            }

            $fileUpload->delete();
            return redirect()->route('admin.file-upload.index')
                ->with('success', 'فایل با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف فایل: ' . $e->getMessage()]);
        }
    }

    /**
     * Download file.
     */
    public function download(FileUpload $fileUpload)
    {
        if (!Storage::disk('public')->exists($fileUpload->file_path)) {
            return redirect()->back()
                ->withErrors(['error' => 'فایل یافت نشد.']);
        }

        return Storage::disk('public')->download($fileUpload->file_path, $fileUpload->original_name);
    }

    /**
     * Toggle file status.
     */
    public function toggleStatus(FileUpload $fileUpload)
    {
        try {
            $newStatus = $fileUpload->status === 'active' ? 'inactive' : 'active';
            $fileUpload->update(['status' => $newStatus]);

            $statusLabel = $newStatus === 'active' ? 'فعال' : 'غیرفعال';
            return redirect()->back()
                ->with('success', "فایل {$statusLabel} شد.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در تغییر وضعیت: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on files.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete,download',
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'exists:file_uploads,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک فایل را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $files = FileUpload::whereIn('id', $request->file_ids);

            switch ($request->action) {
                case 'activate':
                    $files->update(['status' => 'active']);
                    break;

                case 'deactivate':
                    $files->update(['status' => 'inactive']);
                    break;

                case 'delete':
                    // Delete files from storage
                    foreach ($files->get() as $file) {
                        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                            Storage::disk('public')->delete($file->file_path);
                        }
                    }
                    $files->delete();
                    break;

                case 'download':
                    // For multiple downloads, we'll create a zip file
                    $this->createMultipleDownloadZip($files->get());
                    break;
            }

            DB::commit();

            $actionLabels = [
                'activate' => 'فعال‌سازی',
                'deactivate' => 'غیرفعال‌سازی',
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
     * Get file upload statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_files' => FileUpload::count(),
            'active_files' => FileUpload::where('status', 'active')->count(),
            'inactive_files' => FileUpload::where('status', 'inactive')->count(),
            'pending_files' => FileUpload::where('status', 'pending')->count(),
            'by_type' => FileUpload::selectRaw('file_type, COUNT(*) as count')
                ->groupBy('file_type')
                ->get(),
            'by_category' => FileUpload::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get(),
            'by_status' => FileUpload::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'total_size' => FileUpload::sum('file_size'),
            'average_size' => FileUpload::avg('file_size'),
            'files_by_month' => FileUpload::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_files' => FileUpload::orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return view('admin.file-upload.statistics', compact('stats'));
    }

    /**
     * Get file type from MIME type.
     */
    private function getFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return 'document';
        } else {
            return 'other';
        }
    }

    /**
     * Get storage path based on file type and category.
     */
    private function getStoragePath($fileType, $category)
    {
        return "uploads/{$category}/{$fileType}";
    }

    /**
     * Create multiple download zip.
     */
    private function createMultipleDownloadZip($files)
    {
        // In a real application, you would create a zip file with multiple files
        // For now, we'll just return success
        return true;
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = FileUpload::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by file type
        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by privacy
        if ($request->filled('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $files = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $files->items(),
            'pagination' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
            ]
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:story,episode,character,background,music,sound_effect',
            'tags' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $originalName;
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $fileType = $this->getFileType($mimeType);
            $storagePath = $this->getStoragePath($fileType, $request->category);
            $filePath = $file->storeAs($storagePath, $fileName, 'public');

            $fileUpload = FileUpload::create([
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'file_type' => $fileType,
                'category' => $request->category,
                'title' => $request->title ?: pathinfo($originalName, PATHINFO_FILENAME),
                'description' => $request->description,
                'tags' => $request->tags,
                'status' => 'active',
                'is_public' => $request->boolean('is_public', true),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'فایل با موفقیت آپلود شد.',
                'data' => $fileUpload
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading file: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShow(FileUpload $fileUpload)
    {
        return response()->json([
            'success' => true,
            'data' => $fileUpload
        ]);
    }

    public function apiUpdate(Request $request, FileUpload $fileUpload)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:story,episode,character,background,music,sound_effect',
            'tags' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,pending',
            'is_public' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $fileUpload->update([
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category,
                'tags' => $request->tags,
                'status' => $request->status,
                'is_public' => $request->boolean('is_public'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'فایل با موفقیت به‌روزرسانی شد.',
                'data' => $fileUpload
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating file: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDestroy(FileUpload $fileUpload)
    {
        try {
            DB::beginTransaction();

            // Delete file from storage
            if (Storage::disk('public')->exists($fileUpload->file_path)) {
                Storage::disk('public')->delete($fileUpload->file_path);
            }

            $fileUpload->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'فایل با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting file: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete,download,make_public,make_private',
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'integer|exists:file_uploads,id',
        ]);

        try {
            DB::beginTransaction();

            $fileIds = $request->file_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($fileIds as $fileId) {
                try {
                    $file = FileUpload::findOrFail($fileId);

                    switch ($action) {
                        case 'activate':
                            $file->update(['status' => 'active']);
                            break;

                        case 'deactivate':
                            $file->update(['status' => 'inactive']);
                            break;

                        case 'delete':
                            // Delete file from storage
                            if (Storage::disk('public')->exists($file->file_path)) {
                                Storage::disk('public')->delete($file->file_path);
                            }
                            $file->delete();
                            break;

                        case 'download':
                            // In a real application, you would prepare files for download
                            break;

                        case 'make_public':
                            $file->update(['is_public' => true]);
                            break;

                        case 'make_private':
                            $file->update(['is_public' => false]);
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for file', [
                        'file_id' => $fileId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $actionLabels = [
                'activate' => 'فعال‌سازی',
                'deactivate' => 'غیرفعال‌سازی',
                'delete' => 'حذف',
                'download' => 'دانلود',
                'make_public' => 'عمومی کردن',
                'make_private' => 'خصوصی کردن',
            ];

            $message = "عملیات {$actionLabels[$action]} روی {$successCount} فایل انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} فایل ناموفق بود";
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
                'file_ids' => $request->file_ids,
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
            'total_files' => FileUpload::count(),
            'active_files' => FileUpload::where('status', 'active')->count(),
            'inactive_files' => FileUpload::where('status', 'inactive')->count(),
            'pending_files' => FileUpload::where('status', 'pending')->count(),
            'public_files' => FileUpload::where('is_public', true)->count(),
            'private_files' => FileUpload::where('is_public', false)->count(),
            'total_size' => FileUpload::sum('file_size'),
            'average_size' => FileUpload::avg('file_size'),
            'files_by_type' => FileUpload::selectRaw('file_type, COUNT(*) as count')
                ->groupBy('file_type')
                ->get(),
            'files_by_category' => FileUpload::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get(),
            'files_by_status' => FileUpload::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'files_by_month' => FileUpload::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_files' => FileUpload::orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function apiDownload(FileUpload $fileUpload)
    {
        if (!Storage::disk('public')->exists($fileUpload->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'فایل یافت نشد.'
            ], 404);
        }

        return Storage::disk('public')->download($fileUpload->file_path, $fileUpload->original_name);
    }
}
