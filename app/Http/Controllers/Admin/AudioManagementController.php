<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioFile;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AudioManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = AudioFile::with(['episode']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('quality')) {
            $query->where('quality', $request->quality);
        }

        $audioFiles = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => AudioFile::count(),
            'processed' => AudioFile::where('status', 'processed')->count(),
            'processing' => AudioFile::where('status', 'processing')->count(),
            'failed' => AudioFile::where('status', 'failed')->count(),
            'pending' => AudioFile::where('status', 'pending')->count(),
            'total_size' => AudioFile::sum('file_size'),
        ];

        $episodes = Episode::where('status', 'published')->get();

        return view('admin.audio-management.index', compact('audioFiles', 'stats', 'episodes'));
    }

    public function upload()
    {
        $episodes = Episode::where('status', 'published')->get();
        return view('admin.audio-management.upload', compact('episodes'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audio_file' => 'required|file|mimes:mp3,wav,flac,aac,m4a|max:102400',
            'title' => 'required|string|max:255',
            'episode_id' => 'required|exists:episodes,id',
            'quality' => 'required|in:low,medium,high',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $file = $request->file('audio_file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $originalName;
            $filePath = $file->storeAs('audio/episodes', $fileName, 'public');
            $fileSize = $file->getSize();

            $audioFile = AudioFile::create([
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'title' => $request->title,
                'episode_id' => $request->episode_id,
                'quality' => $request->quality,
                'description' => $request->description,
                'status' => 'pending',
                'uploaded_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.audio-management.index')
                ->with('success', 'فایل صوتی با موفقیت آپلود شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در آپلود فایل صوتی: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(AudioFile $audioFile)
    {
        $audioFile->load(['episode', 'uploadedBy']);
        
        $relatedFiles = AudioFile::where('episode_id', $audioFile->episode_id)
            ->where('id', '!=', $audioFile->id)
            ->with(['episode'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.audio-management.show', compact('audioFile', 'relatedFiles'));
    }

    public function edit(AudioFile $audioFile)
    {
        $episodes = Episode::where('status', 'published')->get();
        return view('admin.audio-management.edit', compact('audioFile', 'episodes'));
    }

    public function update(Request $request, AudioFile $audioFile)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'episode_id' => 'required|exists:episodes,id',
            'quality' => 'required|in:low,medium,high',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $audioFile->update([
                'title' => $request->title,
                'episode_id' => $request->episode_id,
                'quality' => $request->quality,
                'description' => $request->description,
            ]);

            return redirect()->route('admin.audio-management.index')
                ->with('success', 'فایل صوتی با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در به‌روزرسانی فایل صوتی: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(AudioFile $audioFile)
    {
        try {
            if ($audioFile->file_path && Storage::disk('public')->exists($audioFile->file_path)) {
                Storage::disk('public')->delete($audioFile->file_path);
            }

            $audioFile->delete();
            return redirect()->route('admin.audio-management.index')
                ->with('success', 'فایل صوتی با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف فایل صوتی: ' . $e->getMessage()]);
        }
    }

    public function process(AudioFile $audioFile)
    {
        try {
            $audioFile->update(['status' => 'processing']);
            
            // Simulate processing
            sleep(2);
            
            $audioFile->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return redirect()->back()
                ->with('success', 'پردازش فایل صوتی تکمیل شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در پردازش: ' . $e->getMessage()]);
        }
    }

    public function download(AudioFile $audioFile)
    {
        if (!Storage::disk('public')->exists($audioFile->file_path)) {
            return redirect()->back()
                ->withErrors(['error' => 'فایل صوتی یافت نشد.']);
        }

        return Storage::disk('public')->download($audioFile->file_path, $audioFile->original_name);
    }

    public function statistics()
    {
        $stats = [
            'total_files' => AudioFile::count(),
            'processed_files' => AudioFile::where('status', 'processed')->count(),
            'processing_files' => AudioFile::where('status', 'processing')->count(),
            'failed_files' => AudioFile::where('status', 'failed')->count(),
            'pending_files' => AudioFile::where('status', 'pending')->count(),
            'total_size' => AudioFile::sum('file_size'),
        ];

        return view('admin.audio-management.statistics', compact('stats'));
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = AudioFile::with(['episode', 'story']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhereHas('episode', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('story', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by file type
        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        // Filter by episode
        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
        }

        // Filter by story
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audioFiles = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $audioFiles->items(),
            'pagination' => [
                'current_page' => $audioFiles->currentPage(),
                'last_page' => $audioFiles->lastPage(),
                'per_page' => $audioFiles->perPage(),
                'total' => $audioFiles->total(),
            ]
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:mp3,wav,ogg,m4a,aac|max:102400', // 100MB max
            'episode_id' => 'nullable|exists:episodes,id',
            'story_id' => 'nullable|exists:stories,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $originalName;
            $filePath = $file->storeAs('audio', $fileName, 'public');
            $fileSize = $file->getSize();
            $fileType = $file->getClientOriginalExtension();

            $audioFile = AudioFile::create([
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'file_type' => $fileType,
                'episode_id' => $request->episode_id,
                'story_id' => $request->story_id,
                'title' => $request->title ?: pathinfo($originalName, PATHINFO_FILENAME),
                'description' => $request->description,
                'tags' => $request->tags,
                'status' => 'pending',
                'is_public' => $request->boolean('is_public', true),
            ]);

            // Start processing
            $this->processAudioFile($audioFile);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'فایل صوتی با موفقیت آپلود شد.',
                'data' => $audioFile->load(['episode', 'story'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading audio file: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود فایل صوتی: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShow(AudioFile $audioFile)
    {
        $audioFile->load(['episode', 'story']);

        return response()->json([
            'success' => true,
            'data' => $audioFile
        ]);
    }

    public function apiUpdate(Request $request, AudioFile $audioFile)
    {
        $request->validate([
            'episode_id' => 'nullable|exists:episodes,id',
            'story_id' => 'nullable|exists:stories,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $audioFile->update([
                'episode_id' => $request->episode_id,
                'story_id' => $request->story_id,
                'title' => $request->title,
                'description' => $request->description,
                'tags' => $request->tags,
                'is_public' => $request->boolean('is_public'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'فایل صوتی با موفقیت به‌روزرسانی شد.',
                'data' => $audioFile->load(['episode', 'story'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating audio file: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی فایل صوتی: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDestroy(AudioFile $audioFile)
    {
        try {
            DB::beginTransaction();

            // Delete file from storage
            if (Storage::disk('public')->exists($audioFile->file_path)) {
                Storage::disk('public')->delete($audioFile->file_path);
            }

            $audioFile->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'فایل صوتی با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting audio file: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف فایل صوتی: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:delete,process,make_public,make_private',
            'audio_file_ids' => 'required|array|min:1',
            'audio_file_ids.*' => 'integer|exists:audio_files,id',
        ]);

        try {
            DB::beginTransaction();

            $audioFileIds = $request->audio_file_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($audioFileIds as $audioFileId) {
                try {
                    $audioFile = AudioFile::findOrFail($audioFileId);

                    switch ($action) {
                        case 'delete':
                            // Delete file from storage
                            if (Storage::disk('public')->exists($audioFile->file_path)) {
                                Storage::disk('public')->delete($audioFile->file_path);
                            }
                            $audioFile->delete();
                            break;

                        case 'process':
                            $this->processAudioFile($audioFile);
                            break;

                        case 'make_public':
                            $audioFile->update(['is_public' => true]);
                            break;

                        case 'make_private':
                            $audioFile->update(['is_public' => false]);
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for audio file', [
                        'audio_file_id' => $audioFileId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $actionLabels = [
                'delete' => 'حذف',
                'process' => 'پردازش',
                'make_public' => 'عمومی کردن',
                'make_private' => 'خصوصی کردن',
            ];

            $message = "عملیات {$actionLabels[$action]} روی {$successCount} فایل صوتی انجام شد";
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
                'audio_file_ids' => $request->audio_file_ids,
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
            'total_files' => AudioFile::count(),
            'processed_files' => AudioFile::where('status', 'processed')->count(),
            'processing_files' => AudioFile::where('status', 'processing')->count(),
            'failed_files' => AudioFile::where('status', 'failed')->count(),
            'pending_files' => AudioFile::where('status', 'pending')->count(),
            'public_files' => AudioFile::where('is_public', true)->count(),
            'private_files' => AudioFile::where('is_public', false)->count(),
            'total_size' => AudioFile::sum('file_size'),
            'average_size' => AudioFile::avg('file_size'),
            'files_by_type' => AudioFile::selectRaw('file_type, COUNT(*) as count')
                ->groupBy('file_type')
                ->get(),
            'files_by_status' => AudioFile::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'files_by_month' => AudioFile::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_files' => AudioFile::with(['episode', 'story'])
                ->latest()
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function apiDownload(AudioFile $audioFile)
    {
        if (!Storage::disk('public')->exists($audioFile->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'فایل صوتی یافت نشد.'
            ], 404);
        }

        return Storage::disk('public')->download($audioFile->file_path, $audioFile->original_name);
    }

    public function apiProcess(AudioFile $audioFile)
    {
        try {
            $this->processAudioFile($audioFile);

            return response()->json([
                'success' => true,
                'message' => 'فرآیند پردازش فایل صوتی شروع شد.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error starting audio processing: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در شروع پردازش فایل صوتی: ' . $e->getMessage()
            ], 500);
        }
    }
}