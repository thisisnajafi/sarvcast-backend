<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Models\MediaAsset;
use App\Models\Story;
use App\Models\StoryProductionAsset;
use App\Support\StoryEditorPaths;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class MediaLegacyImportService
{
    public const LEGACY_DISK = 'legacy';

    public const LEGACY_STORAGE_DISK = 'legacy-storage';

    public const STORY_EDITOR_DISK = 'story-editor';

    /**
     * @return array<string, string>
     */
    private function folderMap(): array
    {
        return config('media_library.legacy_folder_map', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(): array
    {
        $total = 0;
        $pending = 0;
        $byFolder = [];
        $bySource = [];
        $byType = ['image' => 0, 'audio' => 0, 'document' => 0];

        foreach ($this->collectCandidates() as $candidate) {
            $total++;
            $type = $candidate['media_type'];
            $byType[$type] = ($byType[$type] ?? 0) + 1;

            if ($this->alreadyImported($candidate['url'], $candidate['path'], $candidate['disk'])) {
                continue;
            }

            $pending++;
            $source = $candidate['source'];
            $bySource[$source] = ($bySource[$source] ?? 0) + 1;
            $folder = $candidate['folder'];
            $byFolder[$folder] = ($byFolder[$folder] ?? 0) + 1;
        }

        return [
            'total' => $total,
            'pending' => $pending,
            'imported' => max(0, $total - $pending),
            'by_folder' => $byFolder,
            'by_source' => $bySource,
            'by_type' => $byType,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function import(bool $dryRun = false, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $processed = 0;
        $imported = 0;
        $skipped = 0;
        $items = [];

        foreach ($this->collectCandidates() as $candidate) {
            if ($processed >= $limit) {
                break;
            }

            if ($this->alreadyImported($candidate['url'], $candidate['path'], $candidate['disk'])) {
                $skipped++;
                continue;
            }

            $processed++;

            if ($dryRun) {
                $items[] = [
                    'path' => $candidate['path'],
                    'url' => $candidate['url'],
                    'folder' => $candidate['folder'],
                    'media_type' => $candidate['media_type'],
                    'source' => $candidate['source'],
                ];
                $imported++;
                continue;
            }

            $asset = $this->registerCandidate($candidate);
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

    public function resolveAbsolutePath(MediaAsset $asset): ?string
    {
        return match ($asset->disk) {
            self::LEGACY_DISK => $this->absolutePublicPath($asset->path),
            self::LEGACY_STORAGE_DISK => $this->absoluteStoragePublicPath($asset->path),
            self::STORY_EDITOR_DISK => $this->absoluteStoryEditorPath($asset->path),
            default => $this->absolutePublicPath($asset->path),
        };
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function collectCandidates(): iterable
    {
        $seen = [];

        foreach ($this->candidatesFromPublicImages() as $candidate) {
            $key = $this->candidateKey($candidate);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            yield $candidate;
        }

        foreach ($this->candidatesFromStoragePublic() as $candidate) {
            $key = $this->candidateKey($candidate);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            yield $candidate;
        }

        foreach ($this->candidatesFromStoryEditor() as $candidate) {
            $key = $this->candidateKey($candidate);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            yield $candidate;
        }

        foreach ($this->candidatesFromDatabase() as $candidate) {
            $key = $this->candidateKey($candidate);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            yield $candidate;
        }
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function candidatesFromPublicImages(): iterable
    {
        $root = public_path('images');
        if (! is_dir($root)) {
            return;
        }

        foreach ($this->scanFiles($root, $this->imageExtensions()) as $file) {
            $relativePath = $this->relativePublicPath($file);
            yield $this->buildPublicImageCandidate($file, $relativePath, 'public_images');
        }
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function candidatesFromStoragePublic(): iterable
    {
        $root = storage_path('app/public');
        if (! is_dir($root)) {
            return;
        }

        $subdirs = config('media_library.legacy_storage_scan_dirs', ['images', 'media', 'stories', 'audio']);

        foreach ($subdirs as $subdir) {
            $scanRoot = $root . DIRECTORY_SEPARATOR . $subdir;
            if (! is_dir($scanRoot)) {
                continue;
            }

            foreach ($this->scanFiles($scanRoot, array_merge($this->imageExtensions(), $this->audioExtensions())) as $file) {
                $storageRelative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)), '/');
                $publicPath = public_path('storage/' . $storageRelative);
                if (is_file($publicPath)) {
                    continue;
                }

                $url = $this->storagePublicUrl($storageRelative);
                $mediaType = $this->mediaTypeFromExtension($file->getExtension());

                yield [
                    'source' => 'storage_public',
                    'disk' => self::LEGACY_STORAGE_DISK,
                    'absolute_path' => $file->getPathname(),
                    'path' => $storageRelative,
                    'url' => $url,
                    'folder' => $this->resolveFolderFromPath('storage/' . $storageRelative),
                    'media_type' => $mediaType,
                    'original_name' => $file->getFilename(),
                ];
            }
        }
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function candidatesFromStoryEditor(): iterable
    {
        try {
            $root = StoryEditorPaths::resolve();
        } catch (\Throwable) {
            return;
        }

        if (! is_dir($root)) {
            return;
        }

        $imageExtensions = $this->imageExtensions();
        $documentExtensions = $this->documentExtensions();
        $excludePatterns = config('story_editor.exclude_directory_patterns', []);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($this->shouldExcludeStoryEditorPath($file->getPathname(), $excludePatterns)) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            $isImage = in_array($ext, $imageExtensions, true);
            $isDocument = in_array($ext, $documentExtensions, true);

            if (! $isImage && ! $isDocument) {
                continue;
            }

            $relativePath = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)), '/');
            $mediaType = $isImage ? MediaAsset::TYPE_IMAGE : MediaAsset::TYPE_DOCUMENT;

            yield [
                'source' => 'story_editor',
                'disk' => self::STORY_EDITOR_DISK,
                'absolute_path' => $file->getPathname(),
                'path' => $relativePath,
                'url' => '',
                'folder' => $this->resolveStoryEditorFolder($relativePath),
                'media_type' => $mediaType,
                'original_name' => $file->getFilename(),
            ];
        }
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function candidatesFromDatabase(): iterable
    {
        foreach ($this->databaseImageReferences() as $reference) {
            $resolved = $this->resolveFilesystemReference($reference);
            if ($resolved === null) {
                continue;
            }

            $resolved['source'] = 'database';
            yield $resolved;
        }
    }

    /**
     * @return array<int, string>
     */
    private function databaseImageReferences(): array
    {
        $references = [];

        Story::query()
            ->select(['image_url', 'cover_image_url'])
            ->chunk(200, function ($rows) use (&$references) {
                foreach ($rows as $row) {
                    foreach (['image_url', 'cover_image_url'] as $field) {
                        $value = $row->{$field};
                        if (is_string($value) && $value !== '') {
                            $references[] = $value;
                        }
                    }
                }
            });

        Category::query()
            ->select(['icon_path'])
            ->chunk(200, function ($rows) use (&$references) {
                foreach ($rows as $row) {
                    if (is_string($row->icon_path) && $row->icon_path !== '') {
                        $references[] = $row->icon_path;
                    }
                }
            });

        Episode::query()
            ->select(['image_urls', 'audio_url'])
            ->chunk(200, function ($rows) use (&$references) {
                foreach ($rows as $row) {
                    if (is_string($row->audio_url) && $row->audio_url !== '') {
                        $references[] = $row->audio_url;
                    }
                    $urls = $row->image_urls;
                    if (is_array($urls)) {
                        foreach ($urls as $url) {
                            if (is_string($url) && $url !== '') {
                                $references[] = $url;
                            }
                        }
                    }
                }
            });

        ImageTimeline::query()
            ->select(['image_url'])
            ->chunk(200, function ($rows) use (&$references) {
                foreach ($rows as $row) {
                    if (is_string($row->image_url) && $row->image_url !== '') {
                        $references[] = $row->image_url;
                    }
                }
            });

        StoryProductionAsset::query()
            ->select(['image_url'])
            ->chunk(200, function ($rows) use (&$references) {
                foreach ($rows as $row) {
                    if (is_string($row->image_url) && $row->image_url !== '') {
                        $references[] = $row->image_url;
                    }
                }
            });

        return array_values(array_unique($references));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveFilesystemReference(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            $path = parse_url($reference, PHP_URL_PATH);
            if (! is_string($path) || $path === '') {
                return null;
            }
            $reference = ltrim($path, '/');
        }

        $reference = ltrim(str_replace('\\', '/', $reference), '/');

        if (str_starts_with($reference, 'storage/')) {
            $storageRelative = substr($reference, strlen('storage/'));
            $full = storage_path('app/public/' . $storageRelative);
            if (! is_file($full)) {
                return null;
            }

            return [
                'disk' => self::LEGACY_STORAGE_DISK,
                'absolute_path' => $full,
                'path' => $storageRelative,
                'url' => $this->storagePublicUrl($storageRelative),
                'folder' => $this->resolveFolderFromPath('storage/' . $storageRelative),
                'media_type' => $this->mediaTypeFromExtension(pathinfo($full, PATHINFO_EXTENSION)),
                'original_name' => basename($full),
            ];
        }

        if (str_starts_with($reference, 'images/')) {
            $full = public_path($reference);
            if (! is_file($full)) {
                return null;
            }

            return $this->buildPublicImageCandidate(new SplFileInfo($full), $reference, 'database');
        }

        if (str_starts_with($reference, 'audio/')) {
            $full = public_path($reference);
            if (! is_file($full)) {
                return null;
            }

            return [
                'disk' => self::LEGACY_DISK,
                'absolute_path' => $full,
                'path' => $reference,
                'url' => $this->legacyUrl($reference),
                'folder' => 'episodes',
                'media_type' => MediaAsset::TYPE_AUDIO,
                'original_name' => basename($full),
            ];
        }

        $full = public_path('images/' . $reference);
        if (is_file($full)) {
            return $this->buildPublicImageCandidate(new SplFileInfo($full), 'images/' . $reference, 'database');
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPublicImageCandidate(SplFileInfo $file, string $relativePath, string $source): array
    {
        return [
            'source' => $source,
            'disk' => self::LEGACY_DISK,
            'absolute_path' => $file->getPathname(),
            'path' => $relativePath,
            'url' => $this->legacyUrl($relativePath),
            'folder' => $this->resolveFolderFromPath($relativePath),
            'media_type' => MediaAsset::TYPE_IMAGE,
            'original_name' => $file->getFilename(),
        ];
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function registerCandidate(array $candidate): MediaAsset
    {
        $file = new SplFileInfo($candidate['absolute_path']);
        $extension = strtolower($file->getExtension() ?: 'jpg');
        $mediaType = (string) $candidate['media_type'];
        $mime = $this->mimeFromExtension($extension, $mediaType);

        $width = null;
        $height = null;
        $thumbnail = ['path' => null, 'url' => null];

        if ($mediaType === MediaAsset::TYPE_IMAGE) {
            [$width, $height] = @getimagesize($file->getPathname()) ?: [null, null];
            if ($candidate['disk'] === self::LEGACY_DISK) {
                $thumbnail = $this->generateLegacyThumbnail($file, $candidate['path']);
            }
        }

        $uuid = (string) Str::uuid();
        $url = (string) ($candidate['url'] ?? '');

        if ($url === '' && $candidate['disk'] === self::STORY_EDITOR_DISK) {
            $url = $this->streamUrl($uuid);
        }

        return MediaAsset::create([
            'uuid' => $uuid,
            'disk' => $candidate['disk'],
            'path' => $candidate['path'],
            'url' => $url,
            'thumbnail_path' => $thumbnail['path'] ?? null,
            'thumbnail_url' => $thumbnail['url'] ?? ($mediaType === MediaAsset::TYPE_IMAGE ? $url : null),
            'original_name' => $candidate['original_name'],
            'mime_type' => $mime,
            'extension' => $extension,
            'media_type' => $mediaType,
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'folder' => $candidate['folder'],
            'title' => pathinfo($candidate['original_name'], PATHINFO_FILENAME),
            'tags' => isset($candidate['source']) ? ['legacy_import', $candidate['source']] : ['legacy_import'],
            'uploaded_by' => auth()->id(),
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);
    }

    private function streamUrl(string $uuid): string
    {
        $base = rtrim((string) config('app.url'), '/');

        return $base . '/api/admin/media/' . $uuid . '/stream';
    }

    private function alreadyImported(string $url, string $relativePath, string $disk): bool
    {
        return MediaAsset::query()
            ->where(function ($q) use ($url, $relativePath, $disk) {
                if ($url !== '') {
                    $q->where('url', $url);
                }
                $q->orWhere(function ($inner) use ($relativePath, $disk) {
                    $inner->where('path', $relativePath)->where('disk', $disk);
                });
                $q->orWhere('path', $relativePath)
                    ->orWhere('path', 'images/' . ltrim(str_replace('images/', '', $relativePath), '/'));
            })
            ->exists();
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function candidateKey(array $candidate): string
    {
        return ($candidate['disk'] ?? '') . '|' . ($candidate['path'] ?? '');
    }

    private function legacyUrl(string $relativePath): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $normalized = str_replace('\\', '/', $relativePath);

        return $baseUrl . '/' . ltrim($normalized, '/');
    }

    private function storagePublicUrl(string $storageRelative): string
    {
        return $this->legacyUrl('storage/' . ltrim($storageRelative, '/'));
    }

    private function relativePublicPath(SplFileInfo $file): string
    {
        $publicRoot = str_replace('\\', '/', public_path());
        $full = str_replace('\\', '/', $file->getPathname());

        return ltrim(str_replace($publicRoot . '/', '', $full), '/');
    }

    private function absolutePublicPath(string $path): ?string
    {
        $full = public_path(ltrim(str_replace('\\', '/', $path), '/'));

        return is_file($full) ? $full : null;
    }

    private function absoluteStoragePublicPath(string $path): ?string
    {
        $full = storage_path('app/public/' . ltrim(str_replace('\\', '/', $path), '/'));

        return is_file($full) ? $full : null;
    }

    private function absoluteStoryEditorPath(string $path): ?string
    {
        try {
            $root = StoryEditorPaths::resolve();
        } catch (\Throwable) {
            return null;
        }

        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));

        return is_file($full) ? $full : null;
    }

    private function resolveFolderFromPath(string $relativePath): string
    {
        $normalized = str_replace('\\', '/', $relativePath);

        if (str_contains($normalized, 'episodes/timeline') || str_contains($normalized, '/timeline/')) {
            return 'timeline';
        }

        if (str_contains($normalized, 'characters')) {
            return 'characters';
        }

        if (str_contains($normalized, 'categories')) {
            return 'categories';
        }

        if (str_contains($normalized, 'episodes')) {
            return 'episodes';
        }

        if (str_contains($normalized, 'stories')) {
            return 'stories';
        }

        if (str_contains($normalized, 'people')) {
            return 'people';
        }

        if (str_contains($normalized, 'users')) {
            return 'users';
        }

        if (str_contains($normalized, 'audio')) {
            return 'episodes';
        }

        $parts = explode('/', $normalized);
        $subdir = $parts[1] ?? ($parts[0] ?? 'general');
        $mapped = $this->folderMap()[$subdir] ?? 'general';
        $allowed = config('media_library.folders', ['general']);

        return in_array($mapped, $allowed, true) ? $mapped : 'general';
    }

    private function resolveStoryEditorFolder(string $relativePath): string
    {
        $normalized = str_replace('\\', '/', strtolower($relativePath));

        if (str_contains($normalized, 'episode') || str_ends_with($normalized, '.md')) {
            return 'production';
        }

        if (str_ends_with($normalized, '.json')) {
            return 'production';
        }

        return 'stories';
    }

    /**
     * @param array<int, string> $excludePatterns
     */
    private function shouldExcludeStoryEditorPath(string $absolutePath, array $excludePatterns): bool
    {
        $normalized = str_replace('\\', '/', $absolutePath);

        if (str_contains($normalized, '/_backups/')) {
            return true;
        }

        $basename = basename($normalized);
        foreach ($excludePatterns as $pattern) {
            if ($basename === $pattern || stripos($basename, $pattern) !== false) {
                return true;
            }
        }

        return false;
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

    /**
     * @return array<int, string>
     */
    private function imageExtensions(): array
    {
        return config('media_library.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    /**
     * @return array<int, string>
     */
    private function audioExtensions(): array
    {
        return config('media_library.allowed_audio_extensions', ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac']);
    }

    /**
     * @return array<int, string>
     */
    private function documentExtensions(): array
    {
        return config('media_library.legacy_document_extensions', ['md', 'txt', 'json']);
    }

    private function mediaTypeFromExtension(string $extension): string
    {
        $ext = strtolower($extension);

        if (in_array($ext, $this->audioExtensions(), true)) {
            return MediaAsset::TYPE_AUDIO;
        }

        if (in_array($ext, $this->documentExtensions(), true)) {
            return MediaAsset::TYPE_DOCUMENT;
        }

        return MediaAsset::TYPE_IMAGE;
    }

    private function mimeFromExtension(string $extension, string $mediaType): string
    {
        if ($mediaType === MediaAsset::TYPE_AUDIO) {
            return match (strtolower($extension)) {
                'wav' => 'audio/wav',
                'm4a' => 'audio/mp4',
                'aac' => 'audio/aac',
                'ogg' => 'audio/ogg',
                'flac' => 'audio/flac',
                default => 'audio/mpeg',
            };
        }

        if ($mediaType === MediaAsset::TYPE_DOCUMENT) {
            return match (strtolower($extension)) {
                'json' => 'application/json',
                'txt' => 'text/plain',
                default => 'text/markdown',
            };
        }

        return match (strtolower($extension)) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * @param array<int, string> $allowedExtensions
     * @return iterable<SplFileInfo>
     */
    private function scanFiles(string $root, array $allowedExtensions): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (in_array($ext, $allowedExtensions, true)) {
                yield $file;
            }
        }
    }
}
