<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Services\AudioProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AudioManagementController extends Controller
{
    protected $audioProcessingService;

    public function __construct(AudioProcessingService $audioProcessingService)
    {
        $this->audioProcessingService = $audioProcessingService;
    }

    /**
     * Display audio management dashboard
     */
    public function index(): JsonResponse
    {
        $stats = $this->getAudioStats();
        
        return response()->json([
            'success' => true,
            'message' => 'داشبورد مدیریت صدا دریافت شد',
            'data' => [
                'stats' => $stats,
                'recent_uploads' => $this->getRecentUploads(),
                'processing_queue' => $this->getProcessingQueue(),
                'storage_info' => $this->getStorageInfo()
            ]
        ]);
    }

    /**
     * Upload audio file
     */
    public function uploadAudio(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a,aac|max:50000', // 50MB max
                'episode_id' => 'required|exists:episodes,id',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            $episode = Episode::findOrFail($request->episode_id);
            
            // Store the uploaded file
            $file = $request->file('audio_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('audio/episodes', $filename, 'public');

            // Process the audio file
            $processingResult = $this->audioProcessingService->processAudio($path, [
                'extract_metadata' => true,
                'normalize' => true,
                'generate_waveform' => true
            ]);

            // Update episode with audio file info
            $episode->update([
                'audio_file' => $path,
                'duration' => $processingResult['duration'] ?? 0,
                'file_size' => $file->getSize(),
                'audio_format' => $file->getClientOriginalExtension(),
                'title' => $request->title ?? $episode->title,
                'description' => $request->description ?? $episode->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'فایل صوتی با موفقیت آپلود و پردازش شد',
                'data' => [
                    'episode_id' => $episode->id,
                    'file_path' => $path,
                    'duration' => $processingResult['duration'],
                    'file_size' => $file->getSize(),
                    'processing_result' => $processingResult
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی فایل صوتی',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Audio upload failed', [
                'error' => $e->getMessage(),
                'episode_id' => $request->episode_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطایی در آپلود فایل صوتی رخ داد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audio statistics
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->getAudioStats();

        return response()->json([
            'success' => true,
            'message' => 'آمار فایل‌های صوتی دریافت شد',
            'data' => $stats
        ]);
    }

    /**
     * Get audio statistics
     */
    private function getAudioStats(): array
    {
        $totalEpisodes = Episode::count();
        $episodesWithAudio = Episode::whereNotNull('audio_file')->count();
        $totalAudioSize = Episode::whereNotNull('audio_file')->sum('file_size');
        $averageDuration = Episode::whereNotNull('audio_file')->avg('duration');

        return [
            'total_episodes' => $totalEpisodes,
            'episodes_with_audio' => $episodesWithAudio,
            'episodes_without_audio' => $totalEpisodes - $episodesWithAudio,
            'audio_coverage_percentage' => $totalEpisodes > 0 ? round(($episodesWithAudio / $totalEpisodes) * 100, 2) : 0,
            'total_audio_size' => $totalAudioSize,
            'total_audio_size_mb' => round($totalAudioSize / 1024 / 1024, 2),
            'average_duration' => round($averageDuration ?? 0, 2),
            'audio_formats' => Episode::whereNotNull('audio_format')
                ->selectRaw('audio_format, COUNT(*) as count')
                ->groupBy('audio_format')
                ->get()
                ->pluck('count', 'audio_format')
        ];
    }

    /**
     * Get recent audio uploads
     */
    private function getRecentUploads(): array
    {
        return Episode::whereNotNull('audio_file')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'audio_file', 'duration', 'file_size', 'audio_format', 'updated_at'])
            ->map(function ($episode) {
                return [
                    'id' => $episode->id,
                    'title' => $episode->title,
                    'audio_file' => $episode->audio_file,
                    'duration' => $episode->duration,
                    'file_size' => $episode->file_size,
                    'file_size_mb' => round($episode->file_size / 1024 / 1024, 2),
                    'audio_format' => $episode->audio_format,
                    'uploaded_at' => $episode->updated_at->format('Y-m-d H:i:s')
                ];
            })
            ->toArray();
    }

    /**
     * Get processing queue status
     */
    private function getProcessingQueue(): array
    {
        return [
            'pending_jobs' => 0,
            'processing_jobs' => 0,
            'failed_jobs' => 0,
            'completed_today' => Episode::whereNotNull('audio_file')
                ->whereDate('updated_at', today())
                ->count()
        ];
    }

    /**
     * Get storage information
     */
    private function getStorageInfo(): array
    {
        $audioPath = storage_path('app/public/audio');
        $totalSize = 0;
        $fileCount = 0;

        if (is_dir($audioPath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($audioPath)
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                    $fileCount++;
                }
            }
        }

        return [
            'total_files' => $fileCount,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'total_size_gb' => round($totalSize / 1024 / 1024 / 1024, 2),
            'storage_path' => $audioPath
        ];
    }
}