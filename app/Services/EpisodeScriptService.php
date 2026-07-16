<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Story;
use App\Models\StoryProductionFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EpisodeScriptService
{
    public function __construct(
        private readonly StoryEditorRepository $editorRepository,
        private readonly EpisodeAssetCleanupService $assetCleanup,
    ) {}

    /**
     * @return array{script_content: string, script_file_url: string|null, source: string}|null
     */
    public function readForEpisode(Episode $episode): ?array
    {
        $canonicalPath = $this->resolveCanonicalMarkdownPath($episode);
        if ($canonicalPath !== null) {
            $content = $this->readLocalFileIfAllowed($canonicalPath);
            if (is_string($content) && $content !== '') {
                return [
                    'script_content' => $content,
                    'script_file_url' => $episode->script_file_url,
                    'source' => 'story_editor',
                ];
            }
        }

        $scriptFileUrl = $episode->getAttributes()['script_file_url'] ?? null;
        if (! is_string($scriptFileUrl) || $scriptFileUrl === '') {
            return null;
        }

        $content = $this->readFromStoredPath($scriptFileUrl);
        if ($content === null) {
            return null;
        }

        return [
            'script_content' => $content,
            'script_file_url' => $scriptFileUrl,
            'source' => 'uploaded',
        ];
    }

    /**
     * Publish story-editor markdown to the episode's public script file.
     */
    public function syncFromStoryEditor(string $storySlug, string $episodeSlug, string $markdown): void
    {
        $editorEpisode = $this->editorRepository->getEpisode($storySlug, $episodeSlug);
        $episode = $this->resolveDbEpisode($storySlug, $episodeSlug);
        if ($episode === null) {
            return;
        }

        $this->publishScriptContent(
            $episode,
            $markdown,
            $storySlug,
            $episodeSlug,
            $editorEpisode['file_path'] ?? null,
        );
    }

    public function publishScriptContent(
        Episode $episode,
        string $content,
        ?string $storySlug = null,
        ?string $episodeSlug = null,
        ?string $sourcePath = null,
    ): void {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $filename = time() . '_' . $episode->id . '_episode_' . $episode->episode_number . '.md';
        $path = 'episodes/scripts/' . $filename;

        Storage::disk('public')->put($path, $normalized);
        $url = Storage::url($path);

        $previousUrl = $episode->getAttributes()['script_file_url'] ?? null;
        if (is_string($previousUrl) && $previousUrl !== '' && $previousUrl !== $url) {
            $this->assetCleanup->deleteScriptFile($previousUrl);
        }

        $episode->forceFill(['script_file_url' => $url])->save();

        if ($storySlug !== null && $episodeSlug !== null) {
            StoryProductionFile::updateOrCreate(
                [
                    'story_slug' => $storySlug,
                    'episode_slug' => $episodeSlug,
                    'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
                ],
                [
                    'original_filename' => basename($path),
                    'storage_path' => $path,
                    'source_path' => $sourcePath,
                    'story_id' => $episode->story_id,
                    'episode_id' => $episode->id,
                    'episode_number' => $episode->episode_number,
                    'content_hash' => hash('sha256', $normalized),
                    'imported_at' => now(),
                ],
            );
        }
    }

    public function readFromStoredPath(string $pathOrUrl): ?string
    {
        foreach ($this->resolveFilesystemCandidates($pathOrUrl) as $candidate) {
            $diskRelative = $this->toPublicDiskRelative($candidate);
            if ($diskRelative !== null && Storage::disk('public')->exists($diskRelative)) {
                return Storage::disk('public')->get($diskRelative);
            }

            $content = $this->readLocalFileIfAllowed($candidate);
            if ($content !== null) {
                return $content;
            }

            $content = $this->readLocalFileIfAllowed(public_path(ltrim($candidate, '/\\')));
            if ($content !== null) {
                return $content;
            }
        }

        Log::warning('Episode script file not found', [
            'path_or_url' => $pathOrUrl,
        ]);

        return null;
    }

    public function resolveCanonicalMarkdownPath(Episode $episode): ?string
    {
        $storySlug = StoryProductionFile::query()
            ->where('story_id', $episode->story_id)
            ->whereNotNull('story_slug')
            ->value('story_slug');

        if (! is_string($storySlug) || $storySlug === '') {
            $storySlug = $this->editorRepository->findStorySlugByDbStoryId($episode->story_id);
        }

        if (! is_string($storySlug) || $storySlug === '') {
            return null;
        }

        $storyDir = $this->editorRepository->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            return null;
        }

        foreach ($this->editorRepository->listEpisodes($storySlug) as $editorEpisode) {
            if ((int) $editorEpisode['episode_number'] !== (int) $episode->episode_number) {
                continue;
            }

            $data = $this->editorRepository->getEpisode($storySlug, $editorEpisode['id']);

            return $data['file_path'] ?? null;
        }

        return null;
    }

    private function resolveDbEpisode(string $storySlug, string $episodeSlug): ?Episode
    {
        $linkedEpisodeId = StoryProductionFile::query()
            ->where('story_slug', $storySlug)
            ->where('episode_slug', $episodeSlug)
            ->whereNotNull('episode_id')
            ->value('episode_id');

        if ($linkedEpisodeId) {
            return Episode::query()->find((int) $linkedEpisodeId);
        }

        $editorEpisode = $this->editorRepository->getEpisode($storySlug, $episodeSlug);
        if ($editorEpisode === null) {
            return null;
        }

        $storyId = StoryProductionFile::query()
            ->where('story_slug', $storySlug)
            ->whereNotNull('story_id')
            ->value('story_id');

        if (! $storyId) {
            $storyId = $this->resolveStoryIdFromSlug($storySlug);
        }

        if (! $storyId) {
            return null;
        }

        $episodeNumber = (int) ($editorEpisode['episode']['metadata']['episode_number'] ?? 0);
        if ($episodeNumber > 0) {
            $episode = Episode::query()
                ->where('story_id', $storyId)
                ->where('episode_number', $episodeNumber)
                ->first();

            if ($episode) {
                return $episode;
            }
        }

        $title = trim((string) ($editorEpisode['episode']['metadata']['title_persian'] ?? ''));
        if ($title !== '') {
            return Episode::query()
                ->where('story_id', $storyId)
                ->where('title', $title)
                ->first();
        }

        return null;
    }

    private function resolveStoryIdFromSlug(string $storySlug): ?int
    {
        $storyDir = $this->editorRepository->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            return null;
        }

        $metaPath = $storyDir . '/characters_and_objects.json';
        $metaContents = $this->readLocalFileIfAllowed($metaPath);
        if ($metaContents === null) {
            return null;
        }

        $json = json_decode($metaContents, true);
        if (! is_array($json)) {
            return null;
        }

        $title = trim((string) ($json['story_title'] ?? ''));
        if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/u', $title, $matches)) {
            $title = trim($matches[1]);
        }

        if ($title === '') {
            return null;
        }

        return Story::query()->where('title', $title)->value('id');
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

    private function toPublicDiskRelative(string $path): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $path), '/');

        if ($relative === '' || str_contains($relative, '://')) {
            return null;
        }

        if ($this->isAbsoluteFilesystemPath($relative)) {
            return null;
        }

        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        return $relative !== '' ? $relative : null;
    }

    private function readLocalFileIfAllowed(string $path): ?string
    {
        if (! $this->isSafeLocalFilesystemPath($path)) {
            return null;
        }

        try {
            if (! is_file($path)) {
                return null;
            }

            $content = file_get_contents($path);

            return is_string($content) ? $content : null;
        } catch (\Throwable $e) {
            Log::warning('Failed to read local episode script file', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

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
