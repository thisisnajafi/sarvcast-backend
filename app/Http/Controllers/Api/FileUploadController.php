<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload image file
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120', // 5MB max
            'directory' => 'nullable|string|max:100',
            'resize' => 'nullable|array',
            'resize.width' => 'nullable|integer|min:50|max:2000',
            'resize.height' => 'nullable|integer|min:50|max:2000',
            'quality' => 'nullable|integer|min:10|max:100',
            'thumbnail' => 'nullable|array',
            'thumbnail.width' => 'nullable|integer|min:50|max:500',
            'thumbnail.height' => 'nullable|integer|min:50|max:500',
            'thumbnail.quality' => 'nullable|integer|min:10|max:100'
        ], [
            'image.required' => 'تصویر الزامی است',
            'image.image' => 'فایل باید یک تصویر باشد',
            'image.mimes' => 'فرمت تصویر باید یکی از موارد زیر باشد: jpeg, png, jpg, webp, gif',
            'image.max' => 'حجم تصویر نمی‌تواند بیش از 5 مگابایت باشد',
            'directory.max' => 'نام پوشه نمی‌تواند بیش از 100 کاراکتر باشد',
            'resize.width.min' => 'عرض تصویر باید حداقل 50 پیکسل باشد',
            'resize.width.max' => 'عرض تصویر نمی‌تواند بیش از 2000 پیکسل باشد',
            'resize.height.min' => 'ارتفاع تصویر باید حداقل 50 پیکسل باشد',
            'resize.height.max' => 'ارتفاع تصویر نمی‌تواند بیش از 2000 پیکسل باشد',
            'quality.min' => 'کیفیت تصویر باید حداقل 10 باشد',
            'quality.max' => 'کیفیت تصویر نمی‌تواند بیش از 100 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('image');
        $directory = $request->get('directory', 'images');
        
        $options = [];
        if ($request->has('resize')) {
            $options['resize'] = $request->get('resize');
        }
        if ($request->has('quality')) {
            $options['quality'] = $request->get('quality');
        }
        if ($request->has('thumbnail')) {
            $options['thumbnail'] = $request->get('thumbnail');
        }

        try {
            $result = $this->fileUploadService->uploadImage($file, $directory, $options);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? null
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'تصویر با موفقیت آپلود شد',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload audio file
     */
    public function uploadAudio(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio' => 'required|file|mimes:mp3,wav,m4a,aac,ogg|max:51200', // 50MB max
            'directory' => 'nullable|string|max:100',
            'extract_metadata' => 'nullable|boolean'
        ], [
            'audio.required' => 'فایل صوتی الزامی است',
            'audio.file' => 'فایل باید یک فایل معتبر باشد',
            'audio.mimes' => 'فرمت فایل صوتی باید یکی از موارد زیر باشد: mp3, wav, m4a, aac, ogg',
            'audio.max' => 'حجم فایل صوتی نمی‌تواند بیش از 50 مگابایت باشد',
            'directory.max' => 'نام پوشه نمی‌تواند بیش از 100 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('audio');
        $directory = $request->get('directory', 'audio');
        
        $options = [];
        if ($request->has('extract_metadata')) {
            $options['extract_metadata'] = $request->boolean('extract_metadata');
        }

        try {
            $result = $this->fileUploadService->uploadAudio($file, $directory, $options);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? null
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'فایل صوتی با موفقیت آپلود شد',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Audio upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود فایل صوتی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload document file
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240', // 10MB max
            'directory' => 'nullable|string|max:100'
        ], [
            'document.required' => 'سند الزامی است',
            'document.file' => 'فایل باید یک فایل معتبر باشد',
            'document.mimes' => 'فرمت سند باید یکی از موارد زیر باشد: pdf, doc, docx, txt',
            'document.max' => 'حجم سند نمی‌تواند بیش از 10 مگابایت باشد',
            'directory.max' => 'نام پوشه نمی‌تواند بیش از 100 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('document');
        $directory = $request->get('directory', 'documents');

        try {
            $result = $this->fileUploadService->uploadDocument($file, $directory);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? null
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'سند با موفقیت آپلود شد',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Document upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود سند: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file
     */
    public function deleteFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ], [
            'path.required' => 'مسیر فایل الزامی است',
            'path.string' => 'مسیر فایل باید رشته باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $path = $request->get('path');

        try {
            $result = $this->fileUploadService->deleteFile($path);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'فایل با موفقیت حذف شد' : 'فایل یافت نشد یا قبلاً حذف شده است'
            ]);

        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file info
     */
    public function getFileInfo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ], [
            'path.required' => 'مسیر فایل الزامی است',
            'path.string' => 'مسیر فایل باید رشته باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $path = $request->get('path');

        try {
            $result = $this->fileUploadService->getFileInfo($path);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get file info failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file',
            'type' => 'required|string|in:image,audio,document',
            'directory' => 'nullable|string|max:100'
        ], [
            'files.required' => 'فایل‌ها الزامی هستند',
            'files.array' => 'فایل‌ها باید آرایه باشند',
            'files.min' => 'حداقل یک فایل الزامی است',
            'files.max' => 'حداکثر 10 فایل قابل آپلود است',
            'files.*.required' => 'هر فایل الزامی است',
            'files.*.file' => 'هر آیتم باید یک فایل معتبر باشد',
            'type.required' => 'نوع فایل الزامی است',
            'type.in' => 'نوع فایل باید یکی از موارد زیر باشد: image, audio, document',
            'directory.max' => 'نام پوشه نمی‌تواند بیش از 100 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $files = $request->file('files');
        $type = $request->get('type');
        $directory = $request->get('directory', 'uploads');

        try {
            $result = $this->fileUploadService->uploadMultiple($files, $type, $directory);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'فایل‌ها با موفقیت آپلود شدند' : 'برخی فایل‌ها آپلود نشدند',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Multiple files upload failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'count' => count($files)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود فایل‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'directory' => 'nullable|string|max:100',
            'older_than_hours' => 'nullable|integer|min:1|max:168' // Max 1 week
        ], [
            'directory.max' => 'نام پوشه نمی‌تواند بیش از 100 کاراکتر باشد',
            'older_than_hours.min' => 'ساعت باید حداقل 1 باشد',
            'older_than_hours.max' => 'ساعت نمی‌تواند بیش از 168 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $directory = $request->get('directory', 'temp');
        $olderThanHours = $request->get('older_than_hours', 24);

        try {
            $deletedCount = $this->fileUploadService->cleanupTempFiles($directory, $olderThanHours);

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} فایل موقت حذف شد",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'directory' => $directory,
                    'older_than_hours' => $olderThanHours
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Cleanup temp files failed', [
                'error' => $e->getMessage(),
                'directory' => $directory
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پاکسازی فایل‌های موقت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upload configuration
     */
    public function getUploadConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'max_image_size' => '5MB',
                'max_audio_size' => '50MB',
                'max_document_size' => '10MB',
                'allowed_image_types' => ['jpeg', 'png', 'jpg', 'webp', 'gif'],
                'allowed_audio_types' => ['mp3', 'wav', 'm4a', 'aac', 'ogg'],
                'allowed_document_types' => ['pdf', 'doc', 'docx', 'txt'],
                'max_multiple_files' => 10,
                'supported_features' => [
                    'image_resize' => true,
                    'image_thumbnail' => true,
                    'audio_metadata' => true,
                    'file_validation' => true,
                    'multiple_upload' => true
                ]
            ]
        ]);
    }
}
