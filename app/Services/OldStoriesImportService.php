<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Story;
use App\Support\StoryEditorPaths;
use Illuminate\Support\Str;

class OldStoriesImportService
{
    private const CONFLICT_ID_MAX = 7;

    public function __construct(
        private readonly StoryEditorRepository $editorRepository,
        private readonly StoryProductionImportService $importService,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPackages(string $sourcePath): array
    {
        $packages = [];

        foreach (glob(rtrim($sourcePath, '\\/') . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'import_manifest.json';
            if (! is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (! is_array($manifest)) {
                continue;
            }

            $packages[] = [
                'folder_name' => basename($dir),
                'path' => $dir,
                'manifest' => $manifest,
                'story_slug' => $this->editorRepository->storyIdFromFolder(basename($dir)),
            ];
        }

        usort($packages, fn (array $a, array $b) => strnatcasecmp($a['folder_name'], $b['folder_name']));

        return $packages;
    }

    public function resolveSourcePath(?string $override): string
    {
        if (is_string($override) && $override !== '') {
            $path = $this->normalizePath($override);
            if (! is_dir($path)) {
                throw new \RuntimeException("Source directory not found: {$path}");
            }

            return realpath($path) ?: $path;
        }

        foreach ([
            base_path('../manji-stories/staging'),
            base_path('../../manji-stories/staging'),
        ] as $candidate) {
            if (is_dir($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        throw new \RuntimeException(
            'Staging directory not found. Pass --source=/path/to/manji-stories/staging'
        );
    }

    public function resolveDestinationPath(?string $override): string
    {
        if (is_string($override) && $override !== '') {
            $path = $this->normalizePath($override);
            if (! is_dir($path)) {
                @mkdir($path, 0755, true);
            }

            return realpath($path) ?: $path;
        }

        return StoryEditorPaths::resolve();
    }

    public function hasIdConflict(string $folderName, string $destinationPath): bool
    {
        if (! preg_match('/^(\d+)\s*-(.+)$/u', $folderName, $matches)) {
            return false;
        }

        $id = (int) $matches[1];
        if ($id > self::CONFLICT_ID_MAX) {
            return false;
        }

        foreach (glob(rtrim($destinationPath, '\\/') . '/' . $id . ' - *', GLOB_ONLYDIR) ?: [] as $existingDir) {
            if (basename($existingDir) !== $folderName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function importPackage(array $package, string $destinationPath, array $options = []): array
    {
        $folderName = $package['folder_name'];
        $sourcePath = $package['path'];
        $manifest = $package['manifest'];
        $storySlug = $package['story_slug'];

        $result = [
            'folder_name' => $folderName,
            'story_slug' => $storySlug,
            'status' => 'pending',
            'deployed' => false,
            'imports' => [],
            'errors' => [],
            'skipped_episodes' => [],
        ];

        if (($options['skip_conflicts'] ?? true) && $this->hasIdConflict($folderName, $destinationPath)) {
            $result['status'] = 'skipped_conflict';

            return $result;
        }

        $destStoryPath = rtrim($destinationPath, '\\/') . DIRECTORY_SEPARATOR . $folderName;

        if ($options['dry_run'] ?? false) {
            $result['status'] = 'dry_run';
            $result['would_deploy_to'] = $destStoryPath;
            $result['episodes'] = count($manifest['episodes'] ?? []);

            return $result;
        }

        if (! ($options['import_only'] ?? false)) {
            if (is_dir($destStoryPath) && ! ($options['force'] ?? false)) {
                $result['deployed'] = false;
                $result['deploy_note'] = 'destination_exists';
            } else {
                $this->copyDirectory($sourcePath, $destStoryPath);
                $result['deployed'] = true;
            }
        } elseif (! is_dir($destStoryPath)) {
            $result['status'] = 'failed';
            $result['errors'][] = "Destination story folder not found: {$destStoryPath}";

            return $result;
        }

        if ($options['deploy_only'] ?? false) {
            $result['status'] = 'deployed';

            return $result;
        }

        $storyId = null;
        $episodeIds = [];

        if ($options['create_db'] ?? false) {
            $storyId = $this->ensureStoryRecord($manifest, $folderName, $destStoryPath);
            $result['story_id'] = $storyId;
        }

        try {
            $charactersPath = $destStoryPath . DIRECTORY_SEPARATOR . 'characters_and_objects.json';
            if (is_file($charactersPath)) {
                $import = $this->importService->importStoryFileFromPath(
                    $storySlug,
                    $charactersPath,
                    forceStoryId: $storyId,
                );
                $result['imports'][] = ['type' => 'characters_and_objects', 'summary' => $import['summary'] ?? []];
                $storyId = $storyId ?? ($import['summary']['story_id'] ?? null);

                if ($storyId) {
                    Story::whereKey($storyId)->where(function ($q) {
                        $q->whereNull('workflow_status')
                            ->orWhere('workflow_status', Story::WORKFLOW_WRITTEN);
                    })->update([
                        'workflow_status' => Story::WORKFLOW_CHARACTERS_MADE,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $result['errors'][] = 'characters: ' . $e->getMessage();
        }

        foreach ($manifest['episodes'] ?? [] as $episodeManifest) {
            $needsScript = (bool) ($episodeManifest['needs_script'] ?? false);
            $hasScript = (bool) ($episodeManifest['has_script'] ?? false);

            if ($needsScript && ! ($options['prompts_only'] ?? false)) {
                $result['skipped_episodes'][] = [
                    'episode_number' => $episodeManifest['episode_number'] ?? null,
                    'reason' => 'needs_script',
                ];

                continue;
            }

            $targetFolder = (string) ($episodeManifest['target_folder'] ?? '');
            if ($targetFolder === '') {
                continue;
            }

            $episodeDir = $destStoryPath . DIRECTORY_SEPARATOR . $targetFolder;
            if (! is_dir($episodeDir)) {
                $result['errors'][] = "Episode folder missing: {$targetFolder}";

                continue;
            }

            $episodeSlug = $this->editorRepository->episodeIdFromFolder($targetFolder);
            $episodeNumber = (int) ($episodeManifest['episode_number'] ?? 0);
            $episodeId = null;

            if ($options['create_db'] ?? false) {
                $episodeId = $this->ensureEpisodeRecord(
                    $storyId,
                    $episodeNumber,
                    $episodeDir,
                    $episodeManifest,
                );
                $episodeIds[$episodeNumber] = $episodeId;
            }

            $promptsFile = $this->findEpisodeFile($episodeDir, '*_image_prompts.json');
            if ($promptsFile !== null) {
                try {
                    $import = $this->importService->importStoryFileFromPath(
                        $storySlug,
                        $promptsFile,
                        episodeSlug: $episodeSlug,
                        forceStoryId: $storyId,
                        forceEpisodeId: $episodeId,
                    );
                    $result['imports'][] = [
                        'type' => 'image_prompts',
                        'episode' => $episodeNumber,
                        'summary' => $import['summary'] ?? [],
                    ];
                    $episodeId = $episodeId ?? ($import['summary']['episode_id'] ?? null);
                } catch (\Throwable $e) {
                    $result['errors'][] = "prompts ep {$episodeNumber}: " . $e->getMessage();
                }
            }

            if ($hasScript) {
                $scriptFile = $this->findEpisodeFile($episodeDir, '*_story.md') ?? $this->findEpisodeFile($episodeDir, '*.md');
                if ($scriptFile !== null) {
                    try {
                        $import = $this->importService->importStoryFileFromPath(
                            $storySlug,
                            $scriptFile,
                            episodeSlug: $episodeSlug,
                            forceStoryId: $storyId,
                            forceEpisodeId: $episodeId,
                        );
                        $result['imports'][] = [
                            'type' => 'story_script',
                            'episode' => $episodeNumber,
                            'summary' => $import['summary'] ?? [],
                        ];
                    } catch (\Throwable $e) {
                        $result['errors'][] = "script ep {$episodeNumber}: " . $e->getMessage();
                    }
                }
            }
        }

        if ($storyId && ($options['create_db'] ?? false)) {
            Story::whereKey($storyId)->update([
                'total_episodes' => count($manifest['episodes'] ?? []),
            ]);
        }

        $result['status'] = $result['errors'] === [] ? 'imported' : 'imported_with_errors';

        return $result;
    }

    private function ensureStoryRecord(array $manifest, string $folderName, string $storyDirPath): int
    {
        $title = (string) ($manifest['story_title'] ?? $this->guessTitleFromFolder($folderName));
        $storyDirTitle = $title;
        $json = null;

        $jsonPath = rtrim($storyDirPath, '\\/') . DIRECTORY_SEPARATOR . 'characters_and_objects.json';
        if (is_file($jsonPath)) {
            $json = json_decode((string) file_get_contents($jsonPath), true);
            if (is_array($json)) {
                if (! empty($json['story_title'])) {
                    $storyDirTitle = $this->extractPersianTitle((string) $json['story_title']);
                }
            }
        }

        $existing = Story::where('title', $storyDirTitle)->first();
        if ($existing) {
            $updates = [];
            if (empty($existing->age_group)) {
                $updates['age_group'] = $this->resolveAgeGroup($json, $manifest);
            }
            $episodeCount = (int) ($manifest['total_episodes'] ?? 0);
            if ($episodeCount > 0 && (int) $existing->total_episodes < $episodeCount) {
                $updates['total_episodes'] = $episodeCount;
            }
            if ($updates !== []) {
                $existing->update($updates);
            }

            return $existing->id;
        }

        $category = Category::query()->first();
        if ($category === null) {
            throw new \RuntimeException('No categories in database — create at least one category before --create-db.');
        }

        $summary = $manifest['story_summary'] ?? null;
        if ((! is_string($summary) || $summary === '') && is_array($json)) {
            $summary = $json['story_summary'] ?? $json['summary'] ?? null;
        }
        $subtitle = is_string($summary) && $summary !== '' ? Str::limit($summary, 300, '…') : null;
        $description = Str::limit(is_string($summary) && $summary !== '' ? $summary : $storyDirTitle, 5000, '…');

        $story = Story::create([
            'title' => Str::limit($storyDirTitle, 200),
            'subtitle' => $subtitle,
            'description' => $description,
            'image_url' => 'stories/default.jpg',
            'category_id' => $category->id,
            'age_group' => $this->resolveAgeGroup($json, $manifest),
            'language' => 'fa',
            'duration' => 0,
            'total_episodes' => (int) ($manifest['total_episodes'] ?? 0),
            'free_episodes' => 0,
            'is_premium' => false,
            'is_completely_free' => false,
            'status' => 'draft',
            'workflow_status' => Story::WORKFLOW_WRITTEN,
        ]);

        return $story->id;
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @param  array<string, mixed>  $manifest
     */
    private function resolveAgeGroup(?array $json, array $manifest): string
    {
        $candidates = [
            is_array($json) ? ($json['target_age'] ?? null) : null,
            $manifest['target_age'] ?? null,
            $manifest['age_group'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 20, '');
            }
        }

        return '3-6';
    }

    /**
     * @param  array<string, mixed>  $episodeManifest
     */
    private function ensureEpisodeRecord(?int $storyId, int $episodeNumber, string $episodeDir, array $episodeManifest): ?int
    {
        if (! $storyId || $episodeNumber < 1) {
            return null;
        }

        $existing = Episode::where('story_id', $storyId)
            ->where('episode_number', $episodeNumber)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        $title = $this->episodeTitleFromDir($episodeDir, $episodeManifest);

        $episode = Episode::create([
            'story_id' => $storyId,
            'title' => $title,
            'description' => null,
            'audio_url' => null,
            'duration' => 0,
            'episode_number' => $episodeNumber,
            'is_premium' => false,
            'status' => 'draft',
            'use_image_timeline' => true,
        ]);

        return $episode->id;
    }

    /**
     * @param  array<string, mixed>  $episodeManifest
     */
    private function episodeTitleFromDir(string $episodeDir, array $episodeManifest): string
    {
        $promptsFile = $this->findEpisodeFile($episodeDir, '*_image_prompts.json');
        if ($promptsFile !== null) {
            $json = json_decode((string) file_get_contents($promptsFile), true);
            if (is_array($json) && ! empty($json['episode_title_persian'])) {
                return (string) $json['episode_title_persian'];
            }
        }

        $slug = (string) ($episodeManifest['episode_slug'] ?? 'episode');

        return Str::headline(str_replace('_', ' ', $slug));
    }

    private function findEpisodeFile(string $episodeDir, string $pattern): ?string
    {
        $matches = glob($episodeDir . DIRECTORY_SEPARATOR . $pattern) ?: [];

        return $matches[0] ?? null;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
            throw new \RuntimeException("Cannot create directory: {$destination}");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    private function extractPersianTitle(string $title): string
    {
        if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/u', $title, $matches)) {
            return trim($matches[1]);
        }

        return trim($title);
    }

    private function guessTitleFromFolder(string $folderName): string
    {
        if (preg_match('/^\d+\s*-\s*(.+)$/u', $folderName, $matches)) {
            return trim($matches[1]);
        }

        return $folderName;
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if ($path[0] === '/' || $path[0] === '\\' || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
