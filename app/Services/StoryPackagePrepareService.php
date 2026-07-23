<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Builds a staging package (import_manifest.json + normalized episode folders)
 * from a raw writer folder under manji-stories/.
 */
class StoryPackagePrepareService
{
    /**
     * @return array{destination: string, folder_name: string, manifest: array<string, mixed>, has_characters: bool, episode_count: int}
     */
    public function prepareFromWriterFolder(string $storyFolder, string $stagingRoot, bool $force = false): array
    {
        $storyFolder = $this->normalizeExistingDir($storyFolder);
        $stagingRoot = $this->normalizeDir($stagingRoot, create: true);
        $folderName = basename($storyFolder);
        $destination = rtrim($stagingRoot, '\\/') . DIRECTORY_SEPARATOR . $folderName;

        if (is_dir($destination)) {
            if (! $force) {
                throw new \RuntimeException("Staging package already exists: {$destination} (pass force=true to overwrite)");
            }
            $this->deleteDirectory($destination);
        }

        if (! mkdir($destination, 0755, true) && ! is_dir($destination)) {
            throw new \RuntimeException("Could not create staging directory: {$destination}");
        }

        foreach (scandir($storyFolder) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $from = $storyFolder . DIRECTORY_SEPARATOR . $entry;
            if (is_file($from)) {
                copy($from, $destination . DIRECTORY_SEPARATOR . $entry);
            }
        }

        $englishHint = $folderName;
        if (preg_match('/^\d+\s*-\s*(.+)$/u', $folderName, $m)) {
            $englishHint = trim($m[1]);
        }
        $storySlugBase = $this->slug($englishHint);

        $episodes = [];
        foreach (scandir($storyFolder) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $from = $storyFolder . DIRECTORY_SEPARATOR . $entry;
            if (! is_dir($from) || ! preg_match('/^episode\s+(\d+)\b/u', $entry, $em)) {
                continue;
            }

            $num = (int) $em[1];
            $persianTitle = $entry;
            if (preg_match('/^episode\s+\d+\s*-\s*(.+)$/u', $entry, $tm)) {
                $persianTitle = trim($tm[1]);
            }

            $targetFolder = sprintf('episode_%d_%s', $num, $storySlugBase);
            $targetPath = $destination . DIRECTORY_SEPARATOR . $targetFolder;
            mkdir($targetPath, 0755, true);

            foreach (scandir($from) ?: [] as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $srcFile = $from . DIRECTORY_SEPARATOR . $file;
                if (is_file($srcFile)) {
                    copy($srcFile, $targetPath . DIRECTORY_SEPARATOR . $file);
                }
            }

            $hasScript = $this->hasGlob($targetPath, '*_story.md') || $this->hasGlob($targetPath, '*.md');
            $hasPrompts = $this->hasGlob($targetPath, '*_image_prompts.json')
                || $this->hasGlob($targetPath, '*prompts*.json');

            $episodes[] = [
                'episode_number' => $num,
                'episode_slug' => $storySlugBase,
                'source_folder' => $entry,
                'target_folder' => $targetFolder,
                'has_script' => $hasScript,
                'has_prompts' => $hasPrompts,
                'needs_script' => ! $hasScript,
                'title_hint' => $persianTitle,
            ];
        }

        usort($episodes, fn (array $a, array $b) => $a['episode_number'] <=> $b['episode_number']);

        $manifest = [
            'story_title' => $englishHint,
            'story_summary' => null,
            'total_episodes' => count($episodes),
            'episodes' => $episodes,
        ];

        $manifestPath = $destination . DIRECTORY_SEPARATOR . 'import_manifest.json';
        file_put_contents(
            $manifestPath,
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
        );

        return [
            'destination' => $destination,
            'folder_name' => $folderName,
            'manifest' => $manifest,
            'has_characters' => is_file($destination . DIRECTORY_SEPARATOR . 'characters_and_objects.json'),
            'episode_count' => count($episodes),
        ];
    }

    private function slug(string $text): string
    {
        $slug = Str::slug($text, '_');
        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($text)) ?: 'episode';
            $slug = trim($slug, '_');
        }

        return $slug !== '' ? $slug : 'episode';
    }

    private function hasGlob(string $dir, string $pattern): bool
    {
        $matches = glob(rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $pattern) ?: [];

        return $matches !== [];
    }

    private function normalizeExistingDir(string $path): string
    {
        $path = $this->normalizePath($path);
        if (! is_dir($path)) {
            throw new \RuntimeException("Story folder not found: {$path}");
        }

        return realpath($path) ?: $path;
    }

    private function normalizeDir(string $path, bool $create = false): string
    {
        $path = $this->normalizePath($path);
        if (! is_dir($path)) {
            if (! $create) {
                throw new \RuntimeException("Directory not found: {$path}");
            }
            mkdir($path, 0755, true);
        }

        return realpath($path) ?: $path;
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

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
