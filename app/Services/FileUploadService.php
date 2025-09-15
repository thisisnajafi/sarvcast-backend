<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Exception;
use Log;

class FileUploadService
{
    protected $allowedImageTypes = ['jpeg', 'jpg', 'png', 'webp', 'gif'];
    protected $allowedAudioTypes = ['mp3', 'wav', 'aac', 'm4a', 'ogg'];
    protected $allowedDocumentTypes = ['pdf', 'doc', 'docx', 'txt'];
    
    protected $maxImageSize = 5 * 1024 * 1024; // 5MB
    protected $maxAudioSize = 50 * 1024 * 1024; // 50MB
    protected $maxDocumentSize = 10 * 1024 * 1024; // 10MB

    /**
     * Upload and process an image file
     */
    public function uploadImage(UploadedFile $file, string $directory = 'images', array $options = []): array
    {
        try {
            // Validate image file
            $validation = $this->validateImageFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, $options['prefix'] ?? 'img');
            $path = $directory . '/' . $filename;

            // Store original file
            $storedPath = $file->storeAs($directory, $filename, 'public');

            // Process image if options are provided
            if (!empty($options['resize']) || !empty($options['quality'])) {
                $this->processImage($storedPath, $options);
            }

            // Generate thumbnail if requested
            if (!empty($options['thumbnail'])) {
                $thumbnailPath = $this->generateThumbnail($storedPath, $options['thumbnail']);
            }

            return [
                'success' => true,
                'path' => $storedPath,
                'url' => Storage::url($storedPath),
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'thumbnail_url' => isset($thumbnailPath) ? Storage::url($thumbnailPath) : null
            ];

        } catch (Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در آپلود تصویر: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload an audio file
     */
    public function uploadAudio(UploadedFile $file, string $directory = 'audio', array $options = []): array
    {
        try {
            // Validate audio file
            $validation = $this->validateAudioFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, $options['prefix'] ?? 'audio');
            $path = $directory . '/' . $filename;

            // Store file
            $storedPath = $file->storeAs($directory, $filename, 'public');

            // Extract metadata if requested
            $metadata = [];
            if (!empty($options['extract_metadata'])) {
                $metadata = $this->extractAudioMetadata($storedPath);
            }

            return [
                'success' => true,
                'path' => $storedPath,
                'url' => Storage::url($storedPath),
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'metadata' => $metadata
            ];

        } catch (Exception $e) {
            Log::error('Audio upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در آپلود فایل صوتی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload a document file
     */
    public function uploadDocument(UploadedFile $file, string $directory = 'documents', array $options = []): array
    {
        try {
            // Validate document file
            $validation = $this->validateDocumentFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, $options['prefix'] ?? 'doc');
            $path = $directory . '/' . $filename;

            // Store file
            $storedPath = $file->storeAs($directory, $filename, 'public');

            return [
                'success' => true,
                'path' => $storedPath,
                'url' => Storage::url($storedPath),
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (Exception $e) {
            Log::error('Document upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در آپلود سند: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files, string $type = 'image', string $directory = 'uploads', array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($files as $file) {
            switch ($type) {
                case 'image':
                    $result = $this->uploadImage($file, $directory, $options);
                    break;
                case 'audio':
                    $result = $this->uploadAudio($file, $directory, $options);
                    break;
                case 'document':
                    $result = $this->uploadDocument($file, $directory, $options);
                    break;
                default:
                    $result = ['success' => false, 'message' => 'نوع فایل نامعتبر'];
            }

            $results[] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        return [
            'success' => $errorCount === 0,
            'results' => $results,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_count' => count($files)
        ];
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return true;
            }
            return false;
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            return false;
        }
    }

    /**
     * Delete multiple files
     */
    public function deleteMultiple(array $paths): array
    {
        $results = [];
        foreach ($paths as $path) {
            $results[$path] = $this->deleteFile($path);
        }
        return $results;
    }

    /**
     * Validate image file
     */
    protected function validateImageFile(UploadedFile $file): array
    {
        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'image',
                'mimes:' . implode(',', $this->allowedImageTypes),
                'max:' . ($this->maxImageSize / 1024) // Convert to KB
            ]
        ], [
            'file.required' => 'فایل الزامی است',
            'file.file' => 'فایل باید یک فایل معتبر باشد',
            'file.image' => 'فایل باید یک تصویر باشد',
            'file.mimes' => 'فرمت تصویر باید یکی از موارد زیر باشد: ' . implode(', ', $this->allowedImageTypes),
            'file.max' => 'حجم تصویر نمی‌تواند بیش از ' . ($this->maxImageSize / 1024 / 1024) . ' مگابایت باشد'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'خطا در اعتبارسنجی فایل',
                'errors' => $validator->errors()
            ];
        }

        return ['success' => true];
    }

    /**
     * Validate audio file
     */
    protected function validateAudioFile(UploadedFile $file): array
    {
        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'mimes:' . implode(',', $this->allowedAudioTypes),
                'max:' . ($this->maxAudioSize / 1024) // Convert to KB
            ]
        ], [
            'file.required' => 'فایل الزامی است',
            'file.file' => 'فایل باید یک فایل معتبر باشد',
            'file.mimes' => 'فرمت فایل صوتی باید یکی از موارد زیر باشد: ' . implode(', ', $this->allowedAudioTypes),
            'file.max' => 'حجم فایل صوتی نمی‌تواند بیش از ' . ($this->maxAudioSize / 1024 / 1024) . ' مگابایت باشد'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'خطا در اعتبارسنجی فایل',
                'errors' => $validator->errors()
            ];
        }

        return ['success' => true];
    }

    /**
     * Validate document file
     */
    protected function validateDocumentFile(UploadedFile $file): array
    {
        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'mimes:' . implode(',', $this->allowedDocumentTypes),
                'max:' . ($this->maxDocumentSize / 1024) // Convert to KB
            ]
        ], [
            'file.required' => 'فایل الزامی است',
            'file.file' => 'فایل باید یک فایل معتبر باشد',
            'file.mimes' => 'فرمت سند باید یکی از موارد زیر باشد: ' . implode(', ', $this->allowedDocumentTypes),
            'file.max' => 'حجم سند نمی‌تواند بیش از ' . ($this->maxDocumentSize / 1024 / 1024) . ' مگابایت باشد'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'خطا در اعتبارسنجی فایل',
                'errors' => $validator->errors()
            ];
        }

        return ['success' => true];
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file, string $prefix = 'file'): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);
        
        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Process image (resize, quality, etc.)
     */
    protected function processImage(string $path, array $options): void
    {
        $fullPath = Storage::disk('public')->path($path);
        
        $image = Image::make($fullPath);

        // Resize if specified
        if (!empty($options['resize'])) {
            $resizeOptions = $options['resize'];
            if (isset($resizeOptions['width']) && isset($resizeOptions['height'])) {
                $image->resize($resizeOptions['width'], $resizeOptions['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
        }

        // Adjust quality if specified
        $quality = $options['quality'] ?? 90;
        $image->save($fullPath, $quality);
    }

    /**
     * Generate thumbnail
     */
    protected function generateThumbnail(string $originalPath, array $options): string
    {
        $fullPath = Storage::disk('public')->path($originalPath);
        $thumbnailPath = str_replace('.', '_thumb.', $originalPath);
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

        $image = Image::make($fullPath);
        
        $width = $options['width'] ?? 300;
        $height = $options['height'] ?? 300;
        
        $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $image->save($thumbnailFullPath, $options['quality'] ?? 80);

        return $thumbnailPath;
    }

    /**
     * Extract audio metadata
     */
    protected function extractAudioMetadata(string $path): array
    {
        // This would typically use a library like getID3 or similar
        // For now, return basic info
        $fullPath = Storage::disk('public')->path($path);
        
        return [
            'duration' => null, // Would be extracted from audio file
            'bitrate' => null,
            'sample_rate' => null,
            'channels' => null,
            'format' => pathinfo($fullPath, PATHINFO_EXTENSION)
        ];
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $path): array
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                return [
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ];
            }

            $fullPath = Storage::disk('public')->path($path);
            $url = Storage::url($path);

            return [
                'success' => true,
                'path' => $path,
                'url' => $url,
                'size' => Storage::disk('public')->size($path),
                'mime_type' => Storage::disk('public')->mimeType($path),
                'last_modified' => Storage::disk('public')->lastModified($path),
                'exists' => true
            ];

        } catch (Exception $e) {
            Log::error('Get file info failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات فایل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(string $directory = 'temp', int $olderThanHours = 24): int
    {
        try {
            $files = Storage::disk('public')->files($directory);
            $deletedCount = 0;
            $cutoffTime = now()->subHours($olderThanHours)->timestamp;

            foreach ($files as $file) {
                $lastModified = Storage::disk('public')->lastModified($file);
                if ($lastModified < $cutoffTime) {
                    Storage::disk('public')->delete($file);
                    $deletedCount++;
                }
            }

            return $deletedCount;

        } catch (Exception $e) {
            Log::error('Cleanup temp files failed', [
                'error' => $e->getMessage(),
                'directory' => $directory
            ]);

            return 0;
        }
    }
}
