<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AudioProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AudioProcessingController extends Controller
{
    protected $audioProcessingService;

    public function __construct(AudioProcessingService $audioProcessingService)
    {
        $this->audioProcessingService = $audioProcessingService;
    }

    /**
     * Process audio file
     */
    public function processAudio(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'convert_format' => 'nullable|string|in:mp3,wav,m4a,aac,ogg',
            'bitrate' => 'nullable|integer|min:64|max:320',
            'normalize' => 'nullable|boolean',
            'fade_in' => 'nullable|integer|min:0|max:30',
            'fade_out' => 'nullable|integer|min:0|max:30',
            'trim_start' => 'nullable|integer|min:0',
            'trim_end' => 'nullable|integer|min:0',
            'volume' => 'nullable|numeric|min:0.1|max:10.0',
            'quality' => 'nullable|string|in:low,medium,high'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'file_path.string' => 'مسیر فایل باید رشته باشد',
            'convert_format.in' => 'فرمت تبدیل نامعتبر است',
            'bitrate.min' => 'نرخ بیت نباید کمتر از 64 باشد',
            'bitrate.max' => 'نرخ بیت نباید بیشتر از 320 باشد',
            'fade_in.max' => 'مدت زمان fade in نباید بیشتر از 30 ثانیه باشد',
            'fade_out.max' => 'مدت زمان fade out نباید بیشتر از 30 ثانیه باشد',
            'volume.min' => 'حجم صدا نباید کمتر از 0.1 باشد',
            'volume.max' => 'حجم صدا نباید بیشتر از 10 باشد',
            'quality.in' => 'کیفیت نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            
            // Check if file exists
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $options = $request->only([
                'convert_format', 'bitrate', 'normalize', 'fade_in', 
                'fade_out', 'trim_start', 'trim_end', 'volume', 'quality'
            ]);

            $result = $this->audioProcessingService->processAudio($fullPath, $options);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در پردازش فایل صوتی'
                ], 500);
            }

            // Move processed file to storage
            $processedPath = $this->moveProcessedFile($result['processed_file'], $filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'processed_file' => $processedPath,
                    'metadata' => $result['metadata'],
                    'processing_info' => $result['processing_info']
                ],
                'message' => 'فایل صوتی با موفقیت پردازش شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Audio processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پردازش فایل صوتی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract metadata from audio file
     */
    public function extractMetadata(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'file_path.string' => 'مسیر فایل باید رشته باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $metadata = $this->audioProcessingService->extractMetadata($fullPath);

            return response()->json([
                'success' => true,
                'data' => [
                    'file_path' => $filePath,
                    'metadata' => $metadata
                ],
                'message' => 'متادیتا با موفقیت استخراج شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Metadata extraction failed', [
                'error' => $e->getMessage(),
                'file_path' => $request->input('file_path')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در استخراج متادیتا: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert audio format
     */
    public function convertFormat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'target_format' => 'required|string|in:mp3,wav,m4a,aac,ogg',
            'bitrate' => 'nullable|integer|min:64|max:320'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'target_format.required' => 'فرمت هدف الزامی است',
            'target_format.in' => 'فرمت هدف نامعتبر است',
            'bitrate.min' => 'نرخ بیت نباید کمتر از 64 باشد',
            'bitrate.max' => 'نرخ بیت نباید بیشتر از 320 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            $targetFormat = $request->input('target_format');
            $bitrate = $request->input('bitrate', 192);
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->audioProcessingService->convertFormat($fullPath, $targetFormat, $bitrate);

            // Move processed file to storage
            $convertedPath = $this->moveProcessedFile($processedPath, $filePath, $targetFormat);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'converted_file' => $convertedPath,
                    'target_format' => $targetFormat,
                    'bitrate' => $bitrate
                ],
                'message' => 'تبدیل فرمت با موفقیت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Format conversion failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در تبدیل فرمت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalize audio
     */
    public function normalizeAudio(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->audioProcessingService->normalizeAudio($fullPath);

            // Move processed file to storage
            $normalizedPath = $this->moveProcessedFile($processedPath, $filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'normalized_file' => $normalizedPath
                ],
                'message' => 'نرمال‌سازی صدا با موفقیت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Audio normalization failed', [
                'error' => $e->getMessage(),
                'file_path' => $request->input('file_path')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در نرمال‌سازی صدا: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trim audio file
     */
    public function trimAudio(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'start_seconds' => 'required|integer|min:0',
            'end_seconds' => 'nullable|integer|min:0'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'start_seconds.required' => 'زمان شروع الزامی است',
            'start_seconds.min' => 'زمان شروع نمی‌تواند منفی باشد',
            'end_seconds.min' => 'زمان پایان نمی‌تواند منفی باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            $startSeconds = $request->input('start_seconds');
            $endSeconds = $request->input('end_seconds');
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->audioProcessingService->trimAudio($fullPath, $startSeconds, $endSeconds);

            // Move processed file to storage
            $trimmedPath = $this->moveProcessedFile($processedPath, $filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'trimmed_file' => $trimmedPath,
                    'start_seconds' => $startSeconds,
                    'end_seconds' => $endSeconds
                ],
                'message' => 'برش فایل صوتی با موفقیت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Audio trimming failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در برش فایل صوتی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate audio file
     */
    public function validateAudio(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'data' => [
                        'valid' => false,
                        'errors' => ['فایل یافت نشد']
                    ]
                ]);
            }

            $fullPath = Storage::path($filePath);
            $validation = $this->audioProcessingService->validateAudioFile($fullPath);

            return response()->json([
                'success' => true,
                'data' => $validation,
                'message' => $validation['valid'] ? 'فایل صوتی معتبر است' : 'فایل صوتی نامعتبر است'
            ]);

        } catch (\Exception $e) {
            Log::error('Audio validation failed', [
                'error' => $e->getMessage(),
                'file_path' => $request->input('file_path')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get processing statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->audioProcessingService->getProcessingStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار پردازش صدا دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get audio processing stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار پردازش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup temporary files
     */
    public function cleanup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_paths' => 'nullable|array',
            'file_paths.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePaths = $request->input('file_paths', []);
            $cleanedCount = $this->audioProcessingService->cleanupTempFiles($filePaths);

            return response()->json([
                'success' => true,
                'data' => [
                    'cleaned_files' => $cleanedCount
                ],
                'message' => "{$cleanedCount} فایل موقت پاک شد"
            ]);

        } catch (\Exception $e) {
            Log::error('Audio cleanup failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پاک‌سازی فایل‌های موقت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move processed file to storage
     */
    protected function moveProcessedFile(string $processedPath, string $originalPath, string $format = null): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        
        if ($format) {
            $newFilename = $filename . '_processed.' . $format;
        } else {
            $extension = pathinfo($processedPath, PATHINFO_EXTENSION);
            $newFilename = $filename . '_processed.' . $extension;
        }
        
        $newPath = $directory . '/' . $newFilename;
        
        // Copy processed file to storage
        Storage::put($newPath, file_get_contents($processedPath));
        
        // Delete temporary file
        unlink($processedPath);
        
        return $newPath;
    }
}
