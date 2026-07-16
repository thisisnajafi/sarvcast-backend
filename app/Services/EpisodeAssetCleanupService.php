<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Models\MediaUsage;
use App\Models\StoryProductionAsset;
use App\Models\StoryProductionFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EpisodeAssetCleanupService
{
    public function __construct(
        private readonly MediaLibraryService $mediaLibrary,
    ) {}

    /**
     * Remove all filesystem artifacts tied to an episode (called before DB delete).
     */
    public function cleanupEpisodeAssets(Episode $episode): void
    {
        $attributes = $episode->getAttributes();

        $this->deleteScriptFile($attributes['script_file_url'] ?? null);
        $this->deleteAudioFile($attributes['audio_url'] ?? null);
        $this->deleteCoverImage($attributes['cover_image_url'] ?? null);
        $this->deleteImageUrlList($attributes['image_urls'] ?? null);
        $this->deleteTimelineAssets($episode->id);
        $this->deleteProductionArtifacts($episode);
        $this->releaseMediaUsages($episode);
    }

    /**
     * Remove only the episode script and related production records.
     */
    public function deleteEpisodeScript(Episode $episode): bool
    {
        $hadScript = ! empty($episode->getAttributes()['script_file_url'] ?? null);

        $this->deleteScriptFile($episode->getAttributes()['script_file_url'] ?? null);

        StoryProductionFile::query()
            ->where('episode_id', $episode->id)
            ->where('file_type', StoryProductionFile::TYPE_STORY_SCRIPT)
            ->get()
            ->each(fn (StoryProductionFile $file) => $this->deleteProductionFileRecord($file));

        if ($hadScript) {
            $episode->forceFill(['script_file_url' => null])->save();
        }

        return $hadScript;
    }

    public function deleteScriptFile(?string $pathOrUrl): void
    {
        $this->deleteStoredPath($pathOrUrl);
    }

    public function deleteAudioFile(?string $pathOrUrl): void
    {
        $this->deleteStoredPath($pathOrUrl);
    }

    public function deleteCoverImage(?string $pathOrUrl): void
    {
        if (! $pathOrUrl) {
            return;
        }

        $this->deleteStoredPath($pathOrUrl);
        $this->deleteStoredPath('images/' . ltrim($pathOrUrl, '/'));
    }

    public function deleteImageUrlList(mixed $imageUrls): void
    {
        if ($imageUrls === null || $imageUrls === '') {
            return;
        }

        if (is_string($imageUrls)) {
            $decoded = json_decode($imageUrls, true);
            $imageUrls = json_last_error() === JSON_ERROR_NONE ? $decoded : [$imageUrls];
        }

        if (! is_array($imageUrls)) {
            return;
        }

        foreach ($imageUrls as $url) {
            if (is_string($url) && $url !== '') {
                $this->deleteStoredPath($url);
            }
        }
    }

    public function deleteTimelineAssets(int $episodeId): void
    {
        $timelines = ImageTimeline::forEpisode($episodeId)->get();

        foreach ($timelines as $timeline) {
            $this->deleteStoredPath($timeline->getAttributes()['image_url'] ?? null);
            $this->deleteMediaAssetByUrl($timeline->getAttributes()['image_url'] ?? null, $timeline->id, ImageTimeline::class);
        }

        Cache::forget("episode_timeline_{$episodeId}");
        Cache::forget("episode_timeline_{$episodeId}_with_voice_actors");
    }

    public function deleteProductionArtifacts(Episode $episode): void
    {
        StoryProductionFile::query()
            ->where('episode_id', $episode->id)
            ->get()
            ->each(fn (StoryProductionFile $file) => $this->deleteProductionFileRecord($file));

        StoryProductionAsset::query()
            ->where('episode_id', $episode->id)
            ->get()
            ->each(function (StoryProductionAsset $asset) {
                $this->deleteStoredPath($asset->storage_path);
                $this->deleteStoredPath($asset->getAttributes()['image_url'] ?? null);
                $this->deleteMediaAssetByUrl($asset->getAttributes()['image_url'] ?? null, $asset->id, StoryProductionAsset::class);
                $asset->delete();
            });
    }

    private function deleteProductionFileRecord(StoryProductionFile $file): void
    {
        if ($file->storage_path) {
            $this->deleteStoredPath($file->storage_path);
        }

        if ($file->source_path) {
            $this->safeUnlink($file->source_path);
        }

        $file->delete();
    }

    private function releaseMediaUsages(Episode $episode): void
    {
        MediaUsage::query()
            ->where('usable_type', Episode::class)
            ->where('usable_id', $episode->id)
            ->delete();
    }

    private function deleteMediaAssetByUrl(?string $url, int $usableId, string $usableType): void
    {
        if (! $url) {
            return;
        }

        $asset = $this->mediaLibrary->findByUrl($url);
        if ($asset === null) {
            return;
        }

        $otherUsages = MediaUsage::query()
            ->where('media_asset_id', $asset->id)
            ->where(function ($query) use ($usableId, $usableType) {
                $query->where('usable_type', '!=', $usableType)
                    ->orWhere('usable_id', '!=', $usableId);
            })
            ->exists();

        if ($otherUsages) {
            return;
        }

        try {
            $this->mediaLibrary->delete($asset, hard: true, force: true);
        } catch (\Throwable $e) {
            Log::warning('Failed to delete media asset during episode cleanup', [
                'media_asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteStoredPath(?string $pathOrUrl): void
    {
        if (! $pathOrUrl) {
            return;
        }

        foreach ($this->resolveFilesystemCandidates($pathOrUrl) as $candidate) {
            // Prefer Storage disk for public URL paths so we never hit open_basedir
            // with fake absolute paths like "/storage/stories/...".
            if ($this->deleteFromPublicDisk($candidate)) {
                return;
            }

            if ($this->safeUnlink($candidate)) {
                return;
            }

            if ($this->safeUnlink(public_path(ltrim($candidate, '/\\')))) {
                return;
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveFilesystemCandidates(string $pathOrUrl): array
    {
        $candidates = [];
        $trimmed = trim($pathOrUrl);

        if ($trimmed === '') {
            return [];
        }

        $candidates[] = $trimmed;

        $parsedPath = parse_url($trimmed, PHP_URL_PATH);
        if (is_string($parsedPath) && $parsedPath !== '') {
            $candidates[] = $parsedPath;
            $candidates[] = ltrim($parsedPath, '/');

            if (str_starts_with($parsedPath, '/storage/')) {
                $candidates[] = ltrim(substr($parsedPath, strlen('/storage/')), '/');
            }
        }

        if (str_starts_with($trimmed, '/storage/')) {
            $candidates[] = ltrim(substr($trimmed, strlen('/storage/')), '/');
        }

        if (str_starts_with($trimmed, 'storage/')) {
            $candidates[] = ltrim(substr($trimmed, strlen('storage/')), '/');
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function deleteFromPublicDisk(string $relativePath): bool
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if (str_starts_with($relativePath, 'storage/')) {
            $relativePath = substr($relativePath, strlen('storage/'));
        }

        if ($relativePath === '' || str_contains($relativePath, '://')) {
            return false;
        }

        // Absolute OS paths are handled by safeUnlink, not the public disk.
        if ($this->isAbsoluteFilesystemPath($relativePath)) {
            return false;
        }

        try {
            if (Storage::disk('public')->exists($relativePath)) {
                return Storage::disk('public')->delete($relativePath);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to delete file from public disk', [
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    private function safeUnlink(string $path): bool
    {
        if (! $this->isSafeLocalFilesystemPath($path)) {
            return false;
        }

        try {
            if (! is_file($path)) {
                return false;
            }

            return unlink($path);
        } catch (\Throwable $e) {
            Log::warning('Failed to unlink file during episode cleanup', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Avoid is_file()/unlink() on web paths like "/storage/..." which PHP treats as
     * filesystem-root absolute paths and can violate open_basedir on shared hosting.
     */
    private function isSafeLocalFilesystemPath(string $path): bool
    {
        if ($path === '' || str_contains($path, '://')) {
            return false;
        }

        if (! $this->isAbsoluteFilesystemPath($path)) {
            return false;
        }

        $normalized = str_replace('\\', '/', $path);
        $roots = [
            str_replace('\\', '/', storage_path()),
            str_replace('\\', '/', public_path()),
            str_replace('\\', '/', base_path()),
        ];

        foreach ($roots as $root) {
            $root = rtrim($root, '/');
            if ($normalized === $root || str_starts_with($normalized, $root . '/')) {
                return true;
            }
        }

        return false;
    }

    private function isAbsoluteFilesystemPath(string $path): bool
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return true;
        }

        return str_starts_with($path, '/') || str_starts_with($path, '\\');
    }
}
