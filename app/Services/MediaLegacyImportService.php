<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class MediaLegacyImportService
{
    public const LEGACY_DISK = 'legacy';

    /**
     * @return array<string, string>
     */
    private function folderMap(): array
    {
        return config('media_library.legacy_folder_map', [
            'categories' => 'categories',
            'stories' => 'stories',
            'episodes' => 'episodes',
            'people' => 'people',
            'users' => 'users',
            'timeline' => 'timeline',
            'playlists' => 'general',
            'characters' => 'characters',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(): array
    {
        $root = public_path('images');
        if (! is_dir($root)) {
            return [
                'total' => 0,
                'pending' => 0,
                'imported' => 0,
                'by_folder' => [],
            ];
        }

        $total = 0;
        $pending = 0;
        $byFolder = [];

        foreach ($this->scanImageFiles($root) as $file) {
            $total++;
            $relativePath = $this->relativePublicPath($file);
            $url = $this->legacyUrl($relativePath);

            if ($this->alreadyImported($url, $relativePath)) {
                continue;
            }

            $pending++;
            $folder = $this->resolveFolderFromPath($relativePath);
            $byFolder[$folder] = ($byFolder[$folder] ?? 0) + 1;
        }

        return [
            'total' => $total,
            'pending' => $pending,
            'imported' => max(0, $total - $pending),
            'by_folder' => $byFolder,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function import(bool $dryRun = false, int $limit = 100): array
    {
        $root = public_path('images');
        if (! is_dir($root)) {
            return [
                'dry_run' => $dryRun,
                'processed' => 0,
                'imported' => 0,
                'skipped' => 0,
                'items' => [],
            ];
        }

        $limit = max(1, min($limit, 500));
        $processed = 0;
        $imported = 0;
        $skipped = 0;
        $items = [];

        foreach ($this->scanImageFiles($root) as $file) {
            if ($processed >= $limit) {
                break;
            }

            $relativePath = $this->relativePublicPath($file);
            $url = $this->legacyUrl($relativePath);

            if ($this->alreadyImported($url, $relativePath)) {
                $skipped++;
                continue;
            }

            $processed++;

            if ($dryRun) {
                $items[] = [
                    'path' => $relativePath,
                    'url' => $url,
                    'folder' => $this->resolveFolderFromPath($relativePath),
                ];
                $imported++;
                continue;
            }

            $asset = $this->registerLegacyFile($file, $relativePath, $url);
            $items[] = $asset->toApiArray();
            $imported++;
        }

        return [
            'dry_run' => $dryRun,
            'processed' => $processed,
            'imported' => $imported,
            'skipped' => $skipped,
            'items' => $items,
        ];
    }

    private function registerLegacyFile(SplFileInfo $file, string $relativePath, string $url): MediaAsset
    {
        $extension = strtolower($file->getExtension() ?: 'jpg');
        $mime = match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        [$width, $height] = @getimagesize($file->getPathname()) ?: [null, null];
        $thumbnail = $this->generateLegacyThumbnail($file, $relativePath);

        return MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'disk' => self::LEGACY_DISK,
            'path' => $relativePath,
            'url' => $url,
            'thumbnail_path' => $thumbnail['path'] ?? null,
            'thumbnail_url' => $thumbnail['url'] ?? $url,
            'original_name' => $file->getFilename(),
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'folder' => $this->resolveFolderFromPath($relativePath),
            'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
            'uploaded_by' => auth()->id(),
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);
    }

    /**
     * @return array{path?: string, url?: string}
     */
    private function generateLegacyThumbnail(SplFileInfo $file, string $relativePath): array
    {
        try {
            if (! class_exists(\Intervention\Image\Facades\Image::class)) {
                return [];
            }

            $disk = (string) config('media_library.disk', 'public');
            $thumbDir = 'media/legacy-thumbs/' . dirname(str_replace('\\', '/', $relativePath));
            $thumbName = pathinfo($file->getFilename(), PATHINFO_FILENAME) . '_thumb.webp';
            $thumbPath = trim($thumbDir . '/' . $thumbName, '/');

            Storage::disk($disk)->makeDirectory($thumbDir);

            $thumbConfig = config('media_library.thumbnail', ['width' => 300, 'height' => 300, 'quality' => 80]);
            $image = \Intervention\Image\Facades\Image::make($file->getPathname());
            $image->resize(
                (int) ($thumbConfig['width'] ?? 300),
                (int) ($thumbConfig['height'] ?? 300),
                function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
            );
            $image->encode('webp', (int) ($thumbConfig['quality'] ?? 80));
            $image->save(Storage::disk($disk)->path($thumbPath));

            return [
                'path' => $thumbPath,
                'url' => Storage::disk($disk)->url($thumbPath),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    private function alreadyImported(string $url, string $relativePath): bool
    {
        return MediaAsset::query()
            ->where(function ($q) use ($url, $relativePath) {
                $q->where('url', $url)
                    ->orWhere('path', $relativePath)
                    ->orWhere('path', 'images/' . ltrim(str_replace('images/', '', $relativePath), '/'));
            })
            ->exists();
    }

    private function legacyUrl(string $relativePath): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $normalized = str_replace('\\', '/', $relativePath);

        return $baseUrl . '/' . ltrim($normalized, '/');
    }

    private function relativePublicPath(SplFileInfo $file): string
    {
        $publicRoot = str_replace('\\', '/', public_path());
        $full = str_replace('\\', '/', $file->getPathname());

        return ltrim(str_replace($publicRoot . '/', '', $full), '/');
    }

    private function resolveFolderFromPath(string $relativePath): string
    {
        $parts = explode('/', str_replace('\\', '/', $relativePath));
        $subdir = $parts[1] ?? 'general';
        $mapped = $this->folderMap()[$subdir] ?? 'general';
        $allowed = config('media_library.folders', ['general']);

        return in_array($mapped, $allowed, true) ? $mapped : 'general';
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function scanImageFiles(string $root): iterable
    {
        $allowed = config('media_library.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (in_array($ext, $allowed, true)) {
                yield $file;
            }
        }
    }
}
