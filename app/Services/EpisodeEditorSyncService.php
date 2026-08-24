<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Story;
use App\Models\StoryProductionFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EpisodeEditorSyncService
{
    public function __construct(
        private readonly StoryEditorRepository $editor,
    ) {}

    /**
     * @param  list<array{id: int, episode_number: int}>  $ordered
     */
    public function reorder(int $storyId, array $ordered): void
    {
        $story = Story::query()->find($storyId);
        if (! $story) {
            return;
        }

        $targetNumbers = [];
        foreach ($ordered as $row) {
            $id = (int) ($row['id'] ?? 0);
            $number = (int) ($row['episode_number'] ?? 0);
            if ($id > 0 && $number > 0) {
                $targetNumbers[$id] = $number;
            }
        }

        $episodes = Episode::query()
            ->where('story_id', $storyId)
            ->whereIn('id', array_keys($targetNumbers))
            ->get()
            ->keyBy('id');

        $storySlug = $this->editor->findLinkedStorySlug($storyId);
        $moves = [];
        if ($storySlug !== null) {
            foreach ($episodes as $episode) {
                $dir = $this->resolveEditorDirectory($episode, $storySlug);
                if ($dir === null) {
                    continue;
                }
                $moves[] = [
                    'episode' => $episode,
                    'folder_name' => $dir['folder_name'],
                    'to_number' => $targetNumbers[$episode->id],
                ];
            }
        }

        $tempPrefix = '__tmp_reorder_'.uniqid('', true).'_';
        $renamed = [];

        try {
            foreach ($moves as $index => $move) {
                $renamed[$index] = $this->editor->moveEpisodeDirectoryToTemp(
                    $storySlug,
                    $move['folder_name'],
                    $tempPrefix,
                );
            }

            foreach ($moves as $index => $move) {
                $result = $this->editor->renameEpisodeDirectory(
                    $storySlug,
                    $renamed[$index],
                    $move['to_number'],
                    $move['folder_name'],
                );
                $this->updateProductionSlug(
                    $move['episode'],
                    $storySlug,
                    $result['id'],
                    $result['path'],
                    $move['to_number'],
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to reorder story-editor episode folders', [
                'story_id' => $storyId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        DB::transaction(function () use ($storyId, $targetNumbers, $episodes) {
            foreach ($episodes as $episode) {
                Episode::query()
                    ->where('id', $episode->id)
                    ->where('story_id', $storyId)
                    ->update(['episode_number' => 100000 + $episode->id]);
            }

            foreach ($targetNumbers as $id => $number) {
                Episode::query()
                    ->where('id', $id)
                    ->where('story_id', $storyId)
                    ->update(['episode_number' => $number]);
            }
        });
    }

    public function deleteEditorEpisode(Episode $episode): void
    {
        $storySlug = $this->editor->findLinkedStorySlug((int) $episode->story_id);
        if ($storySlug === null) {
            return;
        }

        $dir = $this->resolveEditorDirectory($episode, $storySlug);
        if ($dir === null) {
            return;
        }

        $this->editor->deleteEpisodeDirectory($storySlug, $dir['folder_name']);
    }

    public function ensureEpisodeScaffold(Episode $episode): void
    {
        $storySlug = $this->editor->findLinkedStorySlug((int) $episode->story_id)
            ?? $this->editor->findStorySlugByDbStoryId((int) $episode->story_id);

        if ($storySlug === null) {
            return;
        }

        try {
            $scaffold = $this->editor->createEpisodeScaffold(
                $storySlug,
                (int) $episode->episode_number,
                (string) $episode->title,
            );
        } catch (\Throwable $e) {
            Log::warning('Could not scaffold story-editor episode folder', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        try {
            $md = $this->editor->findEpisodeDirBySlug($storySlug, $scaffold['id']);
            $this->updateProductionSlug(
                $episode,
                $storySlug,
                $scaffold['id'],
                $md['file_path'] ?? $scaffold['path'],
                (int) $episode->episode_number,
            );
        } catch (\Throwable $e) {
            Log::warning('Could not link story-editor production file after scaffold', [
                'episode_id' => $episode->id,
                'story_slug' => $storySlug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function syncNumberOrTitle(Episode $episode, int $oldNumber, string $oldTitle): void
    {
        $newNumber = (int) $episode->episode_number;
        $newTitle = (string) $episode->title;
        if ($newNumber === $oldNumber && $newTitle === $oldTitle) {
            return;
        }

        $storySlug = $this->editor->findLinkedStorySlug((int) $episode->story_id);
        if ($storySlug === null) {
            return;
        }

        $snapshot = $episode->replicate();
        $snapshot->id = $episode->id;
        $snapshot->episode_number = $oldNumber;
        $snapshot->title = $oldTitle;

        $dir = $this->resolveEditorDirectory($snapshot, $storySlug);
        if ($dir === null) {
            $this->ensureEpisodeScaffold($episode);

            return;
        }

        if ($newNumber !== $oldNumber) {
            $temp = $this->editor->moveEpisodeDirectoryToTemp($storySlug, $dir['folder_name'], '__tmp_renum_'.uniqid('', true).'_');
            $result = $this->editor->renameEpisodeDirectory($storySlug, $temp, $newNumber, $dir['folder_name']);
            $this->updateProductionSlug(
                $episode,
                $storySlug,
                $result['id'],
                $result['path'],
                $newNumber,
            );

            return;
        }

        $md = $dir['file_path'] ?? null;
        if (is_string($md) && is_file($md)) {
            $raw = file_get_contents($md);
            if (is_string($raw)) {
                file_put_contents(
                    $md,
                    app(StoryMarkdownService::class)->rewriteEpisodeHeading($raw, $newNumber, $newTitle),
                );
            }
        }
    }

    public function duplicateEditorEpisode(Episode $source, Episode $copy): void
    {
        $storySlug = $this->editor->findLinkedStorySlug((int) $source->story_id);
        if ($storySlug === null) {
            return;
        }

        $dir = $this->resolveEditorDirectory($source, $storySlug);
        if ($dir === null) {
            $this->ensureEpisodeScaffold($copy);

            return;
        }

        try {
            $copied = $this->editor->copyEpisodeDirectory(
                $storySlug,
                $dir['folder_name'],
                (int) $copy->episode_number,
                (string) $copy->title,
            );
            $this->updateProductionSlug(
                $copy,
                $storySlug,
                $copied['id'],
                $copied['path'],
                (int) $copy->episode_number,
            );
        } catch (\Throwable $e) {
            Log::warning('Could not copy story-editor episode folder', [
                'source_episode_id' => $source->id,
                'copy_episode_id' => $copy->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{id: string, folder_name: string, path: string, file_path: string|null, episode_number: int}|null
     */
    private function resolveEditorDirectory(Episode $episode, string $storySlug): ?array
    {
        $slug = StoryProductionFile::query()
            ->where('episode_id', $episode->id)
            ->where('file_type', StoryProductionFile::TYPE_STORY_SCRIPT)
            ->whereNotNull('episode_slug')
            ->value('episode_slug');

        if (is_string($slug) && $slug !== '') {
            $bySlug = $this->editor->findEpisodeDirBySlug($storySlug, $slug);
            if ($bySlug !== null) {
                return $bySlug;
            }
        }

        return $this->editor->findEpisodeDirByNumber($storySlug, (int) $episode->episode_number);
    }

    private function updateProductionSlug(
        Episode $episode,
        string $storySlug,
        string $episodeSlug,
        string $sourcePath,
        int $episodeNumber,
    ): void {
        $mdPath = is_file($sourcePath) ? $sourcePath : null;
        if ($mdPath === null && is_dir($sourcePath)) {
            foreach (glob($sourcePath.DIRECTORY_SEPARATOR.'*.md') ?: [] as $file) {
                $mdPath = $file;
                break;
            }
        }

        $relativeSource = $this->relativizeEditorPath($mdPath);

        $existing = StoryProductionFile::query()
            ->where('episode_id', $episode->id)
            ->where('file_type', StoryProductionFile::TYPE_STORY_SCRIPT)
            ->first();

        $storagePath = $existing?->storage_path
            ?: ($relativeSource ?: 'stories/production/_pending.md');

        $payload = [
            'story_slug' => $storySlug,
            'episode_slug' => $episodeSlug,
            'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
            'story_id' => $episode->story_id,
            'episode_id' => $episode->id,
            'episode_number' => $episodeNumber,
            'source_path' => $relativeSource,
            'original_filename' => $mdPath ? basename($mdPath) : 'episode.md',
            'storage_path' => \Illuminate\Support\Str::limit((string) $storagePath, 500, ''),
        ];

        if ($existing) {
            $existing->update($payload);

            return;
        }

        StoryProductionFile::updateOrCreate(
            [
                'story_slug' => $storySlug,
                'episode_slug' => $episodeSlug,
                'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
            ],
            $payload,
        );
    }

    private function relativizeEditorPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        try {
            $root = \App\Support\StoryEditorPaths::resolve();
            $normalizedPath = str_replace('\\', '/', realpath($path) ?: $path);
            $normalizedRoot = rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/');

            if (str_starts_with($normalizedPath, $normalizedRoot.'/')) {
                return substr($normalizedPath, strlen($normalizedRoot) + 1);
            }
        } catch (\Throwable $e) {
            // Fall through to truncated absolute path.
        }

        return \Illuminate\Support\Str::limit(str_replace('\\', '/', $path), 500, '');
    }
}
