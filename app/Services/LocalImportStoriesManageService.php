<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Story;
use Illuminate\Support\Facades\File;

class LocalImportStoriesManageService
{
    public function __construct(
        private readonly StoryEditorRepository $editorRepository,
        private readonly StoryDeletionService $storyDeletion,
        private readonly StoryProductionImportService $productionImport,
        private readonly EpisodeAssetCleanupService $assetCleanup,
        private readonly OldStoriesImportService $oldStoriesImport,
    ) {}

    /**
     * @return array{
     *   story: ?Story,
     *   story_id: ?int,
     *   story_slug: string,
     *   story_dir: string,
     *   folder_name: string,
     * }
     */
    public function resolveStory(?int $storyId = null, ?string $folderName = null, ?string $storySlug = null): array
    {
        $story = null;
        $storyDir = null;
        $resolvedSlug = null;
        $resolvedFolder = null;

        if ($storyId !== null && $storyId > 0) {
            $story = Story::query()->find($storyId);
            if ($story === null) {
                throw new \RuntimeException("Story not found for id {$storyId}.");
            }

            $resolvedSlug = $this->editorRepository->findStorySlugByDbStoryId($storyId);
            if ($resolvedSlug !== null) {
                $storyDir = $this->editorRepository->findStoryDirectory($resolvedSlug);
            }
        }

        if ($storyDir === null && is_string($folderName) && trim($folderName) !== '') {
            $storyDir = $this->findStoryDirectoryByFolderQuery(trim($folderName));
        }

        if ($storyDir === null && is_string($storySlug) && trim($storySlug) !== '') {
            $storyDir = $this->editorRepository->findStoryDirectory(trim($storySlug));
        }

        if ($storyDir === null) {
            throw new \RuntimeException('Story folder not found on server (use story_id, folder_name, or story_slug).');
        }

        $resolvedFolder = basename($storyDir);
        $resolvedSlug = $resolvedSlug ?? $this->editorRepository->storyIdFromFolder($resolvedFolder);

        if ($story === null) {
            $story = $this->findStoryByEditorFolder($resolvedFolder, $resolvedSlug);
        }

        return [
            'story' => $story,
            'story_id' => $story?->id,
            'story_slug' => $resolvedSlug,
            'story_dir' => $storyDir,
            'folder_name' => $resolvedFolder,
        ];
    }

