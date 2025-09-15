<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageProcessingController extends Controller
{
    protected $imageProcessingService;

    public function __construct(ImageProcessingService $imageProcessingService)
    {
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Process image file
     */
    public function processImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'resize' => 'nullable|string', // "width,height,maintain_aspect_ratio"
            'quality' => 'nullable|integer|min:1|max:100',
            'watermark' => 'nullable|boolean',
            'watermark_position' => 'nullable|string|in:top-left,top-right,bottom-left,bottom-right,center',
            'watermark_opacity' => 'nullable|numeric|min:0|max:1',
            'crop' => 'nullable|string', // "x,y,width,height"
            'rotate' => 'nullable|integer|min:-360|max:360',
            'flip' => 'nullable|string|in:horizontal,vertical',
            'brightness' => 'nullable|integer|min:-100|max:100',
            'contrast' => 'nullable|integer|min:-100|max:100',
            'blur' => 'nullable|integer|min:0|max:100',
            'sharpen' => 'nullable|integer|min:0|max:100',
            'format' => 'nullable|string|in:jpg,jpeg,png,webp,gif',
            'optimize' => 'nullable|boolean',
            'progressive' => 'nullable|boolean'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'file_path.string' => 'مسیر فایل باید رشته باشد',
            'quality.min' => 'کیفیت نباید کمتر از 1 باشد',
            'quality.max' => 'کیفیت نباید بیشتر از 100 باشد',
            'watermark_position.in' => 'موقعیت واترمارک نامعتبر است',
            'watermark_opacity.min' => 'شفافیت واترمارک نمی‌تواند کمتر از 0 باشد',
            'watermark_opacity.max' => 'شفافیت واترمارک نمی‌تواند بیشتر از 1 باشد',
            'rotate.min' => 'زاویه چرخش نمی‌تواند کمتر از -360 باشد',
            'rotate.max' => 'زاویه چرخش نمی‌تواند بیشتر از 360 باشد',
            'flip.in' => 'جهت برگرداندن نامعتبر است',
            'brightness.min' => 'روشنایی نمی‌تواند کمتر از -100 باشد',
            'brightness.max' => 'روشنایی نمی‌تواند بیشتر از 100 باشد',
            'contrast.min' => 'کنتراست نمی‌تواند کمتر از -100 باشد',
            'contrast.max' => 'کنتراست نمی‌تواند بیشتر از 100 باشد',
            'blur.min' => 'تاری نمی‌تواند کمتر از 0 باشد',
            'blur.max' => 'تاری نمی‌تواند بیشتر از 100 باشد',
            'sharpen.min' => 'تیز کردن نمی‌تواند کمتر از 0 باشد',
            'sharpen.max' => 'تیز کردن نمی‌تواند بیشتر از 100 باشد',
            'format.in' => 'فرمت نامعتبر است'
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
            $options = $this->parseProcessingOptions($request);

            $result = $this->imageProcessingService->processImage($fullPath, $options);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در پردازش تصویر'
                ], 500);
            }

            // Move processed file to storage
            $processedPath = $this->moveProcessedFile($result['processed_file'], $filePath, $options['format']);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'processed_file' => $processedPath,
                    'image_info' => $result['image_info'],
                    'processing_info' => $result['processing_info']
                ],
                'message' => 'تصویر با موفقیت پردازش شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پردازش تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resize image
     */
    public function resizeImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'width' => 'required|integer|min:1|max:4000',
            'height' => 'required|integer|min:1|max:4000',
            'maintain_aspect_ratio' => 'nullable|boolean'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'width.required' => 'عرض الزامی است',
            'width.min' => 'عرض نمی‌تواند کمتر از 1 باشد',
            'width.max' => 'عرض نمی‌تواند بیشتر از 4000 باشد',
            'height.required' => 'ارتفاع الزامی است',
            'height.min' => 'ارتفاع نمی‌تواند کمتر از 1 باشد',
            'height.max' => 'ارتفاع نمی‌تواند بیشتر از 4000 باشد'
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
            $width = $request->input('width');
            $height = $request->input('height');
            $maintainAspectRatio = $request->input('maintain_aspect_ratio', true);
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->imageProcessingService->resizeImage($fullPath, $width, $height, $maintainAspectRatio);

            // Move processed file to storage
            $resizedPath = $this->moveProcessedFile($processedPath, $filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'resized_file' => $resizedPath,
                    'width' => $width,
                    'height' => $height,
                    'maintain_aspect_ratio' => $maintainAspectRatio
                ],
                'message' => 'تغییر اندازه تصویر با موفقیت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Image resize failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در تغییر اندازه تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crop image
     */
    public function cropImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'x.required' => 'موقعیت X الزامی است',
            'x.min' => 'موقعیت X نمی‌تواند منفی باشد',
            'y.required' => 'موقعیت Y الزامی است',
            'y.min' => 'موقعیت Y نمی‌تواند منفی باشد',
            'width.required' => 'عرض الزامی است',
            'width.min' => 'عرض نمی‌تواند کمتر از 1 باشد',
            'height.required' => 'ارتفاع الزامی است',
            'height.min' => 'ارتفاع نمی‌تواند کمتر از 1 باشد'
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
            $x = $request->input('x');
            $y = $request->input('y');
            $width = $request->input('width');
            $height = $request->input('height');
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->imageProcessingService->cropImage($fullPath, $x, $y, $width, $height);

            // Move processed file to storage
            $croppedPath = $this->moveProcessedFile($processedPath, $filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'cropped_file' => $croppedPath,
                    'crop_area' => [
                        'x' => $x,
                        'y' => $y,
                        'width' => $width,
                        'height' => $height
                    ]
                ],
                'message' => 'برش تصویر با موفقیت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Image crop failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در برش تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add watermark
     */
    public function addWatermark(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'watermark_path' => 'nullable|string',
            'position' => 'nullable|string|in:top-left,top-right,bottom-left,bottom-right,center',
            'opacity' => 'nullable|numeric|min:0|max:1'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'position.in' => 'موقعیت واترمارک نامعتبر است',
            'opacity.min' => 'شفافیت نمی‌تواند کمتر از 0 باشد',
            'opacity.max' => 'شفافیت نمی‌تواند بیشتر از 1 باشد'
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
            $watermarkPath = $request->input('watermark_path');
            $position = $request->input('position', 'bottom-right');
            $opacity = $request->input('opacity', 0.7);
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->imageProcessingService->addWatermark($fullPath, $watermarkPath, $position, $opacity);

            // Move processed file to storage
            $watermarkedPath = $this->moveProcessedFile($processedPath, $filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'watermarked_file' => $watermarkedPath,
                    'watermark_position' => $position,
                    'watermark_opacity' => $opacity
                ],
                'message' => 'واترمارک با موفقیت اضافه شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Watermark addition failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در اضافه کردن واترمارک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize image
     */
    public function optimizeImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'quality' => 'nullable|integer|min:1|max:100'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'quality.min' => 'کیفیت نمی‌تواند کمتر از 1 باشد',
            'quality.max' => 'کیفیت نمی‌تواند بیشتر از 100 باشد'
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
            $quality = $request->input('quality', 90);
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->imageProcessingService->optimizeImage($fullPath, $quality);

            // Move processed file to storage
            $optimizedPath = $this->moveProcessedFile($processedPath, $filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'optimized_file' => $optimizedPath,
                    'quality' => $quality
                ],
                'message' => 'بهینه‌سازی تصویر با موفقیت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Image optimization failed', [
                'error' => $e->getMessage(),
                'file_path' => $request->input('file_path')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بهینه‌سازی تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate thumbnail
     */
    public function generateThumbnail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'width' => 'nullable|integer|min:50|max:1000',
            'height' => 'nullable|integer|min:50|max:1000'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'width.min' => 'عرض نمی‌تواند کمتر از 50 باشد',
            'width.max' => 'عرض نمی‌تواند بیشتر از 1000 باشد',
            'height.min' => 'ارتفاع نمی‌تواند کمتر از 50 باشد',
            'height.max' => 'ارتفاع نمی‌تواند بیشتر از 1000 باشد'
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
            $width = $request->input('width', 300);
            $height = $request->input('height', 300);
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $processedPath = $this->imageProcessingService->generateThumbnail($fullPath, $width, $height);

            // Move processed file to storage
            $thumbnailPath = $this->moveProcessedFile($processedPath, $filePath, 'jpg');

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'thumbnail_file' => $thumbnailPath,
                    'thumbnail_size' => [
                        'width' => $width,
                        'height' => $height
                    ]
                ],
                'message' => 'تصویر بندانگشتی با موفقیت ایجاد شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد تصویر بندانگشتی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate multiple sizes
     */
    public function generateMultipleSizes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'sizes' => 'required|array',
            'sizes.*.width' => 'required|integer|min:50|max:2000',
            'sizes.*.height' => 'required|integer|min:50|max:2000',
            'sizes.*.quality' => 'nullable|integer|min:1|max:100',
            'sizes.*.format' => 'nullable|string|in:jpg,jpeg,png,webp'
        ], [
            'file_path.required' => 'مسیر فایل الزامی است',
            'sizes.required' => 'اندازه‌ها الزامی است',
            'sizes.array' => 'اندازه‌ها باید آرایه باشد',
            'sizes.*.width.required' => 'عرض هر اندازه الزامی است',
            'sizes.*.height.required' => 'ارتفاع هر اندازه الزامی است'
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
            $sizes = $request->input('sizes');
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل یافت نشد'
                ], 404);
            }

            $fullPath = Storage::path($filePath);
            $results = $this->imageProcessingService->generateMultipleSizes($fullPath, $sizes);

            $processedFiles = [];
            foreach ($results as $sizeName => $result) {
                $processedPath = $this->moveProcessedFile($result['processed_file'], $filePath, $sizes[$sizeName]['format'] ?? 'jpg');
                $processedFiles[$sizeName] = $processedPath;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'original_file' => $filePath,
                    'processed_files' => $processedFiles,
                    'sizes' => $sizes
                ],
                'message' => 'اندازه‌های مختلف با موفقیت ایجاد شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Multiple sizes generation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد اندازه‌های مختلف: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image information
     */
    public function getImageInfo(Request $request): JsonResponse
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
            $imageInfo = $this->imageProcessingService->getImageInfo($fullPath);

            return response()->json([
                'success' => true,
                'data' => [
                    'file_path' => $filePath,
                    'image_info' => $imageInfo
                ],
                'message' => 'اطلاعات تصویر دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Image info retrieval failed', [
                'error' => $e->getMessage(),
                'file_path' => $request->input('file_path')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate image file
     */
    public function validateImage(Request $request): JsonResponse
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
            $validation = $this->imageProcessingService->validateImageFile($fullPath);

            return response()->json([
                'success' => true,
                'data' => $validation,
                'message' => $validation['valid'] ? 'تصویر معتبر است' : 'تصویر نامعتبر است'
            ]);

        } catch (\Exception $e) {
            Log::error('Image validation failed', [
                'error' => $e->getMessage(),
                'file_path' => $request->input('file_path')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get processing statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->imageProcessingService->getProcessingStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار پردازش تصویر دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get image processing stats', [
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
            $cleanedCount = $this->imageProcessingService->cleanupTempFiles($filePaths);

            return response()->json([
                'success' => true,
                'data' => [
                    'cleaned_files' => $cleanedCount
                ],
                'message' => "{$cleanedCount} فایل موقت پاک شد"
            ]);

        } catch (\Exception $e) {
            Log::error('Image cleanup failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پاک‌سازی فایل‌های موقت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse processing options from request
     */
    protected function parseProcessingOptions(Request $request): array
    {
        $options = [];

        // Parse resize
        if ($request->has('resize')) {
            $resizeParts = explode(',', $request->input('resize'));
            if (count($resizeParts) >= 2) {
                $options['resize'] = [
                    (int)$resizeParts[0],
                    (int)$resizeParts[1],
                    isset($resizeParts[2]) ? (bool)$resizeParts[2] : true
                ];
            }
        }

        // Parse crop
        if ($request->has('crop')) {
            $cropParts = explode(',', $request->input('crop'));
            if (count($cropParts) >= 4) {
                $options['crop'] = [
                    (int)$cropParts[0],
                    (int)$cropParts[1],
                    (int)$cropParts[2],
                    (int)$cropParts[3]
                ];
            }
        }

        // Other options
        $options['quality'] = $request->input('quality', 90);
        $options['watermark'] = $request->input('watermark', false);
        $options['watermark_position'] = $request->input('watermark_position', 'bottom-right');
        $options['watermark_opacity'] = $request->input('watermark_opacity', 0.7);
        $options['rotate'] = $request->input('rotate', 0);
        $options['flip'] = $request->input('flip');
        $options['brightness'] = $request->input('brightness', 0);
        $options['contrast'] = $request->input('contrast', 0);
        $options['blur'] = $request->input('blur', 0);
        $options['sharpen'] = $request->input('sharpen', 0);
        $options['format'] = $request->input('format', 'jpg');
        $options['optimize'] = $request->input('optimize', true);
        $options['progressive'] = $request->input('progressive', true);

        return $options;
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