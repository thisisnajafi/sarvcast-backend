<?php

namespace App\Services;

use App\Support\StoryEditorPaths;
use Illuminate\Support\Str;

class StoryEditorRepository
{
    public function __construct(
        private readonly StoryMarkdownService $markdownService,
    ) {}

    public function resolveStoriesPath(): string
    {
        return StoryEditorPaths::resolve();
    }

    /**
     * @return array<int, array{
     *   id: string,
     *   folder_name: string,
     *   name_persian: string,
     *   name_english: string,
     *   episode_count: int,
     *   target_age: string|null
     * }>
     */
    public function listStories(): array
    {
        $basePath = $this->resolveStoriesPath();
        $stories = [];

        foreach (glob($basePath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folderName = basename($dir);
            if ($this->shouldExcludeDirectory($folderName)) {
                continue;
            }

            $meta = $this->readStoryMeta($dir);
            $episodes = $this->discoverEpisodes($dir);

            $stories[] = [
                'id' => $this->storyIdFromFolder($folderName),
                'folder_name' => $folderName,
                'name_persian' => $meta['name_persian'],
                'name_english' => $meta['name_english'],
                'episode_count' => count($episodes),
                'target_age' => $meta['target_age'],
            ];
        }

        usort($stories, fn (array $a, array $b) => strnatcasecmp($a['folder_name'], $b['folder_name']));

        return $stories;
    }

    public function findStoryDirectory(string $storyId): ?string
    {
        $basePath = $this->resolveStoriesPath();

        foreach (glob($basePath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folderName = basename($dir);
            if ($this->shouldExcludeDirectory($folderName)) {
                continue;
            }
            if ($this->storyIdFromFolder($folderName) === $storyId) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * @return array{name_persian: string, name_english: string, target_age: string|null, folder_name: string}|null
     */
    public function getStoryMetaForSlug(string $storySlug): ?array
    {
        $dir = $this->findStoryDirectory($storySlug);
        if ($dir === null) {
            return null;
        }

        $meta = $this->readStoryMeta($dir);

        return [
            'name_persian' => $meta['name_persian'],
            'name_english' => $meta['name_english'],
            'target_age' => $meta['target_age'],
            'folder_name' => basename($dir),
        ];
    }

    /**
     * @return array<int, array{
     *   id: string,
     *   episode_number: int,
     *   title_persian: string,
     *   file_path: string,
     *   last_modified: string
     * }>
     */
    public function listEpisodes(string $storyId): array
    {
        $storyDir = $this->findStoryDirectory($storyId);
        if ($storyDir === null) {
            return [];
        }

        $episodes = [];
        foreach ($this->discoverEpisodes($storyDir) as $episode) {
            $content = file_get_contents($episode['file_path']);
            $title = '';
            if (is_string($content) && preg_match('/^#\s+(.+)$/m', $content, $matches)) {
                $title = trim($matches[1]);
            }

            $episodes[] = [
                'id' => $episode['id'],
                'episode_number' => $episode['episode_number'],
                'title_persian' => $title,
                'file_path' => $episode['file_path'],
                'last_modified' => date('c', filemtime($episode['file_path'])),
            ];
        }

        usort($episodes, fn (array $a, array $b) => $a['episode_number'] <=> $b['episode_number']);

        return $episodes;
    }

    /**
     * @return array{
     *   episode: array,
     *   master_characters: array<string, array>,
     *   invalid_character_ids: array<int, string>,
     *   file_path: string,
     *   last_modified: string
     * }|null
     */
    public function getEpisode(string $storyId, string $episodeId): ?array
    {
        $storyDir = $this->findStoryDirectory($storyId);
        if ($storyDir === null) {
            return null;
        }

        $episode = $this->findEpisode($storyDir, $episodeId);
        if ($episode === null) {
            return null;
        }

        $raw = file_get_contents($episode['file_path']);
        if (!is_string($raw)) {
            return null;
        }

        $parsed = $this->markdownService->parse($raw);
        $masterCharacters = $this->readMasterCharacters($storyDir);
        $invalidIds = $this->findInvalidCharacterIds($parsed['characters'] ?? [], $masterCharacters);

        return [
            'episode' => $parsed,
            'raw_markdown' => $raw,
            'master_characters' => $masterCharacters,
            'invalid_character_ids' => $invalidIds,
            'file_path' => $episode['file_path'],
            'last_modified' => date('c', filemtime($episode['file_path'])),
        ];
    }

    /**
     * @return array{episode: array, backup_path: string, master_characters: array, invalid_character_ids: array, file_path: string, last_modified: string}|null
     */
    public function saveRawMarkdown(string $storyId, string $episodeId, string $rawMarkdown): ?array
    {
        $storyDir = $this->findStoryDirectory($storyId);
        if ($storyDir === null) {
            return null;
        }

        $episode = $this->findEpisode($storyDir, $episodeId);
        if ($episode === null) {
            return null;
        }

        $backupPath = $this->createBackup($episode['file_path']);
        $normalized = str_replace(["\r\n", "\r"], "\n", $rawMarkdown);

        if (file_put_contents($episode['file_path'], $normalized) === false) {
            throw new \RuntimeException('Failed to write episode markdown file.');
        }

        $reparsed = $this->markdownService->parse($normalized);
        $masterCharacters = $this->readMasterCharacters($storyDir);

        return [
            'episode' => $reparsed,
            'raw_markdown' => $normalized,
            'master_characters' => $masterCharacters,
            'invalid_character_ids' => $this->findInvalidCharacterIds($reparsed['characters'] ?? [], $masterCharacters),
            'file_path' => $episode['file_path'],
            'last_modified' => date('c', filemtime($episode['file_path'])),
            'backup_path' => $backupPath,
        ];
    }

    /**
     * @return array{episode: array, backup_path: string}|null
     */
    public function saveEpisode(string $storyId, string $episodeId, array $structuredEpisode): ?array
    {
        $storyDir = $this->findStoryDirectory($storyId);
        if ($storyDir === null) {
            return null;
        }

        $episode = $this->findEpisode($storyDir, $episodeId);
        if ($episode === null) {
            return null;
        }

        $backupPath = $this->createBackup($episode['file_path']);
        $markdown = $this->markdownService->serialize($structuredEpisode);

        if (file_put_contents($episode['file_path'], $markdown) === false) {
            throw new \RuntimeException('Failed to write episode markdown file.');
        }

        $reparsed = $this->markdownService->parse($markdown);
        $masterCharacters = $this->readMasterCharacters($storyDir);

        return [
            'episode' => $reparsed,
            'master_characters' => $masterCharacters,
            'invalid_character_ids' => $this->findInvalidCharacterIds($reparsed['characters'] ?? [], $masterCharacters),
            'file_path' => $episode['file_path'],
            'last_modified' => date('c', filemtime($episode['file_path'])),
            'backup_path' => $backupPath,
        ];
    }

    public function storyIdFromFolder(string $folderName): string
    {
        $slug = Str::slug($folderName);

        return $slug !== '' ? $slug : md5($folderName);
    }

    public function episodeIdFromFolder(string $folderName): string
    {
        return Str::slug($folderName, '_');
    }

    public function findEpisodeDirectory(string $storyDir, string $episodeSlug): ?string
    {
        foreach (glob($storyDir . '/episode*', GLOB_ONLYDIR) ?: [] as $episodeDir) {
            if ($this->episodeIdFromFolder(basename($episodeDir)) === $episodeSlug) {
                return $episodeDir;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, folder_name: string, path: string, created: bool}
     */
    public function createStoryScaffold(string $title, ?int $dbStoryId = null): array
    {
        $basePath = $this->resolveStoriesPath();
        $existingSlug = $dbStoryId !== null ? $this->findStorySlugByDbStoryId($dbStoryId) : null;

        // Only reuse a slug when the story-editor folder actually exists on disk.
        // Stale story_production_* rows can point at a missing folder and must not block recreate.
        if ($existingSlug !== null) {
            $dir = $this->findStoryDirectory($existingSlug);
            if ($dir !== null) {
                return [
                    'id' => $existingSlug,
                    'folder_name' => basename($dir),
                    'path' => $dir,
                    'created' => false,
                ];
            }
        }

        $folderName = $this->buildStoryFolderName($title);
        $path = $basePath . DIRECTORY_SEPARATOR . $folderName;

        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new \RuntimeException('امکان ایجاد پوشه داستان وجود ندارد.');
        }

        return [
            'id' => $this->storyIdFromFolder($folderName),
            'folder_name' => $folderName,
            'path' => $path,
            'created' => true,
        ];
    }

    /**
     * @return array{id: string, folder_name: string, path: string, created: bool}
     */
    public function createEpisodeScaffold(string $storySlug, int $episodeNumber, string $title): array
    {
        $storyDir = $this->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            throw new \RuntimeException('داستان یافت نشد.');
        }

        foreach (glob($storyDir . '/episode*', GLOB_ONLYDIR) ?: [] as $episodeDir) {
            if (preg_match('/episode[_\s-]*(\d+)/i', basename($episodeDir), $matches)
                && (int) $matches[1] === $episodeNumber) {
                $folderName = basename($episodeDir);

                $this->ensureStubMarkdown($episodeDir, $episodeNumber, $title);

                return [
                    'id' => $this->episodeIdFromFolder($folderName),
                    'folder_name' => $folderName,
                    'path' => $episodeDir,
                    'created' => false,
                ];
            }
        }

        $folderName = $this->buildEpisodeFolderName($episodeNumber, $title);
        $path = $storyDir . DIRECTORY_SEPARATOR . $folderName;

        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new \RuntimeException('امکان ایجاد پوشه قسمت وجود ندارد.');
        }

        $this->ensureStubMarkdown($path, $episodeNumber, $title);

        return [
            'id' => $this->episodeIdFromFolder($folderName),
            'folder_name' => $folderName,
            'path' => $path,
            'created' => true,
        ];
    }

    /**
     * Linked editor slug from production tables only (never title substring match).
     */
    public function findLinkedStorySlug(int $storyId): ?string
    {
        $productionSlugs = \App\Models\StoryProductionFile::query()
            ->where('story_id', $storyId)
            ->whereNotNull('story_slug')
            ->distinct()
            ->pluck('story_slug');

        foreach ($productionSlugs as $slug) {
            if (is_string($slug) && $slug !== '' && $this->findStoryDirectory($slug) !== null) {
                return $slug;
            }
        }

        $assetSlugs = \App\Models\StoryProductionAsset::query()
            ->where('story_id', $storyId)
            ->whereNotNull('story_slug')
            ->distinct()
            ->pluck('story_slug');

        foreach ($assetSlugs as $slug) {
            if (is_string($slug) && $slug !== '' && $this->findStoryDirectory($slug) !== null) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, folder_name: string, path: string, file_path: string|null, episode_number: int}|null
     */
    public function findEpisodeDirBySlug(string $storySlug, string $episodeSlug): ?array
    {
        $storyDir = $this->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            return null;
        }

        foreach ($this->listEpisodeDirectories($storyDir) as $item) {
            if ($item['id'] === $episodeSlug) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, folder_name: string, path: string, file_path: string|null, episode_number: int}|null
     */
    public function findEpisodeDirByNumber(string $storySlug, int $episodeNumber): ?array
    {
        $storyDir = $this->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            return null;
        }

        foreach ($this->listEpisodeDirectories($storyDir) as $item) {
            if ($item['episode_number'] === $episodeNumber) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, folder_name: string, path: string}
     */
    public function renameEpisodeDirectory(string $storySlug, string $currentFolderName, int $newNumber, ?string $nameTemplate = null): array
    {
        $storyDir = $this->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            throw new \RuntimeException('داستان یافت نشد.');
        }

        $from = $storyDir.DIRECTORY_SEPARATOR.$currentFolderName;
        if (! is_dir($from)) {
            throw new \RuntimeException('پوشه قسمت یافت نشد.');
        }

        $newFolderName = $this->folderNameWithNumber($nameTemplate ?? $currentFolderName, $newNumber);
        $to = $storyDir.DIRECTORY_SEPARATOR.$newFolderName;
        if (is_dir($to) && realpath($to) !== realpath($from)) {
            $newFolderName = $this->folderNameWithNumber($currentFolderName, $newNumber).'_n'.$newNumber;
            $to = $storyDir.DIRECTORY_SEPARATOR.$newFolderName;
        }

        if (! @rename($from, $to)) {
            throw new \RuntimeException('امکان تغییر نام پوشه قسمت وجود ندارد.');
        }

        $md = $this->findEpisodeMarkdownFile($to);
        if ($md !== null) {
            $raw = file_get_contents($md);
            if (is_string($raw)) {
                file_put_contents($md, $this->markdownService->rewriteEpisodeHeading($raw, $newNumber));
            }
        }

        return [
            'id' => $this->episodeIdFromFolder($newFolderName),
            'folder_name' => $newFolderName,
            'path' => $to,
        ];
    }

    public function moveEpisodeDirectoryToTemp(string $storySlug, string $folderName, string $tempPrefix): string
    {
        $storyDir = $this->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            throw new \RuntimeException('داستان یافت نشد.');
        }

        $from = $storyDir.DIRECTORY_SEPARATOR.$folderName;
        if (! is_dir($from)) {
            throw new \RuntimeException('پوشه قسمت یافت نشد.');
        }

        $tempName = $tempPrefix.$folderName;
        $to = $storyDir.DIRECTORY_SEPARATOR.$tempName;
        if (! @rename($from, $to)) {
            throw new \RuntimeException('امکان جابه‌جایی موقت پوشه قسمت وجود ندارد.');
        }

        return $tempName;
    }

    public function deleteEpisodeDirectory(string $storySlug, string $folderName): bool
    {
        $storyDir = $this->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            return false;
        }

        $path = $storyDir.DIRECTORY_SEPARATOR.$folderName;
        $storyReal = realpath($storyDir);
        $pathReal = realpath($path);
        if ($storyReal === false || $pathReal === false) {
            return false;
        }

        $storyNorm = rtrim(str_replace('\\', '/', $storyReal), '/');
        $pathNorm = str_replace('\\', '/', $pathReal);
        if ($pathNorm === $storyNorm || ! str_starts_with($pathNorm, $storyNorm.'/')) {
            return false;
        }

        $this->deleteDirectoryRecursive($pathReal);

        return true;
    }

    /**
     * Delete the entire story-editor directory (all episode MD folders and story metadata).
     */
    public function deleteStoryDirectory(string $storySlugOrFolderName): bool
    {
        $storyDir = $this->findStoryDirectory($storySlugOrFolderName);

        if ($storyDir === null) {
            $basePath = $this->resolveStoriesPath();
            $candidate = $basePath.DIRECTORY_SEPARATOR.$storySlugOrFolderName;
            if (is_dir($candidate)) {
                $storyDir = realpath($candidate) ?: $candidate;
            }
        }

        if ($storyDir === null) {
            return false;
        }

        $this->deleteDirectoryRecursive($storyDir);

        return true;
    }

    /**
     * @return list<string>
     */
    public function resolveStoryEditorSlugs(int $storyId): array
    {
        $slugs = [];

        $fromDb = \App\Models\StoryProductionFile::query()
            ->where('story_id', $storyId)
            ->whereNotNull('story_slug')
            ->distinct()
            ->pluck('story_slug')
            ->merge(
                \App\Models\StoryProductionAsset::query()
                    ->where('story_id', $storyId)
                    ->whereNotNull('story_slug')
                    ->distinct()
                    ->pluck('story_slug')
            );

        foreach ($fromDb as $slug) {
            if (is_string($slug) && $slug !== '' && ! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        foreach ([
            $this->findLinkedStorySlug($storyId),
            $this->findStorySlugByDbStoryId($storyId),
        ] as $slug) {
            if (is_string($slug) && $slug !== '' && ! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * @return array{id: string, folder_name: string, path: string}
     */
    public function copyEpisodeDirectory(string $storySlug, string $sourceFolderName, int $newNumber, string $title): array
    {
        $storyDir = $this->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            throw new \RuntimeException('داستان یافت نشد.');
        }

        $from = $storyDir.DIRECTORY_SEPARATOR.$sourceFolderName;
        if (! is_dir($from)) {
            throw new \RuntimeException('پوشه قسمت مبدأ یافت نشد.');
        }

        $folderName = $this->buildEpisodeFolderName($newNumber, $title);
        $to = $storyDir.DIRECTORY_SEPARATOR.$folderName;
        if (is_dir($to)) {
            $folderName = $this->buildEpisodeFolderName($newNumber, $title.'_copy');
            $to = $storyDir.DIRECTORY_SEPARATOR.$folderName;
        }

        $this->copyDirectoryRecursive($from, $to);
        $md = $this->findEpisodeMarkdownFile($to);
        if ($md !== null) {
            $raw = file_get_contents($md);
            if (is_string($raw)) {
                file_put_contents($md, $this->markdownService->rewriteEpisodeHeading($raw, $newNumber, $title));
            }
        } else {
            $this->ensureStubMarkdown($to, $newNumber, $title);
        }

        return [
            'id' => $this->episodeIdFromFolder($folderName),
            'folder_name' => $folderName,
            'path' => $to,
        ];
    }

    public function findStorySlugByDbStoryId(int $storyId): ?string
    {
        $productionSlugs = \App\Models\StoryProductionFile::query()
            ->where('story_id', $storyId)
            ->whereNotNull('story_slug')
            ->distinct()
            ->pluck('story_slug');

        foreach ($productionSlugs as $slug) {
            if (is_string($slug) && $slug !== '' && $this->findStoryDirectory($slug) !== null) {
                return $slug;
            }
        }

        $assetSlugs = \App\Models\StoryProductionAsset::query()
            ->where('story_id', $storyId)
            ->whereNotNull('story_slug')
            ->distinct()
            ->pluck('story_slug');

        foreach ($assetSlugs as $slug) {
            if (is_string($slug) && $slug !== '' && $this->findStoryDirectory($slug) !== null) {
                return $slug;
            }
        }

        $story = \App\Models\Story::query()->find($storyId);
        if ($story === null) {
            return null;
        }

        $title = trim((string) $story->title);
        if ($title === '') {
            return null;
        }

        $access = app(ContributorStoryAccessService::class);

        foreach ($this->listStories() as $item) {
            $persian = trim((string) ($item['name_persian'] ?? ''));
            if ($persian !== '' && $access->titlesMatch($persian, $title)) {
                return $item['id'];
            }

            $folder = trim((string) ($item['folder_name'] ?? ''));
            if ($folder !== '' && preg_match('/^\d+\s*[-–]\s*(.+)$/u', $folder, $m)) {
                if ($access->titlesMatch(trim($m[1]), $title)) {
                    return $item['id'];
                }
            }

            if ($folder !== '' && $access->titlesMatch($folder, $title)) {
                return $item['id'];
            }
        }

        return null;
    }

    public function buildStoryFolderName(string $title): string
    {
        return $this->nextStoryOrdinal() . ' - ' . trim($title);
    }

    public function buildEpisodeFolderName(int $episodeNumber, string $title): string
    {
        $slug = Str::slug($title, '_');
        if ($slug === '') {
            $slug = 'episode';
        }

        return 'episode_' . $episodeNumber . '_' . $slug;
    }

    public function nextStoryOrdinal(): int
    {
        $basePath = $this->resolveStoriesPath();
        $max = 0;

        foreach (glob($basePath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (preg_match('/^(\d+)\s*-/', basename($dir), $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    /**
     * @return array{name_persian: string, name_english: string, target_age: string|null}
     */
    private function readStoryMeta(string $storyDir): array
    {
        $jsonPath = $storyDir . '/characters_and_objects.json';
        if (!is_file($jsonPath)) {
            return [
                'name_persian' => $this->guessPersianNameFromFolder(basename($storyDir)),
                'name_english' => $this->guessEnglishNameFromFolder(basename($storyDir)),
                'target_age' => null,
            ];
        }

        $json = json_decode((string) file_get_contents($jsonPath), true);
        if (!is_array($json)) {
            return [
                'name_persian' => $this->guessPersianNameFromFolder(basename($storyDir)),
                'name_english' => $this->guessEnglishNameFromFolder(basename($storyDir)),
                'target_age' => null,
            ];
        }

        $title = (string) ($json['story_title'] ?? '');
        $persian = $title;
        $english = $this->guessEnglishNameFromFolder(basename($storyDir));

        if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/u', $title, $matches)) {
            $persian = trim($matches[1]);
            $english = trim($matches[2]);
        }

        return [
            'name_persian' => $persian,
            'name_english' => $english,
            'target_age' => isset($json['target_age']) ? (string) $json['target_age'] : null,
        ];
    }

    /**
     * @return array<string, array>
     */
    private function readMasterCharacters(string $storyDir): array
    {
        $jsonPath = $storyDir . '/characters_and_objects.json';
        if (!is_file($jsonPath)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($jsonPath), true);
        if (!is_array($json) || !isset($json['characters']) || !is_array($json['characters'])) {
            return [];
        }

        return $json['characters'];
    }

    /**
     * @param  array<int, array{character_id?: string}>  $episodeCharacters
     * @param  array<string, array>  $masterCharacters
     * @return array<int, string>
     */
    private function findInvalidCharacterIds(array $episodeCharacters, array $masterCharacters): array
    {
        if ($masterCharacters === []) {
            return [];
        }

        $invalid = [];
        foreach ($episodeCharacters as $character) {
            $id = trim((string) ($character['character_id'] ?? ''));
            if ($id !== '' && !array_key_exists($id, $masterCharacters)) {
                $invalid[] = $id;
            }
        }

        return array_values(array_unique($invalid));
    }

    /**
     * @return array<int, array{id: string, episode_number: int, folder_name: string, file_path: string}>
     */
    private function discoverEpisodes(string $storyDir): array
    {
        $episodes = [];

        foreach (glob($storyDir . '/episode*', GLOB_ONLYDIR) ?: [] as $episodeDir) {
            $folderName = basename($episodeDir);
            $mdFile = $this->findEpisodeMarkdownFile($episodeDir);
            if ($mdFile === null) {
                continue;
            }

            $episodeNumber = 0;
            if (preg_match('/episode[_\s-]*(\d+)/i', $folderName, $matches)) {
                $episodeNumber = (int) $matches[1];
            }

            $episodes[] = [
                'id' => $this->episodeIdFromFolder($folderName),
                'episode_number' => $episodeNumber,
                'folder_name' => $folderName,
                'file_path' => $mdFile,
            ];
        }

        return $episodes;
    }

    private function findEpisode(string $storyDir, string $episodeId): ?array
    {
        foreach ($this->discoverEpisodes($storyDir) as $episode) {
            if ($episode['id'] === $episodeId) {
                return $episode;
            }
        }

        return null;
    }

    private function findEpisodeMarkdownFile(string $episodeDir): ?string
    {
        $exclude = config('story_editor.exclude_directory_patterns', []);

        foreach (glob($episodeDir . '/*.md') ?: [] as $mdFile) {
            $stem = strtoupper(pathinfo($mdFile, PATHINFO_FILENAME));
            $skip = false;
            foreach ($exclude as $pattern) {
                if (stripos($stem, strtoupper($pattern)) !== false) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                return $mdFile;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{
     *   id: string,
     *   filename: string,
     *   created_at: string,
     *   size_bytes: int,
     *   summary: array{title_persian: string|null, scene_count: int, dialogue_line_count: int}
     * }>
     */
    public function listEpisodeBackups(string $storyId, string $episodeId): ?array
    {
        $paths = $this->resolveEpisodeBackupPaths($storyId, $episodeId);
        if ($paths === null) {
            return null;
        }

        $backupDir = $paths['backup_dir'];
        if (! is_dir($backupDir)) {
            return [];
        }

        $items = [];
        foreach (glob($backupDir . '/*.bak') ?: [] as $file) {
            $id = basename($file);
            if ($this->sanitizeBackupId($id) === null) {
                continue;
            }

            $items[] = $this->buildBackupListItem($file, $id);
        }

        usort($items, static fn (array $a, array $b) => strcmp($b['id'], $a['id']));

        return $items;
    }

    /**
     * @return array{
     *   id: string,
     *   filename: string,
     *   created_at: string,
     *   size_bytes: int,
     *   summary: array{title_persian: string|null, scene_count: int, dialogue_line_count: int},
     *   episode: array,
     *   raw_markdown: string
     * }|null
     */
    public function getEpisodeBackup(string $storyId, string $episodeId, string $backupId): ?array
    {
        $file = $this->resolveBackupFile($storyId, $episodeId, $backupId);
        if ($file === null) {
            return null;
        }

        $raw = file_get_contents($file);
        if (! is_string($raw)) {
            return null;
        }

        $parsed = $this->markdownService->parse($raw);
        $id = basename($file);
        $item = $this->buildBackupListItem($file, $id, $parsed);

        return array_merge($item, [
            'episode' => $parsed,
            'raw_markdown' => $raw,
        ]);
    }

    /**
     * Restore a backup onto the live episode markdown (creates a backup of current live first).
     *
     * @return array{episode: array, backup_path: string, master_characters: array, invalid_character_ids: array, file_path: string, last_modified: string, restored_from: string}|null
     */
    public function restoreEpisodeBackup(string $storyId, string $episodeId, string $backupId): ?array
    {
        $paths = $this->resolveEpisodeBackupPaths($storyId, $episodeId);
        if ($paths === null) {
            return null;
        }

        $backupFile = $this->resolveBackupFile($storyId, $episodeId, $backupId);
        if ($backupFile === null) {
            return null;
        }

        $raw = file_get_contents($backupFile);
        if (! is_string($raw)) {
            throw new \RuntimeException('Failed to read backup file.');
        }

        $livePath = $paths['file_path'];
        $preRestoreBackup = $this->createBackup($livePath);

        if (file_put_contents($livePath, $raw) === false) {
            throw new \RuntimeException('Failed to restore episode markdown file.');
        }

        $reparsed = $this->markdownService->parse($raw);
        $masterCharacters = $this->readMasterCharacters($paths['story_dir']);

        return [
            'episode' => $reparsed,
            'master_characters' => $masterCharacters,
            'invalid_character_ids' => $this->findInvalidCharacterIds($reparsed['characters'] ?? [], $masterCharacters),
            'file_path' => $livePath,
            'last_modified' => date('c', filemtime($livePath)),
            'backup_path' => $preRestoreBackup,
            'restored_from' => basename($backupFile),
        ];
    }

    public function deleteEpisodeBackup(string $storyId, string $episodeId, string $backupId): bool
    {
        $file = $this->resolveBackupFile($storyId, $episodeId, $backupId);
        if ($file === null) {
            return false;
        }

        return unlink($file);
    }

    /**
     * @param  array<int, string>  $backupIds
     * @return array{deleted: array<int, string>, missing: array<int, string>}
     */
    public function deleteEpisodeBackups(string $storyId, string $episodeId, array $backupIds): array
    {
        $deleted = [];
        $missing = [];

        foreach ($backupIds as $backupId) {
            if (! is_string($backupId) || $backupId === '') {
                continue;
            }

            if ($this->deleteEpisodeBackup($storyId, $episodeId, $backupId)) {
                $deleted[] = $this->sanitizeBackupId($backupId) ?? $backupId;
            } else {
                $missing[] = $backupId;
            }
        }

        return [
            'deleted' => $deleted,
            'missing' => $missing,
        ];
    }

    public function sanitizeBackupId(string $backupId): ?string
    {
        if ($backupId === '' || $backupId !== basename($backupId)) {
            return null;
        }

        if (str_contains($backupId, '..') || str_contains($backupId, '/') || str_contains($backupId, '\\')) {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9._-]+\.bak$/', $backupId)) {
            return null;
        }

        return $backupId;
    }

    /**
     * @return array{story_dir: string, file_path: string, backup_dir: string}|null
     */
    private function resolveEpisodeBackupPaths(string $storyId, string $episodeId): ?array
    {
        $storyDir = $this->findStoryDirectory($storyId);
        if ($storyDir === null) {
            return null;
        }

        $episode = $this->findEpisode($storyDir, $episodeId);
        if ($episode === null) {
            return null;
        }

        return [
            'story_dir' => $storyDir,
            'file_path' => $episode['file_path'],
            'backup_dir' => dirname($episode['file_path']) . DIRECTORY_SEPARATOR . '_backups',
        ];
    }

    private function resolveBackupFile(string $storyId, string $episodeId, string $backupId): ?string
    {
        $safeId = $this->sanitizeBackupId($backupId);
        if ($safeId === null) {
            return null;
        }

        $paths = $this->resolveEpisodeBackupPaths($storyId, $episodeId);
        if ($paths === null) {
            return null;
        }

        $file = $paths['backup_dir'] . DIRECTORY_SEPARATOR . $safeId;
        $realBackupDir = realpath($paths['backup_dir']);
        $realFile = realpath($file);

        if ($realBackupDir === false || $realFile === false) {
            return null;
        }

        $normalizedDir = rtrim(str_replace('\\', '/', $realBackupDir), '/');
        $normalizedFile = str_replace('\\', '/', $realFile);
        if (! str_starts_with($normalizedFile, $normalizedDir . '/')) {
            return null;
        }

        if (! is_file($realFile)) {
            return null;
        }

        return $realFile;
    }

    /**
     * @param  array<string, mixed>|null  $parsed
     * @return array{
     *   id: string,
     *   filename: string,
     *   created_at: string,
     *   size_bytes: int,
     *   summary: array{title_persian: string|null, scene_count: int, dialogue_line_count: int}
     * }
     */
    private function buildBackupListItem(string $file, string $id, ?array $parsed = null): array
    {
        if ($parsed === null) {
            $raw = file_get_contents($file);
            $parsed = is_string($raw) ? $this->markdownService->parse($raw) : [];
        }

        $mtime = filemtime($file) ?: time();

        return [
            'id' => $id,
            'filename' => $id,
            'created_at' => date('c', $mtime),
            'size_bytes' => (int) filesize($file),
            'summary' => $this->summarizeParsedEpisode(is_array($parsed) ? $parsed : []),
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{title_persian: string|null, scene_count: int, dialogue_line_count: int}
     */
    private function summarizeParsedEpisode(array $parsed): array
    {
        $dialogueCount = 0;
        foreach ($parsed['scenes'] ?? [] as $scene) {
            if (! is_array($scene)) {
                continue;
            }
            $dialogueCount += count($scene['dialogue_lines'] ?? []);
        }

        return [
            'title_persian' => isset($parsed['metadata']['title_persian'])
                ? (string) $parsed['metadata']['title_persian']
                : null,
            'scene_count' => count($parsed['scenes'] ?? []),
            'dialogue_line_count' => $dialogueCount,
        ];
    }

    private function createBackup(string $filePath): string
    {
        $dir = dirname($filePath);
        $backupDir = $dir . '/_backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $basename = pathinfo($filePath, PATHINFO_FILENAME);
        $timestamp = now()->format('Ymd_His');
        $backupPath = $backupDir . '/' . $basename . '.' . $timestamp . '.bak';

        if (!copy($filePath, $backupPath)) {
            throw new \RuntimeException('Failed to create backup file.');
        }

        return $backupPath;
    }

    private function shouldExcludeDirectory(string $dirName): bool
    {
        $patterns = config('story_editor.exclude_directory_patterns', []);

        if (in_array($dirName, $patterns, true)) {
            return true;
        }

        foreach ($patterns as $pattern) {
            if (preg_match('/^(' . preg_quote($pattern, '/') . ')/i', $dirName)) {
                return true;
            }
        }

        return false;
    }

    private function guessPersianNameFromFolder(string $folderName): string
    {
        if (preg_match('/^\d+\s*-\s*(.+)$/u', $folderName, $matches)) {
            return trim($matches[1]);
        }

        return $folderName;
    }

    private function guessEnglishNameFromFolder(string $folderName): string
    {
        $name = $this->guessPersianNameFromFolder($folderName);

        return Str::headline($name);
    }

    /**
     * @return list<array{id: string, folder_name: string, path: string, file_path: string|null, episode_number: int}>
     */
    private function listEpisodeDirectories(string $storyDir): array
    {
        $items = [];

        foreach (glob($storyDir.'/episode*', GLOB_ONLYDIR) ?: [] as $episodeDir) {
            $folderName = basename($episodeDir);
            $episodeNumber = 0;
            if (preg_match('/episode[_\s-]*(\d+)/i', $folderName, $matches)) {
                $episodeNumber = (int) $matches[1];
            }

            $items[] = [
                'id' => $this->episodeIdFromFolder($folderName),
                'folder_name' => $folderName,
                'path' => $episodeDir,
                'file_path' => $this->findEpisodeMarkdownFile($episodeDir),
                'episode_number' => $episodeNumber,
            ];
        }

        return $items;
    }

    private function folderNameWithNumber(string $folderName, int $number): string
    {
        $updated = preg_replace('/(episode[_\s-]*)(\d+)/i', '${1}'.$number, $folderName, 1);

        return is_string($updated) && $updated !== '' ? $updated : 'episode_'.$number;
    }

    private function ensureStubMarkdown(string $episodeDir, int $episodeNumber, string $title): void
    {
        if ($this->findEpisodeMarkdownFile($episodeDir) !== null) {
            return;
        }

        $stem = Str::slug($title, '_');
        if ($stem === '') {
            $stem = 'episode';
        }

        $path = $episodeDir.DIRECTORY_SEPARATOR.$stem.'_story.md';
        file_put_contents($path, $this->markdownService->serialize([
            'metadata' => [
                'title_persian' => $title,
                'episode_number' => $episodeNumber,
                'total_episodes' => $episodeNumber,
            ],
            'characters' => [],
            'scenes' => [],
            'closing' => [],
        ]));
    }

    private function deleteDirectoryRecursive(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($directory);
    }

    private function copyDirectoryRecursive(string $from, string $to): void
    {
        if (! is_dir($to) && ! mkdir($to, 0755, true) && ! is_dir($to)) {
            throw new \RuntimeException('امکان کپی پوشه قسمت وجود ندارد.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $sub = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($from))), '/');
            $target = $to.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $sub);
            if ($file->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $dir = dirname($target);
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($file->getPathname(), $target);
            }
        }
    }
}
