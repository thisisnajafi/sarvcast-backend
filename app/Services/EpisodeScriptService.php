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
        if ($canonicalPath !== null && is_file($canonicalPath)) {
            $content = file_get_contents($canonicalPath);
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
            if (is_file($candidate)) {
                $content = file_get_contents($candidate);

                return is_string($content) ? $content : null;
            }

            if (Storage::disk('public')->exists($candidate)) {
                return Storage::disk('public')->get($candidate);
            }

            $publicCandidate = public_path($candidate);
            if (is_file($publicCandidate)) {
                $content = file_get_contents($publicCandidate);

                return is_string($content) ? $content : null;
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
        if (! is_file($metaPath)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($metaPath), true);
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
}
