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

        $episodes = Episode::where('is_active', true)->get();

        return view('admin.audio-management.index', compact('audioFiles', 'stats', 'episodes'));
    }

    public function upload()
    {
        $episodes = Episode::where('is_active', true)->get();
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
        $episodes = Episode::where('is_active', true)->get();
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
}