<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $this->validateImage($file);

        $disk = (string) config('media_library.disk', 'public');
        $folder = $this->resolveFolder($meta['folder'] ?? 'general');
        $uuid = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $datePath = now()->format('Y/m');
        $filename = $uuid . '.' . $extension;
        $relativeDir = "media/{$datePath}";
        $path = "{$relativeDir}/{$filename}";

        Storage::disk($disk)->putFileAs($relativeDir, $file, $filename);

        [$width, $height] = $this->readDimensions($disk, $path);
        $thumbnail = $this->generateThumbnail($disk, $path, $uuid, $relativeDir, $extension);

        $url = Storage::disk($disk)->url($path);

        return MediaAsset::create([
            'uuid' => $uuid,
            'disk' => $disk,
            'path' => $path,
            'url' => $url,
            'thumbnail_path' => $thumbnail['path'] ?? null,
            'thumbnail_url' => $thumbnail['url'] ?? $url,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'extension' => $extension,
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
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
            if ($asset->disk !== MediaLegacyImportService::LEGACY_DISK) {
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

    private function validateImage(UploadedFile $file): void
    {
        $allowed = config('media_library.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
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
    private function generateThumbnail(string $disk, string $sourcePath, string $uuid, string $relativeDir, string $extension): array
    {
        try {
            if (! class_exists(\Intervention\Image\Facades\Image::class)) {
                return [];
            }

            $fullPath = Storage::disk($disk)->path($sourcePath);
            $thumbName = $uuid . '_thumb.webp';
            $thumbPath = "{$relativeDir}/{$thumbName}";
            $thumbFullPath = Storage::disk($disk)->path($thumbPath);

            $thumbConfig = config('media_library.thumbnail', ['width' => 300, 'height' => 300, 'quality' => 80]);
            $image = \Intervention\Image\Facades\Image::make($fullPath);
            $image->resize(
                (int) ($thumbConfig['width'] ?? 300),
                (int) ($thumbConfig['height'] ?? 300),
                function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
            );
            $image->encode('webp', (int) ($thumbConfig['quality'] ?? 80));
            $image->save($thumbFullPath);

            return [
                'path' => $thumbPath,
                'url' => Storage::disk($disk)->url($thumbPath),
            ];
        } catch (\Throwable $e) {
            Log::warning('Media thumbnail generation failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
