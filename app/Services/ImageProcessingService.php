<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;

class ImageProcessingService
{
    protected $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
    protected $outputFormat = 'jpg';
    protected $defaultQuality = 90;
    protected $tempPath;
    protected $watermarkPath;

    public function __construct()
    {
        $this->tempPath = storage_path('app/temp/images');
        $this->watermarkPath = public_path('assets/images/watermark.png');
        $this->ensureTempDirectory();
    }

    /**
     * Process image with various options
     */
    public function processImage(string $filePath, array $options = []): array
    {
        try {
            $originalPath = $filePath;
            $fileInfo = pathinfo($filePath);
            $originalFormat = strtolower($fileInfo['extension']);

            // Validate format
            if (!in_array($originalFormat, $this->supportedFormats)) {
                throw new \InvalidArgumentException("فرمت تصویری پشتیبانی نمی‌شود: {$originalFormat}");
            }

            $options = array_merge([
                'resize' => null,
                'quality' => $this->defaultQuality,
                'watermark' => false,
                'watermark_position' => 'bottom-right',
                'watermark_opacity' => 0.7,
                'crop' => null,
                'rotate' => 0,
                'flip' => null,
                'brightness' => 0,
                'contrast' => 0,
                'blur' => 0,
                'sharpen' => 0,
                'format' => $this->outputFormat,
                'optimize' => true,
                'progressive' => true
            ], $options);

            $result = [
                'original_file' => $filePath,
                'processed_file' => null,
                'image_info' => [],
                'processing_info' => [],
                'success' => false
            ];

            // Load image
            $image = Image::make($filePath);
            $result['image_info'] = $this->getImageInfo($image);

            // Apply processing options
            $this->applyProcessingOptions($image, $options);

            // Generate output filename
            $outputFilename = $this->generateOutputFilename($fileInfo['filename'], $options['format']);
            $outputPath = $this->tempPath . '/' . $outputFilename;

            // Save processed image
            $this->saveImage($image, $outputPath, $options);

            $result['processed_file'] = $outputPath;
            $result['processing_info'] = [
                'original_size' => filesize($filePath),
                'processed_size' => filesize($outputPath),
                'compression_ratio' => round((1 - filesize($outputPath) / filesize($filePath)) * 100, 2),
                'processing_options' => $options,
                'processing_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ];
            $result['success'] = true;

            Log::info('Image processing completed', [
                'original_file' => $filePath,
                'processed_file' => $outputPath,
                'options' => $options
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'options' => $options
            ]);

            throw $e;
        }
    }

    /**
     * Resize image
     */
    public function resizeImage(string $filePath, int $width, int $height, bool $maintainAspectRatio = true): string
    {
        return $this->processImage($filePath, [
            'resize' => [$width, $height, $maintainAspectRatio],
            'optimize' => true
        ])['processed_file'];
    }

    /**
     * Crop image
     */
    public function cropImage(string $filePath, int $x, int $y, int $width, int $height): string
    {
        return $this->processImage($filePath, [
            'crop' => [$x, $y, $width, $height],
            'optimize' => true
        ])['processed_file'];
    }

    /**
     * Add watermark
     */
    public function addWatermark(string $filePath, string $watermarkPath = null, string $position = 'bottom-right', float $opacity = 0.7): string
    {
        return $this->processImage($filePath, [
            'watermark' => true,
            'watermark_path' => $watermarkPath,
            'watermark_position' => $position,
            'watermark_opacity' => $opacity,
            'optimize' => true
        ])['processed_file'];
    }

    /**
     * Optimize image
     */
    public function optimizeImage(string $filePath, int $quality = null): string
    {
        $quality = $quality ?? $this->defaultQuality;
        
        return $this->processImage($filePath, [
            'quality' => $quality,
            'optimize' => true,
            'progressive' => true
        ])['processed_file'];
    }

    /**
     * Convert format
     */
    public function convertFormat(string $filePath, string $format): string
    {
        return $this->processImage($filePath, [
            'format' => $format,
            'optimize' => true
        ])['processed_file'];
    }

    /**
     * Generate thumbnail
     */
    public function generateThumbnail(string $filePath, int $width = 300, int $height = 300): string
    {
        return $this->processImage($filePath, [
            'resize' => [$width, $height, true],
            'quality' => 85,
            'optimize' => true,
            'format' => 'jpg'
        ])['processed_file'];
    }

    /**
     * Generate multiple sizes
     */
    public function generateMultipleSizes(string $filePath, array $sizes): array
    {
        $results = [];
        
        foreach ($sizes as $sizeName => $sizeConfig) {
            $width = $sizeConfig['width'];
            $height = $sizeConfig['height'];
            $quality = $sizeConfig['quality'] ?? 90;
            $format = $sizeConfig['format'] ?? 'jpg';
            
            $results[$sizeName] = $this->processImage($filePath, [
                'resize' => [$width, $height, true],
                'quality' => $quality,
                'format' => $format,
                'optimize' => true
            ]);
        }
        
        return $results;
    }

    /**
     * Get image information
     */
    public function getImageInfo(string $filePath): array
    {
        try {
            $image = Image::make($filePath);
            return $this->getImageInfo($image);
        } catch (\Exception $e) {
            Log::error('Failed to get image info', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Validate image file
     */
    public function validateImageFile(string $filePath): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'info' => []
        ];

        try {
            // Check if file exists
            if (!file_exists($filePath)) {
                $result['errors'][] = 'فایل وجود ندارد';
                return $result;
            }

            // Check file size
            $fileSize = filesize($filePath);
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($fileSize > $maxSize) {
                $result['errors'][] = 'حجم فایل بیش از حد مجاز است';
            }

            // Check format
            $fileInfo = pathinfo($filePath);
            $format = strtolower($fileInfo['extension']);
            if (!in_array($format, $this->supportedFormats)) {
                $result['errors'][] = 'فرمت فایل پشتیبانی نمی‌شود';
            }

            // Get image info
            $imageInfo = $this->getImageInfo($filePath);
            if (empty($imageInfo)) {
                $result['errors'][] = 'فایل تصویری معتبر نیست';
            } else {
                $result['info'] = $imageInfo;
                
                // Check dimensions
                if ($imageInfo['width'] < 100 || $imageInfo['height'] < 100) {
                    $result['warnings'][] = 'ابعاد تصویر خیلی کوچک است';
                } elseif ($imageInfo['width'] > 4000 || $imageInfo['height'] > 4000) {
                    $result['warnings'][] = 'ابعاد تصویر خیلی بزرگ است';
                }

                // Check file size vs dimensions
                $pixels = $imageInfo['width'] * $imageInfo['height'];
                $bytesPerPixel = $fileSize / $pixels;
                if ($bytesPerPixel < 0.5) {
                    $result['warnings'][] = 'کیفیت تصویر ممکن است پایین باشد';
                }
            }

            $result['valid'] = empty($result['errors']);

        } catch (\Exception $e) {
            $result['errors'][] = 'خطا در اعتبارسنجی فایل: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(array $filePaths = []): int
    {
        $cleanedCount = 0;

        if (empty($filePaths)) {
            // Clean all temp files older than 1 hour
            $files = glob($this->tempPath . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < time() - 3600) {
                    unlink($file);
                    $cleanedCount++;
                }
            }
        } else {
            foreach ($filePaths as $filePath) {
                if (file_exists($filePath) && strpos($filePath, $this->tempPath) === 0) {
                    unlink($filePath);
                    $cleanedCount++;
                }
            }
        }

        return $cleanedCount;
    }

    /**
     * Get processing statistics
     */
    public function getProcessingStats(): array
    {
        $tempFiles = glob($this->tempPath . '/*');
        $totalSize = 0;
        $fileCount = 0;

        foreach ($tempFiles as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $fileCount++;
            }
        }

        return [
            'temp_files_count' => $fileCount,
            'temp_files_size' => $totalSize,
            'temp_files_size_mb' => round($totalSize / 1024 / 1024, 2),
            'supported_formats' => $this->supportedFormats,
            'default_quality' => $this->defaultQuality,
            'output_format' => $this->outputFormat,
            'watermark_available' => file_exists($this->watermarkPath)
        ];
    }

    /**
     * Apply processing options to image
     */
    protected function applyProcessingOptions($image, array $options): void
    {
        // Resize
        if ($options['resize']) {
            list($width, $height, $maintainAspectRatio) = $options['resize'];
            if ($maintainAspectRatio) {
                $image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image->resize($width, $height);
            }
        }

        // Crop
        if ($options['crop']) {
            list($x, $y, $width, $height) = $options['crop'];
            $image->crop($width, $height, $x, $y);
        }

        // Rotate
        if ($options['rotate'] !== 0) {
            $image->rotate($options['rotate']);
        }

        // Flip
        if ($options['flip']) {
            if ($options['flip'] === 'horizontal') {
                $image->flip('h');
            } elseif ($options['flip'] === 'vertical') {
                $image->flip('v');
            }
        }

        // Brightness
        if ($options['brightness'] !== 0) {
            $image->brightness($options['brightness']);
        }

        // Contrast
        if ($options['contrast'] !== 0) {
            $image->contrast($options['contrast']);
        }

        // Blur
        if ($options['blur'] > 0) {
            $image->blur($options['blur']);
        }

        // Sharpen
        if ($options['sharpen'] > 0) {
            $image->sharpen($options['sharpen']);
        }

        // Watermark
        if ($options['watermark']) {
            $watermarkPath = $options['watermark_path'] ?? $this->watermarkPath;
            if (file_exists($watermarkPath)) {
                $this->addWatermarkToImage($image, $watermarkPath, $options['watermark_position'], $options['watermark_opacity']);
            }
        }
    }

    /**
     * Add watermark to image
     */
    protected function addWatermarkToImage($image, string $watermarkPath, string $position, float $opacity): void
    {
        $watermark = Image::make($watermarkPath);
        $watermark->opacity($opacity * 100);

        $imageWidth = $image->width();
        $imageHeight = $image->height();
        $watermarkWidth = $watermark->width();
        $watermarkHeight = $watermark->height();

        // Calculate position
        switch ($position) {
            case 'top-left':
                $x = 10;
                $y = 10;
                break;
            case 'top-right':
                $x = $imageWidth - $watermarkWidth - 10;
                $y = 10;
                break;
            case 'bottom-left':
                $x = 10;
                $y = $imageHeight - $watermarkHeight - 10;
                break;
            case 'bottom-right':
            default:
                $x = $imageWidth - $watermarkWidth - 10;
                $y = $imageHeight - $watermarkHeight - 10;
                break;
            case 'center':
                $x = ($imageWidth - $watermarkWidth) / 2;
                $y = ($imageHeight - $watermarkHeight) / 2;
                break;
        }

        $image->insert($watermark, 'top-left', $x, $y);
    }

    /**
     * Save image with options
     */
    protected function saveImage($image, string $outputPath, array $options): void
    {
        $format = $options['format'];
        $quality = $options['quality'];

        switch ($format) {
            case 'jpg':
            case 'jpeg':
                $image->encode('jpg', $quality);
                break;
            case 'png':
                $image->encode('png');
                break;
            case 'webp':
                $image->encode('webp', $quality);
                break;
            case 'gif':
                $image->encode('gif');
                break;
            default:
                $image->encode('jpg', $quality);
        }

        // Save with progressive encoding for JPEG
        if ($format === 'jpg' && $options['progressive']) {
            $image->interlace();
        }

        $image->save($outputPath);
    }

    /**
     * Get image information from Image object
     */
    protected function getImageInfo($image): array
    {
        return [
            'width' => $image->width(),
            'height' => $image->height(),
            'format' => $image->mime(),
            'size' => $image->filesize(),
            'aspect_ratio' => round($image->width() / $image->height(), 2),
            'orientation' => $image->width() > $image->height() ? 'landscape' : ($image->width() < $image->height() ? 'portrait' : 'square'),
            'colorspace' => $image->colorspace(),
            'has_alpha' => $image->hasAlpha()
        ];
    }

    /**
     * Generate output filename
     */
    protected function generateOutputFilename(string $originalFilename, string $format): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
        return $baseName . '_processed_' . $timestamp . '.' . $format;
    }

    /**
     * Ensure temp directory exists
     */
    protected function ensureTempDirectory(): void
    {
        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }
}