    /**
     * @return array{
     *   episode: ?Episode,
     *   episode_id: ?int,
     *   episode_slug: string,
     *   episode_dir: string,
     *   episode_folder: string,
     *   episode_number: int,
     *   story: array<string, mixed>,
     * }
     */
    public function resolveEpisode(
        ?int $episodeId = null,
        ?int $storyId = null,
        ?string $folderName = null,
        ?string $storySlug = null,
        ?int $episodeNumber = null,
        ?string $episodeSlug = null,
    ): array {
        $storyContext = $this->resolveStory($storyId, $folderName, $storySlug);
        $episode = null;
        $episodeDir = null;
        $episodeFolder = null;
        $resolvedEpisodeSlug = null;
        $resolvedNumber = 0;

        if ($episodeId !== null && $episodeId > 0) {
            $episode = Episode::query()->find($episodeId);
            if ($episode === null) {
                throw new \RuntimeException("Episode not found for id {$episodeId}.");
            }

            $resolvedNumber = (int) $episode->episode_number;
            $found = $this->findEpisodeDirInStory($storyContext['story_dir'], $resolvedNumber, $episodeSlug);
            if ($found !== null) {
                $episodeDir = $found['episode_dir'];
                $episodeFolder = $found['episode_folder'];
                $resolvedEpisodeSlug = $found['episode_slug'];
            }
        }

        if ($episodeDir === null && is_string($episodeSlug) && trim($episodeSlug) !== '') {
            $resolvedEpisodeSlug = trim($episodeSlug);
            $episodeDir = $this->editorRepository->findEpisodeDirectory(
                $storyContext['story_dir'],
                $resolvedEpisodeSlug,
            );
            if ($episodeDir !== null) {
                $episodeFolder = basename($episodeDir);
            }
        }

        if ($episodeDir === null && $episodeNumber !== null && $episodeNumber > 0) {
            $resolvedNumber = $episodeNumber;
            $found = $this->findEpisodeDirInStory($storyContext['story_dir'], $episodeNumber, null);
            if ($found !== null) {
                $episodeDir = $found['episode_dir'];
                $episodeFolder = $found['episode_folder'];
                $resolvedEpisodeSlug = $found['episode_slug'];
            }
        }

        if ($episodeDir === null) {
            throw new \RuntimeException('Episode folder not found (use episode_id or episode_number / episode_slug with story).');
        }

        $episodeFolder = $episodeFolder ?? basename($episodeDir);
        $resolvedEpisodeSlug = $resolvedEpisodeSlug ?? $this->editorRepository->episodeIdFromFolder($episodeFolder);

        if ($resolvedNumber <= 0) {
            if (preg_match('/episode[_\s-]*(\d+)/i', $episodeFolder, $m)) {
                $resolvedNumber = (int) $m[1];
            }
        }

        if ($episode === null && $storyContext['story_id'] !== null && $resolvedNumber > 0) {
            $episode = Episode::query()
                ->where('story_id', $storyContext['story_id'])
                ->where('episode_number', $resolvedNumber)
                ->first();
        }

        return [
            'episode' => $episode,
            'episode_id' => $episode?->id,
            'episode_slug' => $resolvedEpisodeSlug,
            'episode_dir' => $episodeDir,
            'episode_folder' => $episodeFolder,
            'episode_number' => $resolvedNumber,
            'story' => $storyContext,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteStory(?int $storyId = null, ?string $folderName = null, ?string $storySlug = null): array
    {
        $context = $this->resolveStory($storyId, $folderName, $storySlug);

        if ($context['story'] instanceof Story) {
            $deletedId = (int) $context['story']->id;
            $this->storyDeletion->delete($context['story']);

            return [
                'action' => 'delete_story',
                'story_id' => $deletedId,
                'folder_name' => $context['folder_name'],
                'story_slug' => $context['story_slug'],
                'mode' => 'full_db_and_files',
            ];
        }

        $this->editorRepository->deleteStoryDirectory($context['story_slug']);

        return [
            'action' => 'delete_story',
            'story_id' => null,
            'folder_name' => $context['folder_name'],
            'story_slug' => $context['story_slug'],
            'mode' => 'filesystem_only',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteEpisode(
        ?int $episodeId = null,
        ?int $storyId = null,
        ?string $folderName = null,
        ?string $storySlug = null,
        ?int $episodeNumber = null,
        ?string $episodeSlug = null,
    ): array {
        $context = $this->resolveEpisode(
            $episodeId,
            $storyId,
            $folderName,
            $storySlug,
            $episodeNumber,
            $episodeSlug,
        );

        if ($context['episode'] instanceof Episode) {
            $deletedId = (int) $context['episode']->id;
            $context['episode']->delete();

            return [
                'action' => 'delete_episode',
                'episode_id' => $deletedId,
                'episode_number' => $context['episode_number'],
                'story_id' => $context['story']['story_id'],
                'folder_name' => $context['story']['folder_name'],
                'mode' => 'full_db_and_files',
            ];
        }

        $this->editorRepository->deleteEpisodeDirectory(
            $context['story']['story_slug'],
            $context['episode_folder'],
        );

        return [
            'action' => 'delete_episode',
            'episode_id' => null,
            'episode_number' => $context['episode_number'],
            'story_id' => $context['story']['story_id'],
            'folder_name' => $context['story']['folder_name'],
            'mode' => 'filesystem_only',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteEpisodeScript(
        ?int $episodeId = null,
        ?int $storyId = null,
        ?string $folderName = null,
        ?string $storySlug = null,
        ?int $episodeNumber = null,
        ?string $episodeSlug = null,
    ): array {
        $context = $this->resolveEpisode(
            $episodeId,
            $storyId,
            $folderName,
            $storySlug,
            $episodeNumber,
            $episodeSlug,
        );

        $removedEditorFiles = [];
        foreach (glob($context['episode_dir'] . DIRECTORY_SEPARATOR . '*_story.md') ?: [] as $file) {
            if (@unlink($file)) {
                $removedEditorFiles[] = basename($file);
            }
        }
        foreach (glob($context['episode_dir'] . DIRECTORY_SEPARATOR . '*.md') ?: [] as $file) {
            if (@unlink($file)) {
                $removedEditorFiles[] = basename($file);
            }
        }

        $dbDeleted = false;
        if ($context['episode'] instanceof Episode) {
            $dbDeleted = $this->assetCleanup->deleteEpisodeScript($context['episode']);
        }

        return [
            'action' => 'delete_script',
            'episode_id' => $context['episode_id'],
            'episode_number' => $context['episode_number'],
            'story_id' => $context['story']['story_id'],
            'removed_editor_files' => $removedEditorFiles,
            'db_script_cleared' => $dbDeleted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteCharacter(
        ?int $characterId = null,
        ?int $storyId = null,
        ?string $folderName = null,
        ?string $storySlug = null,
        ?string $characterKey = null,
    ): array {
        $context = $this->resolveStory($storyId, $folderName, $storySlug);
        $removedKeys = [];
        $deletedCharacterIds = [];

        if ($characterId !== null && $characterId > 0) {
            $character = Character::query()->find($characterId);
            if ($character === null) {
                throw new \RuntimeException("Character not found for id {$characterId}.");
            }

            $result = $this->productionImport->deleteCharacterProductionArtifacts($character);
            $character->delete();
            $deletedCharacterIds[] = $characterId;
            $removedKeys = array_merge($removedKeys, $result['removed_keys'] ?? []);
        }

        if (is_string($characterKey) && trim($characterKey) !== '') {
            $key = trim($characterKey);
            $removed = $this->productionImport->removeAssetFromCharactersDocument(
                $context['story_slug'],
                'characters',
                $key,
            );
            if ($removed) {
                $removedKeys[] = $key;
            }

            if ($context['story_id'] !== null) {
                $dbCharacter = Character::query()
                    ->where('story_id', $context['story_id'])
                    ->where('name', $key)
                    ->first();

                if ($dbCharacter !== null && ! in_array((int) $dbCharacter->id, $deletedCharacterIds, true)) {
                    $result = $this->productionImport->deleteCharacterProductionArtifacts($dbCharacter);
                    $dbCharacter->delete();
                    $deletedCharacterIds[] = (int) $dbCharacter->id;
                    $removedKeys = array_merge($removedKeys, $result['removed_keys'] ?? []);
                }
            }
        }

        if ($characterId === null && ($characterKey === null || trim((string) $characterKey) === '')) {
            throw new \RuntimeException('Provide character_id or character_key.');
        }

        return [
            'action' => 'delete_character',
            'story_id' => $context['story_id'],
            'folder_name' => $context['folder_name'],
            'deleted_character_ids' => array_values(array_unique($deletedCharacterIds)),
            'removed_keys' => array_values(array_unique($removedKeys)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateCharactersFile(string $absolutePath, ?int $storyId = null, ?string $folderName = null, ?string $storySlug = null): array
    {
        if (! is_file($absolutePath)) {
            throw new \RuntimeException("Characters file not found: {$absolutePath}");
        }

        $context = $this->resolveStory($storyId, $folderName, $storySlug);
        $destPath = $context['story_dir'] . DIRECTORY_SEPARATOR . 'characters_and_objects.json';
        File::copy($absolutePath, $destPath);

        $import = $this->productionImport->importStoryFileFromPath(
            $context['story_slug'],
            $destPath,
            forceStoryId: $context['story_id'],
        );

        return [
            'action' => 'update_characters',
            'story_id' => $context['story_id'],
            'folder_name' => $context['folder_name'],
            'import' => $import['summary'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateEpisodeScript(
        string $absolutePath,
        ?int $episodeId = null,
        ?int $storyId = null,
        ?string $folderName = null,
        ?string $storySlug = null,
        ?int $episodeNumber = null,
        ?string $episodeSlug = null,
    ): array {
        if (! is_file($absolutePath)) {
            throw new \RuntimeException("Script file not found: {$absolutePath}");
        }

        $context = $this->resolveEpisode(
            $episodeId,
            $storyId,
            $folderName,
            $storySlug,
            $episodeNumber,
            $episodeSlug,
        );

        $basename = basename($absolutePath);
        $destPath = $context['episode_dir'] . DIRECTORY_SEPARATOR . $basename;
        File::copy($absolutePath, $destPath);

        $import = $this->productionImport->importStoryFileFromPath(
            $context['story']['story_slug'],
            $destPath,
            episodeSlug: $context['episode_slug'],
            forceStoryId: $context['story']['story_id'],
            forceEpisodeId: $context['episode_id'],
        );

        return [
            'action' => 'update_script',
            'story_id' => $context['story']['story_id'],
            'episode_id' => $context['episode_id'],
            'episode_number' => $context['episode_number'],
            'import' => $import['summary'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateEpisodePrompts(
        string $absolutePath,
        ?int $episodeId = null,
        ?int $storyId = null,
        ?string $folderName = null,
        ?string $storySlug = null,
        ?int $episodeNumber = null,
        ?string $episodeSlug = null,
    ): array {
        if (! is_file($absolutePath)) {
            throw new \RuntimeException("Prompts file not found: {$absolutePath}");
        }

        $context = $this->resolveEpisode(
            $episodeId,
            $storyId,
            $folderName,
            $storySlug,
            $episodeNumber,
            $episodeSlug,
        );

        $basename = basename($absolutePath);
        $destPath = $context['episode_dir'] . DIRECTORY_SEPARATOR . $basename;
        File::copy($absolutePath, $destPath);

        $import = $this->productionImport->importStoryFileFromPath(
            $context['story']['story_slug'],
            $destPath,
            episodeSlug: $context['episode_slug'],
            forceStoryId: $context['story']['story_id'],
            forceEpisodeId: $context['episode_id'],
        );

        return [
            'action' => 'update_prompts',
            'story_id' => $context['story']['story_id'],
            'episode_id' => $context['episode_id'],
            'episode_number' => $context['episode_number'],
            'import' => $import['summary'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStoryPackage(string $packagePath, string $folderName): array
    {
        $destPath = $this->oldStoriesImport->resolveDestinationPath(null);
        $package = [
            'folder_name' => $folderName,
            'path' => $packagePath,
            'manifest' => json_decode(
                (string) file_get_contents($packagePath . DIRECTORY_SEPARATOR . 'import_manifest.json'),
                true,
            ) ?? [],
            'story_slug' => $this->editorRepository->storyIdFromFolder($folderName),
        ];

        return $this->oldStoriesImport->importPackage($package, $destPath, [
            'skip_conflicts' => false,
            'create_db' => true,
            'force' => true,
            'dry_run' => false,
            'deploy_only' => false,
            'import_only' => false,
            'prompts_only' => false,
        ]);
    }

    private function findStoryDirectoryByFolderQuery(string $query): ?string
    {
        $basePath = $this->editorRepository->resolveStoriesPath();
        $direct = $basePath . DIRECTORY_SEPARATOR . $query;
        if (is_dir($direct)) {
            return $direct;
        }

        foreach (glob($basePath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folderName = basename($dir);
            if ($folderName === $query) {
                return $dir;
            }

            if (ctype_digit($query) && preg_match('/^' . preg_quote($query, '/') . '\s*-/', $folderName)) {
                return $dir;
            }

            if (stripos($folderName, $query) !== false) {
                return $dir;
            }
        }

        return null;
    }

    private function findStoryByEditorFolder(string $folderName, string $storySlug): ?Story
    {
        $meta = $this->editorRepository->getStoryMetaForSlug($storySlug);
        $title = trim((string) ($meta['name_persian'] ?? ''));
        if ($title === '' && preg_match('/^\d+\s*[-–]\s*(.+)$/u', $folderName, $m)) {
            $title = trim($m[1]);
        }

        if ($title === '') {
            return null;
        }

        return Story::query()->where('title', $title)->first();
    }

    /**
     * @return array{episode_dir: string, episode_folder: string, episode_slug: string, episode_number: int}|null
     */
    private function findEpisodeDirInStory(string $storyDir, int $episodeNumber, ?string $episodeSlug): ?array
    {
        foreach (glob($storyDir . '/episode*', GLOB_ONLYDIR) ?: [] as $episodeDir) {
            $folderName = basename($episodeDir);
            $slug = $this->editorRepository->episodeIdFromFolder($folderName);
            $number = 0;
            if (preg_match('/episode[_\s-]*(\d+)/i', $folderName, $matches)) {
                $number = (int) $matches[1];
            }

            if ($episodeSlug !== null && $slug === $episodeSlug) {
                return [
                    'episode_dir' => $episodeDir,
                    'episode_folder' => $folderName,
                    'episode_slug' => $slug,
                    'episode_number' => $number,
                ];
            }

            if ($number === $episodeNumber) {
                return [
                    'episode_dir' => $episodeDir,
                    'episode_folder' => $folderName,
                    'episode_slug' => $slug,
                    'episode_number' => $number,
                ];
            }
        }

        return null;
    }
}
