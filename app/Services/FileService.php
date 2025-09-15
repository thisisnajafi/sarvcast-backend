<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class FileService
{
    /**
     * Upload file to storage
     */
    public function uploadFile(UploadedFile $file, string $directory = 'uploads', string $disk = null): array
    {
        $disk = $disk ?: $this->getDefaultDisk();
        
        // Generate unique filename
        $filename = $this->generateFilename($file);
        
        // Store file
        $path = $file->storeAs($directory, $filename, $disk);
        
        // Get URL
        $url = $this->getFileUrl($path, $disk);
        
        return [
            'path' => $path,
            'url' => $url,
            'filename' => $filename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'disk' => $disk
        ];
    }

    /**
     * Upload and optimize image
     */
    public function uploadImage(UploadedFile $file, string $directory = 'images', array $sizes = [], string $disk = null): array
    {
        $disk = $disk ?: $this->getDefaultDisk();
        
        // Generate unique filename
        $filename = $this->generateFilename($file);
        $originalPath = $file->storeAs($directory, $filename, $disk);
        
        $result = [
            'original' => [
                'path' => $originalPath,
                'url' => $this->getFileUrl($originalPath, $disk),
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'disk' => $disk
            ],
            'variants' => []
        ];

        // Create image variants if sizes are specified
        if (!empty($sizes)) {
            foreach ($sizes as $sizeName => $sizeConfig) {
                $variantPath = $this->createImageVariant($file, $directory, $filename, $sizeConfig, $disk);
                if ($variantPath) {
                    $result['variants'][$sizeName] = [
                        'path' => $variantPath,
                        'url' => $this->getFileUrl($variantPath, $disk),
                        'filename' => $this->getVariantFilename($filename, $sizeName),
                        'width' => $sizeConfig['width'] ?? null,
                        'height' => $sizeConfig['height'] ?? null,
                        'disk' => $disk
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Upload audio file
     */
    public function uploadAudio(UploadedFile $file, string $directory = 'audio', string $disk = null): array
    {
        $disk = $disk ?: $this->getDefaultDisk();
        
        // Generate unique filename
        $filename = $this->generateFilename($file);
        
        // Store file
        $path = $file->storeAs($directory, $filename, $disk);
        
        // Get URL
        $url = $this->getFileUrl($path, $disk);
        
        return [
            'path' => $path,
            'url' => $url,
            'filename' => $filename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'duration' => $this->getAudioDuration($file),
            'disk' => $disk
        ];
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $path, string $disk = null): bool
    {
        $disk = $disk ?: $this->getDefaultDisk();
        
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        
        return false;
    }

    /**
     * Delete multiple files
     */
    public function deleteFiles(array $paths, string $disk = null): array
    {
        $results = [];
        
        foreach ($paths as $path) {
            $results[$path] = $this->deleteFile($path, $disk);
        }
        
        return $results;
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $path, string $disk = null): string
    {
        $disk = $disk ?: $this->getDefaultDisk();
        
        if ($disk === 's3') {
            return Storage::disk($disk)->url($path);
        }
        
        return Storage::disk($disk)->url($path);
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = Str::slug($name);
        
        return $name . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Create image variant
     */
    private function createImageVariant(UploadedFile $file, string $directory, string $filename, array $config, string $disk): ?string
    {
        try {
            $image = Image::make($file);
            
            $width = $config['width'] ?? null;
            $height = $config['height'] ?? null;
            $fit = $config['fit'] ?? 'contain';
            
            if ($width && $height) {
                switch ($fit) {
                    case 'cover':
                        $image->fit($width, $height);
                        break;
                    case 'contain':
                        $image->resize($width, $height, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        break;
                    case 'fill':
                        $image->resize($width, $height);
                        break;
                }
            } elseif ($width) {
                $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } elseif ($height) {
                $image->resize(null, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Apply quality if specified
            if (isset($config['quality'])) {
                $image->encode(null, $config['quality']);
            }
            
            // Generate variant filename
            $variantFilename = $this->getVariantFilename($filename, $config['name'] ?? 'variant');
            $variantPath = $directory . '/' . $variantFilename;
            
            // Store variant
            Storage::disk($disk)->put($variantPath, $image->stream());
            
            return $variantPath;
        } catch (\Exception $e) {
            \Log::error('Failed to create image variant: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get variant filename
     */
    private function getVariantFilename(string $filename, string $variantName): string
    {
        $pathInfo = pathinfo($filename);
        return $pathInfo['filename'] . '_' . $variantName . '.' . $pathInfo['extension'];
    }

    /**
     * Get audio duration
     */
    private function getAudioDuration(UploadedFile $file): ?int
    {
        try {
            // This would require ffmpeg or similar library
            // For now, return null
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get default disk
     */
    private function getDefaultDisk(): string
    {
        return config('filesystems.default', 'local');
    }

    /**
     * Get S3 disk
     */
    public function getS3Disk(): string
    {
        return 's3';
    }

    /**
     * Get public disk
     */
    public function getPublicDisk(): string
    {
        return 'public';
    }

    /**
     * Check if S3 is configured
     */
    public function isS3Configured(): bool
    {
        return !empty(config('filesystems.disks.s3.key')) && 
               !empty(config('filesystems.disks.s3.secret')) && 
               !empty(config('filesystems.disks.s3.bucket'));
    }

    /**
     * Get file size in human readable format
     */
    public function getHumanReadableSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validate file type
     */
    public function validateFileType(UploadedFile $file, array $allowedTypes): bool
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        foreach ($allowedTypes as $type) {
            if ($mimeType === $type || $extension === $type) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $path, string $disk = null): array
    {
        $disk = $disk ?: $this->getDefaultDisk();
        
        if (!Storage::disk($disk)->exists($path)) {
            return [];
        }
        
        $size = Storage::disk($disk)->size($path);
        $mimeType = Storage::disk($disk)->mimeType($path);
        $lastModified = Storage::disk($disk)->lastModified($path);
        
        return [
            'path' => $path,
            'url' => $this->getFileUrl($path, $disk),
            'size' => $size,
            'human_size' => $this->getHumanReadableSize($size),
            'mime_type' => $mimeType,
            'last_modified' => date('Y-m-d H:i:s', $lastModified),
            'disk' => $disk
        ];
    }
}
