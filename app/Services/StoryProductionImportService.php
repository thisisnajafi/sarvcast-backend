<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Story;
use App\Models\StoryProductionAsset;
use App\Models\StoryProductionFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoryProductionImportService
{
    public function __construct(
        private readonly StoryEditorRepository $editorRepository,
        private readonly StoryMarkdownService $markdownService,
        private readonly MediaLibraryService $mediaLibrary,
    ) {}

    /**
     * @return array{file_type: string, story_slug: string, episode_slug: string|null, summary: array}
     */
    public function detectFileType(string $filename, string $content): array
    {
        $lower = strtolower($filename);

        if (str_ends_with($lower, '.md')) {
            return ['file_type' => StoryProductionFile::TYPE_STORY_SCRIPT];
        }

        $json = json_decode($content, true);
        if (! is_array($json)) {
            throw new \InvalidArgumentException('فایل JSON معتبر نیست.');
        }

        if (isset($json['characters']) || isset($json['objects']) || isset($json['settings'])) {
            return ['file_type' => StoryProductionFile::TYPE_CHARACTERS];
        }

        if (isset($json['scenes']) || isset($json['cover_image'])) {
            return ['file_type' => StoryProductionFile::TYPE_IMAGE_PROMPTS];
        }

        throw new \InvalidArgumentException('نوع فایل شناسایی نشد. فایل باید characters_and_objects، image_prompts یا story.md باشد.');
    }

    /**
     * @return array<string, mixed>
     */
    public function importStoryFile(
        string $storySlug,
        UploadedFile $file,
        ?string $episodeSlug = null,
        ?int $forceStoryId = null,
        ?int $forceEpisodeId = null,
    ): array {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new \InvalidArgumentException('فایل معتبر نیست.');
        }

        return $this->importStoryFileFromPath(
            $storySlug,
            $path,
            $file->getClientOriginalName(),
            $episodeSlug,
            $forceStoryId,
            $forceEpisodeId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function importStoryFileFromPath(
        string $storySlug,
        string $absolutePath,
        ?string $originalFilename = null,
        ?string $episodeSlug = null,
        ?int $forceStoryId = null,
        ?int $forceEpisodeId = null,
    ): array {
        if (! is_file($absolutePath)) {
            throw new \InvalidArgumentException("File not found: {$absolutePath}");
        }

        $filename = $originalFilename ?? basename($absolutePath);
        $file = new UploadedFile(
            $absolutePath,
            $filename,
            mime_content_type($absolutePath) ?: 'application/octet-stream',
            null,
            true,
        );

        return $this->importStoryFileInternal(
            $storySlug,
            $file,
            file_get_contents($absolutePath),
            $episodeSlug,
            $forceStoryId,
            $forceEpisodeId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function importStoryFileInternal(
        string $storySlug,
        UploadedFile $file,
        string|false $content,
        ?string $episodeSlug = null,
        ?int $forceStoryId = null,
        ?int $forceEpisodeId = null,
    ): array {
        $storyDir = $this->editorRepository->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            throw new \RuntimeException('داستان یافت نشد.');
        }

        if (! is_string($content) || $content === '') {
            throw new \InvalidArgumentException('فایل خالی است.');
        }

        $detected = $this->detectFileType($file->getClientOriginalName(), $content);
        $fileType = $detected['file_type'];

        if ($fileType === StoryProductionFile::TYPE_STORY_SCRIPT && $episodeSlug === null) {
            $episodeSlug = $this->inferEpisodeSlugFromScript($storyDir, $content);
        }

        if ($fileType === StoryProductionFile::TYPE_IMAGE_PROMPTS && $episodeSlug === null) {
            $episodeSlug = $this->inferEpisodeSlugFromPrompts($storyDir, $content);
        }

        if (in_array($fileType, [StoryProductionFile::TYPE_STORY_SCRIPT, StoryProductionFile::TYPE_IMAGE_PROMPTS], true) && $episodeSlug === null) {
            throw new \InvalidArgumentException('برای فایل قسمت، پوشه اپیزود مشخص نیست.');
        }

        if ($episodeSlug !== null && $this->editorRepository->findEpisodeDirectory($storyDir, $episodeSlug) === null) {
            throw new \InvalidArgumentException('پوشه قسمت یافت نشد. ابتدا قسمت را در storage ایجاد کنید.');
        }

        return DB::transaction(function () use ($storySlug, $episodeSlug, $file, $content, $fileType, $storyDir, $forceStoryId, $forceEpisodeId) {
            $storagePath = $this->storeFile($storySlug, $episodeSlug, $fileType, $file, $content);
            $sourcePath = $this->mirrorToSourceRepo($storyDir, $episodeSlug, $fileType, $file, $content);

            $summary = match ($fileType) {
                StoryProductionFile::TYPE_CHARACTERS => $this->importCharactersAndObjects($storySlug, $content),
                StoryProductionFile::TYPE_IMAGE_PROMPTS => $this->importImagePrompts($storySlug, $episodeSlug, $content),
                StoryProductionFile::TYPE_STORY_SCRIPT => $this->importStoryScript($storySlug, $episodeSlug, $content),
            };

            $storyId = $forceStoryId ?? $this->resolveDbStoryId($storySlug, $summary);
            $episodeId = null;
            $episodeNumber = $summary['episode_number'] ?? null;

            if ($episodeSlug !== null) {
                $episodeId = $forceEpisodeId ?? $this->resolveDbEpisodeId($storyId, $storySlug, $episodeSlug, $summary);
            }

            $record = StoryProductionFile::updateOrCreate(
                [
                    'story_slug' => $storySlug,
                    'episode_slug' => $episodeSlug,
                    'file_type' => $fileType,
                ],
                [
                    'original_filename' => $file->getClientOriginalName(),
                    'storage_path' => $storagePath,
                    'source_path' => $sourcePath,
                    'story_id' => $storyId,
                    'episode_id' => $episodeId,
                    'episode_number' => $episodeNumber,
                    'parsed_summary' => $summary,
                    'content_hash' => hash('sha256', $content),
                    'imported_at' => now(),
                ],
            );

            if ($fileType === StoryProductionFile::TYPE_STORY_SCRIPT && $episodeId) {
                Episode::whereKey($episodeId)->update([
                    'script_file_url' => Storage::url($storagePath),
                ]);
            }

            return [
                'file' => $record->fresh(),
                'summary' => $summary,
                'storage_url' => Storage::url($storagePath),
                'source_path' => $sourcePath,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getPackageOverview(string $storySlug): array
    {
        $storyDir = $this->editorRepository->findStoryDirectory($storySlug);
        if ($storyDir === null) {
            throw new \RuntimeException('داستان یافت نشد.');
        }

        $meta = $this->readStoryMetaFromDir($storyDir);
        $episodes = $this->editorRepository->listEpisodes($storySlug);

        $files = StoryProductionFile::where('story_slug', $storySlug)->get();
        $assets = StoryProductionAsset::where('story_slug', $storySlug)->get();

        $storyFile = $files->firstWhere('file_type', StoryProductionFile::TYPE_CHARACTERS);
        $dbStoryId = $storyFile?->story_id ?? $this->resolveDbStoryId($storySlug, $meta);

        $characterAssets = $assets->where('asset_type', StoryProductionAsset::TYPE_CHARACTER)->values();
        $objectAssets = $assets->where('asset_type', StoryProductionAsset::TYPE_OBJECT)->values();
        $settingAssets = $assets->where('asset_type', StoryProductionAsset::TYPE_SETTING)->values();

        $episodePackages = [];
        foreach ($episodes as $ep) {
            $epSlug = $ep['id'];
            $epFiles = $files->where('episode_slug', $epSlug);
            $epAssets = $assets->where('episode_slug', $epSlug);

            $episodePackages[] = [
                'id' => $epSlug,
                'episode_number' => $ep['episode_number'],
                'title_persian' => $ep['title_persian'],
                'last_modified' => $ep['last_modified'],
                'files' => [
                    'story_script' => $epFiles->firstWhere('file_type', StoryProductionFile::TYPE_STORY_SCRIPT),
                    'image_prompts' => $epFiles->firstWhere('file_type', StoryProductionFile::TYPE_IMAGE_PROMPTS),
                ],
                'scene_count' => $epAssets->where('asset_type', StoryProductionAsset::TYPE_SCENE)->count(),
                'cover_uploaded' => $epAssets->where('asset_type', StoryProductionAsset::TYPE_COVER)->contains(
                    fn ($a) => ! empty($a->image_url)
                ),
                'scenes_with_images' => $epAssets
                    ->where('asset_type', StoryProductionAsset::TYPE_SCENE)
                    ->filter(fn ($a) => ! empty($a->image_url))
                    ->count(),
            ];
        }

        return [
            'story_slug' => $storySlug,
            'meta' => $meta,
            'linked_story_id' => $dbStoryId,
            'characters_and_objects' => $storyFile,
            'asset_counts' => [
                'characters' => $characterAssets->count(),
                'objects' => $objectAssets->count(),
                'settings' => $settingAssets->count(),
                'characters_with_images' => $characterAssets->filter(fn ($a) => ! empty($a->image_url))->count(),
                'objects_with_images' => $objectAssets->filter(fn ($a) => ! empty($a->image_url))->count(),
                'settings_with_images' => $settingAssets->filter(fn ($a) => ! empty($a->image_url))->count(),
            ],
            'episodes' => $episodePackages,
        ];
    }

    public function resolveStorySlugByDbStoryId(int $storyId): ?string
    {
        return $this->editorRepository->findStorySlugByDbStoryId($storyId);
    }

    /**
     * @return array<string, mixed>
     */
    public function uploadAssetImage(
        string $storySlug,
        string $assetType,
        string $assetKey,
        UploadedFile $image,
        ?string $episodeSlug = null,
    ): array {
        $mediaAsset = $this->mediaLibrary->upload($image, [
            'folder' => 'production',
            'title' => $assetKey,
            'uploaded_by' => auth()->id(),
        ]);

        return $this->assignAssetImageUrl($storySlug, $assetType, $assetKey, $mediaAsset->url, $episodeSlug);
    }

    /**
     * @return array<string, mixed>
     */
    public function assignAssetImageUrl(
        string $storySlug,
        string $assetType,
        string $assetKey,
        string $imageUrl,
        ?string $episodeSlug = null,
    ): array {
        $asset = StoryProductionAsset::where('story_slug', $storySlug)
            ->where('episode_slug', $episodeSlug)
            ->where('asset_type', $assetType)
            ->where('asset_key', $assetKey)
            ->first();

        if ($asset === null) {
            throw new \RuntimeException('دارایی یافت نشد. ابتدا فایل JSON مربوطه را import کنید.');
        }

        $mediaAsset = $this->mediaLibrary->findByUrl($imageUrl);
        $storagePath = $mediaAsset?->path;

        $asset->update([
            'image_url' => $imageUrl,
            'storage_path' => $storagePath,
        ]);

        $this->mediaLibrary->syncUsageFor($asset->fresh(), 'image_url', $imageUrl);

        if ($assetType === StoryProductionAsset::TYPE_CHARACTER && $asset->character_id) {
            Character::whereKey($asset->character_id)->update(['image_url' => $imageUrl]);
        }

        return [
            'asset' => $asset->fresh(),
            'image_url' => $imageUrl,
        ];
    }

  /**
     * @return array<string, mixed>
     */
    private function importCharactersAndObjects(string $storySlug, string $content): array
    {
        $data = json_decode($content, true);
        if (! is_array($data)) {
            throw new \InvalidArgumentException('JSON نامعتبر است.');
        }

        $storyId = $this->resolveDbStoryId($storySlug, [
            'title_persian' => $this->extractPersianTitle((string) ($data['story_title'] ?? '')),
        ]);

        foreach ($data['characters'] ?? [] as $key => $character) {
            $this->upsertAsset($storySlug, null, StoryProductionAsset::TYPE_CHARACTER, $key, [
                'name_persian' => $character['name_persian'] ?? $key,
                'name_english' => $character['name_english'] ?? null,
                'prompt' => $character['character_sheet_prompt'] ?? null,
                'metadata' => $character,
                'story_id' => $storyId,
            ]);

            if ($storyId) {
                $this->syncCharacterRecord($storyId, $storySlug, $key, $character);
            }
        }

        foreach ($data['objects'] ?? [] as $key => $object) {
            $this->upsertAsset($storySlug, null, StoryProductionAsset::TYPE_OBJECT, $key, [
                'name_persian' => $object['name_persian'] ?? $key,
                'name_english' => $object['name_english'] ?? null,
                'prompt' => $object['object_prompt'] ?? null,
                'metadata' => $object,
                'story_id' => $storyId,
            ]);
        }

        foreach ($data['settings'] ?? [] as $key => $setting) {
            $this->upsertAsset($storySlug, null, StoryProductionAsset::TYPE_SETTING, $key, [
                'name_persian' => $setting['name_persian'] ?? $key,
                'name_english' => $setting['name_english'] ?? null,
                'prompt' => $setting['setting_prompt_base'] ?? null,
                'metadata' => $setting,
                'story_id' => $storyId,
            ]);
        }

        if ($storyId) {
            Story::whereKey($storyId)->update([
                'age_group' => $data['target_age'] ?? null,
                'total_episodes' => $data['total_episodes'] ?? null,
                'workflow_status' => Story::WORKFLOW_CHARACTERS_MADE,
            ]);
        }

        return [
            'title' => $data['story_title'] ?? null,
            'target_age' => $data['target_age'] ?? null,
            'total_episodes' => $data['total_episodes'] ?? null,
            'character_count' => count($data['characters'] ?? []),
            'object_count' => count($data['objects'] ?? []),
            'setting_count' => count($data['settings'] ?? []),
            'story_id' => $storyId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importImagePrompts(string $storySlug, ?string $episodeSlug, string $content): array
    {
        $data = json_decode($content, true);
        if (! is_array($data)) {
            throw new \InvalidArgumentException('JSON نامعتبر است.');
        }

        $episodeNumber = (int) ($data['episode_number'] ?? 0);
        $storyId = $this->resolveDbStoryId($storySlug, []);
        $episodeId = $this->resolveDbEpisodeId($storyId, $storySlug, $episodeSlug, [
            'episode_number' => $episodeNumber,
            'title_persian' => $data['episode_title_persian'] ?? null,
        ]);

        if (! empty($data['cover_image']) && is_array($data['cover_image'])) {
            $cover = $data['cover_image'];
            $this->upsertAsset($storySlug, $episodeSlug, StoryProductionAsset::TYPE_COVER, 'cover', [
                'name_persian' => $cover['title'] ?? 'کاور',
                'name_english' => null,
                'prompt' => $cover['image_prompt'] ?? null,
                'metadata' => $cover,
                'story_id' => $storyId,
                'episode_id' => $episodeId,
            ]);
        }

        foreach ($data['scenes'] ?? [] as $scene) {
            $key = $scene['prompt_id'] ?? ($scene['scene_reference'] ?? Str::uuid()->toString());
            $this->upsertAsset($storySlug, $episodeSlug, StoryProductionAsset::TYPE_SCENE, $key, [
                'name_persian' => $scene['scene_title_persian'] ?? $key,
                'name_english' => $scene['scene_title_english'] ?? null,
                'prompt' => $scene['image_prompt'] ?? null,
                'metadata' => $scene,
                'story_id' => $storyId,
                'episode_id' => $episodeId,
            ]);
        }

        return [
            'episode_number' => $episodeNumber,
            'title_persian' => $data['episode_title_persian'] ?? null,
            'scene_count' => count($data['scenes'] ?? []),
            'has_cover' => ! empty($data['cover_image']),
            'episode_id' => $episodeId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importStoryScript(string $storySlug, ?string $episodeSlug, string $content): array
    {
        $parsed = $this->markdownService->parse($content);
        $storyId = $this->resolveDbStoryId($storySlug, [
            'title_persian' => $parsed['metadata']['title_persian'] ?? null,
        ]);
        $episodeId = $this->resolveDbEpisodeId($storyId, $storySlug, $episodeSlug, [
            'episode_number' => $parsed['metadata']['episode_number'] ?? null,
            'title_persian' => $parsed['metadata']['title_persian'] ?? null,
        ]);

        if ($episodeId) {
            Episode::whereKey($episodeId)->update([
                'title' => $parsed['metadata']['title_persian'] ?? Episode::find($episodeId)?->title,
                'description' => $parsed['closing']['episode_summary'] ?? null,
            ]);
        }

        return [
            'title_persian' => $parsed['metadata']['title_persian'] ?? null,
            'episode_number' => $parsed['metadata']['episode_number'] ?? null,
            'scene_count' => count($parsed['scenes'] ?? []),
            'character_count' => count($parsed['characters'] ?? []),
            'episode_id' => $episodeId,
            'story_id' => $storyId,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAssets(string $storySlug, ?string $episodeSlug = null, ?string $assetType = null): array
    {
        $query = StoryProductionAsset::where('story_slug', $storySlug);

        if ($episodeSlug !== null) {
            $query->where('episode_slug', $episodeSlug);
        } else {
            $query->whereNull('episode_slug');
        }

        if ($assetType !== null) {
            $query->where('asset_type', $assetType);
        }

        return $query->orderBy('asset_type')->orderBy('asset_key')->get()->map(function (StoryProductionAsset $asset) {
            return [
                'id' => $asset->id,
                'asset_type' => $asset->asset_type,
                'asset_key' => $asset->asset_key,
                'name_persian' => $asset->name_persian,
                'name_english' => $asset->name_english,
                'prompt' => $asset->prompt,
                'image_url' => $asset->image_url ? $asset->getImageUrlFromPath($asset->image_url) : null,
                'has_image' => ! empty($asset->image_url),
                'episode_slug' => $asset->episode_slug,
                'character_id' => $asset->character_id,
                'metadata' => $asset->metadata,
            ];
        })->all();
    }

    private function storeFile(
        string $storySlug,
        ?string $episodeSlug,
        string $fileType,
        UploadedFile $file,
        string $content,
    ): string {
        $filename = $this->canonicalFilename($fileType, $file->getClientOriginalName(), $episodeSlug);
        $directory = $episodeSlug
            ? "stories/production/{$storySlug}/episodes/{$episodeSlug}"
            : "stories/production/{$storySlug}";

        $path = "{$directory}/{$filename}";
        Storage::disk('public')->put($path, $content);

        return $path;
    }

    private function mirrorToSourceRepo(
        string $storyDir,
        ?string $episodeSlug,
        string $fileType,
        UploadedFile $file,
        string $content,
    ): ?string {
        try {
            $targetDir = $episodeSlug
                ? $this->editorRepository->findEpisodeDirectory($storyDir, $episodeSlug)
                : $storyDir;

            if ($targetDir === null) {
                return null;
            }

            $filename = match ($fileType) {
                StoryProductionFile::TYPE_CHARACTERS => 'characters_and_objects.json',
                StoryProductionFile::TYPE_IMAGE_PROMPTS => $this->guessImagePromptsFilename($targetDir),
                StoryProductionFile::TYPE_STORY_SCRIPT => $this->guessStoryMdFilename($targetDir),
                default => $file->getClientOriginalName(),
            };

            $fullPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($fullPath, $content);

            return $fullPath;
        } catch (\Throwable) {
            return null;
        }
    }

    private function canonicalFilename(string $fileType, string $original, ?string $episodeSlug): string
    {
        return match ($fileType) {
            StoryProductionFile::TYPE_CHARACTERS => 'characters_and_objects.json',
            StoryProductionFile::TYPE_IMAGE_PROMPTS => ($episodeSlug ?? 'episode') . '_image_prompts.json',
            StoryProductionFile::TYPE_STORY_SCRIPT => ($episodeSlug ?? 'episode') . '_story.md',
            default => $original,
        };
    }

    private function guessImagePromptsFilename(string $episodeDir): string
    {
        foreach (glob($episodeDir . '/*_image_prompts.json') ?: [] as $path) {
            return basename($path);
        }

        return basename($episodeDir) . '_image_prompts.json';
    }

    private function guessStoryMdFilename(string $episodeDir): string
    {
        foreach (glob($episodeDir . '/*_story.md') ?: [] as $path) {
            return basename($path);
        }
        foreach (glob($episodeDir . '/*.md') ?: [] as $path) {
            return basename($path);
        }

        return basename($episodeDir) . '_story.md';
    }

    private function inferEpisodeSlugFromScript(string $storyDir, string $content): ?string
    {
        $parsed = $this->markdownService->parse($content);
        $num = (int) ($parsed['metadata']['episode_number'] ?? 0);

        return $this->findEpisodeSlugByNumber($storyDir, $num);
    }

    private function inferEpisodeSlugFromPrompts(string $storyDir, string $content): ?string
    {
        $data = json_decode($content, true);
        $num = (int) ($data['episode_number'] ?? 0);

        return $this->findEpisodeSlugByNumber($storyDir, $num);
    }

    private function findEpisodeSlugByNumber(string $storyDir, int $episodeNumber): ?string
    {
        if ($episodeNumber < 1) {
            return null;
        }

        foreach (glob($storyDir . '/episode*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (preg_match('/episode[_\s-]*(\d+)/i', basename($dir), $m) && (int) $m[1] === $episodeNumber) {
                return $this->editorRepository->episodeIdFromFolder(basename($dir));
            }
        }

        return null;
    }

    private function resolveDbStoryId(string $storySlug, array $summary): ?int
    {
        $existing = StoryProductionFile::where('story_slug', $storySlug)
            ->whereNotNull('story_id')
            ->value('story_id');

        if ($existing) {
            return (int) $existing;
        }

        $title = $summary['title_persian'] ?? $this->extractPersianTitle((string) ($summary['title'] ?? ''));
        if ($title) {
            $story = Story::where('title', $title)->first();
            if ($story) {
                return $story->id;
            }
        }

        $folderName = $this->editorRepository->findStoryDirectory($storySlug);
        if ($folderName) {
            $meta = $this->readStoryMetaFromDir($folderName);
            $story = Story::where('title', $meta['name_persian'])->first();
            if ($story) {
                return $story->id;
            }
        }

        return null;
    }

    private function resolveDbEpisodeId(?int $storyId, string $storySlug, ?string $episodeSlug, array $summary): ?int
    {
        if ($episodeSlug === null) {
            return null;
        }

        $existing = StoryProductionFile::where('story_slug', $storySlug)
            ->where('episode_slug', $episodeSlug)
            ->whereNotNull('episode_id')
            ->value('episode_id');

        if ($existing) {
            return (int) $existing;
        }

        if (! $storyId) {
            return null;
        }

        $episodeNumber = (int) ($summary['episode_number'] ?? 0);
        if ($episodeNumber > 0) {
            $episode = Episode::where('story_id', $storyId)
                ->where('episode_number', $episodeNumber)
                ->first();
            if ($episode) {
                return $episode->id;
            }
        }

        $title = $summary['title_persian'] ?? null;
        if ($title) {
            $episode = Episode::where('story_id', $storyId)->where('title', $title)->first();
            if ($episode) {
                return $episode->id;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertAsset(
        string $storySlug,
        ?string $episodeSlug,
        string $assetType,
        string $assetKey,
        array $payload,
    ): StoryProductionAsset {
        return StoryProductionAsset::updateOrCreate(
            [
                'story_slug' => $storySlug,
                'episode_slug' => $episodeSlug,
                'asset_type' => $assetType,
                'asset_key' => $assetKey,
            ],
            $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $character
     */
    private function syncCharacterRecord(int $storyId, string $storySlug, string $key, array $character): void
    {
        $name = $character['name_persian'] ?? $key;
        $description = $character['visual_description_persian']
            ?? $character['arc_summary']
            ?? null;

        $record = Character::firstOrCreate(
            ['story_id' => $storyId, 'name' => $name],
            ['description' => $description],
        );

        if ($description && ! $record->description) {
            $record->update(['description' => $description]);
        }

        StoryProductionAsset::where('story_slug', $storySlug)
            ->where('asset_type', StoryProductionAsset::TYPE_CHARACTER)
            ->where('asset_key', $key)
            ->update(['character_id' => $record->id, 'story_id' => $storyId]);
    }

    /**
     * @return array{name_persian: string, name_english: string, target_age: string|null}
     */
    private function readStoryMetaFromDir(string $storyDir): array
    {
        $jsonPath = $storyDir . '/characters_and_objects.json';
        if (! is_file($jsonPath)) {
            return [
                'name_persian' => basename($storyDir),
                'name_english' => '',
                'target_age' => null,
            ];
        }

        $json = json_decode((string) file_get_contents($jsonPath), true);
        $title = (string) ($json['story_title'] ?? '');
        $persian = $this->extractPersianTitle($title);

        return [
            'name_persian' => $persian,
            'name_english' => $title,
            'target_age' => $json['target_age'] ?? null,
        ];
    }

    private function extractPersianTitle(string $title): string
    {
        if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/u', $title, $matches)) {
            return trim($matches[1]);
        }

        return trim($title);
    }
}
