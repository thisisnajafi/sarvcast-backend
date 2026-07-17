<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class MediaLibraryService
{
    /**
     * @return array<int, MediaAsset>
     */
    public function uploadMany(array $files, array $meta = []): array
    {
        $assets = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $assets[] = $this->upload($file, $meta);
            }
        }

        return $assets;
    }

    public function upload(UploadedFile $file, array $meta = []): MediaAsset
    {
        $mediaType = $this->resolveMediaType($file);
        $this->validateFile($file, $mediaType);

        $disk = (string) config('media_library.disk', 'public');
        $folder = $this->resolveFolder($meta['folder'] ?? 'general');
        $uuid = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension() ?: ($mediaType === MediaAsset::TYPE_AUDIO ? 'mp3' : 'jpg'));
        $datePath = now()->format('Y/m');
        $relativeDir = "media/{$datePath}";

        $mimeType = $file->getMimeType() ?: ($mediaType === MediaAsset::TYPE_AUDIO ? 'audio/mpeg' : 'image/jpeg');
        $sizeBytes = (int) $file->getSize();
        $width = null;
        $height = null;
        $thumbnail = ['path' => null, 'url' => null];

        if ($mediaType === MediaAsset::TYPE_IMAGE) {
            $processed = $this->storeImageAsOptimizedWebp($disk, $file, $uuid, $relativeDir, $extension, $mimeType);
            $path = $processed['path'];
            $extension = $processed['extension'];
            $mimeType = $processed['mime_type'];
            $sizeBytes = $processed['size_bytes'];
            $width = $processed['width'];
            $height = $processed['height'];
            $thumbnail = $this->generateThumbnail($disk, $path, $uuid, $relativeDir);
        } else {
            $filename = $uuid.'.'.$extension;
            $path = "{$relativeDir}/{$filename}";
            Storage::disk($disk)->putFileAs($relativeDir, $file, $filename);
        }

        $url = Storage::disk($disk)->url($path);

        return MediaAsset::create([
            'uuid' => $uuid,
            'disk' => $disk,
            'path' => $path,
            'url' => $url,
            'thumbnail_path' => $thumbnail['path'] ?? null,
            'thumbnail_url' => $thumbnail['url'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'media_type' => $mediaType,
            'size_bytes' => $sizeBytes,
            'width' => $width,
            'height' => $height,
            'duration_seconds' => null,
            'folder' => $folder,
            'alt_text' => $meta['alt_text'] ?? null,
            'title' => $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'tags' => $meta['tags'] ?? null,
            'uploaded_by' => $meta['uploaded_by'] ?? auth()->id(),
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);
    }

    public function archive(MediaAsset $asset): MediaAsset
    {
        $asset->update(['status' => MediaAsset::STATUS_ARCHIVED]);

        return $asset->fresh();
    }

    public function delete(MediaAsset $asset, bool $hard = false, bool $force = false): void
    {
        $usages = $this->getUsageReferences($asset);
        if ($usages !== [] && ! $force) {
            throw new MediaInUseException('این رسانه در حال استفاده است و قابل حذف نیست.', $usages);
        }

        if ($hard) {
            if (! in_array($asset->disk, [
                MediaLegacyImportService::LEGACY_DISK,
                MediaLegacyImportService::LEGACY_STORAGE_DISK,
                MediaLegacyImportService::STORY_EDITOR_DISK,
            ], true)) {
                $disk = $asset->disk;
                if ($asset->path && Storage::disk($disk)->exists($asset->path)) {
                    Storage::disk($disk)->delete($asset->path);
                }
                if ($asset->thumbnail_path && Storage::disk($disk)->exists($asset->thumbnail_path)) {
                    Storage::disk($disk)->delete($asset->thumbnail_path);
                }
            }
            $asset->delete();

            return;
        }

        $this->archive($asset);
    }

    public function findByUrl(string $url): ?MediaAsset
    {
        return MediaAsset::query()
            ->where('status', MediaAsset::STATUS_ACTIVE)
            ->where(function ($q) use ($url) {
                $q->where('url', $url)->orWhere('thumbnail_url', $url);
            })
            ->first();
    }

    public function syncUsageFor(Model $usable, string $field, ?string $imageUrl): void
    {
        MediaUsage::query()
            ->where('usable_type', $usable::class)
            ->where('usable_id', $usable->getKey())
            ->where('field', $field)
            ->delete();

        if ($imageUrl === null || $imageUrl === '') {
            return;
        }

        $asset = $this->findByUrl($imageUrl);
        if ($asset === null) {
            return;
        }

        MediaUsage::create([
            'media_asset_id' => $asset->id,
            'usable_type' => $usable::class,
            'usable_id' => $usable->getKey(),
            'field' => $field,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUsageReferences(MediaAsset $asset): array
    {
        return MediaUsage::query()
            ->where('media_asset_id', $asset->id)
            ->get()
            ->map(static fn (MediaUsage $usage) => [
                'usable_type' => class_basename($usage->usable_type),
                'usable_id' => $usage->usable_id,
                'field' => $usage->field,
            ])
            ->values()
            ->all();
    }

    /**
     * Store an image, converting JPEG/PNG to compressed WebP when enabled.
     *
     * @return array{path: string, extension: string, mime_type: string, size_bytes: int, width: ?int, height: ?int}
     */
    private function storeImageAsOptimizedWebp(
        string $disk,
        UploadedFile $file,
        string $uuid,
        string $relativeDir,
        string $extension,
        string $mimeType,
    ): array {
        $shouldConvert = (bool) config('media_library.convert_to_webp', true)
            && in_array($extension, ['jpg', 'jpeg', 'png'], true);

        if ($shouldConvert) {
            try {
                $manager = $this->imageManager();
                $image = $manager->read($file->getRealPath() ?: $file->getPathname());

                $maxEdge = (int) config('media_library.max_edge_px', 2560);
                if ($maxEdge > 0) {
                    $image->scaleDown(width: $maxEdge, height: $maxEdge);
                }

                $quality = max(1, min(100, (int) config('media_library.webp_quality', 82)));
                $encoded = $image->toWebp(quality: $quality);
                $binary = (string) $encoded;

                $extension = 'webp';
                $mimeType = 'image/webp';
                $filename = $uuid.'.'.$extension;
                $path = "{$relativeDir}/{$filename}";

                Storage::disk($disk)->put($path, $binary);

                return [
                    'path' => $path,
                    'extension' => $extension,
                    'mime_type' => $mimeType,
                    'size_bytes' => strlen($binary),
                    'width' => $image->width(),
                    'height' => $image->height(),
                ];
            } catch (\Throwable $e) {
                Log::warning('Media WebP conversion failed; storing original', [
                    'error' => $e->getMessage(),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        $filename = $uuid.'.'.$extension;
        $path = "{$relativeDir}/{$filename}";
        Storage::disk($disk)->putFileAs($relativeDir, $file, $filename);
        [$width, $height] = $this->readDimensions($disk, $path);

        return [
            'path' => $path,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size_bytes' => (int) $file->getSize(),
            'width' => $width,
            'height' => $height,
        ];
    }

    private function resolveMediaType(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $audioExtensions = config('media_library.allowed_audio_extensions', ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac']);

        if (in_array($ext, $audioExtensions, true) || str_starts_with((string) $file->getMimeType(), 'audio/')) {
            return MediaAsset::TYPE_AUDIO;
        }

        return MediaAsset::TYPE_IMAGE;
    }

    private function validateFile(UploadedFile $file, string $mediaType): void
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        if ($mediaType === MediaAsset::TYPE_AUDIO) {
            $allowed = config('media_library.allowed_audio_extensions', ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac']);
            $maxKb = (int) config('media_library.max_audio_upload_kb', 102400);

            if (! in_array($ext, $allowed, true)) {
                throw new \InvalidArgumentException('فرمت فایل صوتی مجاز نیست.');
            }

            if ($file->getSize() > $maxKb * 1024) {
                throw new \InvalidArgumentException('حجم فایل صوتی بیش از حد مجاز است.');
            }

            return;
        }

        $allowed = config('media_library.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $maxKb = (int) config('media_library.max_upload_kb', 5120);

        if (! in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException('فرمت تصویر مجاز نیست.');
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw new \InvalidArgumentException('حجم تصویر بیش از حد مجاز است.');
        }
    }

    private function resolveFolder(string $folder): string
    {
        $allowed = config('media_library.folders', ['general']);

        return in_array($folder, $allowed, true) ? $folder : 'general';
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function readDimensions(string $disk, string $path): array
    {
        try {
            $fullPath = Storage::disk($disk)->path($path);
            $info = @getimagesize($fullPath);
            if (is_array($info)) {
                return [$info[0] ?? null, $info[1] ?? null];
            }
        } catch (\Throwable) {
            /* ignore */
        }

        return [null, null];
    }

    /**
     * @return array{path?: string, url?: string}
     */
    private function generateThumbnail(string $disk, string $sourcePath, string $uuid, string $relativeDir): array
    {
        try {
            $manager = $this->imageManager();
            $fullPath = Storage::disk($disk)->path($sourcePath);
            $thumbName = $uuid.'_thumb.webp';
            $thumbPath = "{$relativeDir}/{$thumbName}";

            $thumbConfig = config('media_library.thumbnail', ['width' => 300, 'height' => 300, 'quality' => 80]);
            $image = $manager->read($fullPath);
            $image->scaleDown(
                width: (int) ($thumbConfig['width'] ?? 300),
                height: (int) ($thumbConfig['height'] ?? 300),
            );

            $quality = max(1, min(100, (int) ($thumbConfig['quality'] ?? 80)));
            $encoded = $image->toWebp(quality: $quality);
            Storage::disk($disk)->put($thumbPath, (string) $encoded);

            return [
                'path' => $thumbPath,
                'url' => Storage::disk($disk)->url($thumbPath),
            ];
        } catch (\Throwable $e) {
            Log::warning('Media thumbnail generation failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function imageManager(): ImageManager
    {
        if (extension_loaded('imagick')) {
            try {
                return ImageManager::imagick();
            } catch (\Throwable) {
                /* fall through to GD */
            }
        }

        return ImageManager::gd();
    }
}
