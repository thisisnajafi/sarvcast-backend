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

        if ($existingSlug !== null) {
            $dir = $this->findStoryDirectory($existingSlug);

            return [
                'id' => $existingSlug,
                'folder_name' => $dir !== null ? basename($dir) : $existingSlug,
                'path' => $dir,
                'created' => false,
            ];
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

        return [
            'id' => $this->episodeIdFromFolder($folderName),
            'folder_name' => $folderName,
            'path' => $path,
            'created' => true,
        ];
    }

    public function findStorySlugByDbStoryId(int $storyId): ?string
    {
        $fromProduction = \App\Models\StoryProductionFile::query()
            ->where('story_id', $storyId)
            ->whereNotNull('story_slug')
            ->value('story_slug');

        if (is_string($fromProduction) && $fromProduction !== '') {
            return $fromProduction;
        }

        $story = \App\Models\Story::query()->find($storyId);
        if ($story === null) {
            return null;
        }

        foreach ($this->listStories() as $item) {
            if ($item['name_persian'] === $story->title) {
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
}
