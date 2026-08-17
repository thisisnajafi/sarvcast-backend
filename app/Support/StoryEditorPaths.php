<?php

namespace App\Support;

class StoryEditorPaths
{
    /**
     * Resolve the manji-stories directory (env → config default → discovery list).
     *
     * @throws \RuntimeException
     */
    public static function resolve(): string
    {
        $candidates = [];

        $fromEnv = config('story_editor.stories_path');
        if (is_string($fromEnv) && $fromEnv !== '') {
            $candidates[] = $fromEnv;
        }

        $fromConfig = config('story_editor.default_stories_path');
        if (is_string($fromConfig) && $fromConfig !== '') {
            $candidates[] = $fromConfig;
        }

        foreach (config('story_editor.discovery_paths', []) as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $candidates[] = self::normalizePath($path);
        }

        foreach (array_unique($candidates) as $path) {
            if (is_dir($path)) {
                return realpath($path) ?: $path;
            }
        }

        $storageDefault = config('story_editor.default_stories_path');
        if (is_string($storageDefault) && $storageDefault !== '') {
            self::ensureDirectory($storageDefault);

            if (is_dir($storageDefault)) {
                return realpath($storageDefault) ?: $storageDefault;
            }
        }

        throw new \RuntimeException(
            'Stories directory not found. Expected storage path: '
            . (is_string($storageDefault) ? $storageDefault : storage_path('app/manji-stories'))
        );
    }

    private static function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        @mkdir($path, 0755, true);
    }

    private static function normalizePath(string $path): string
    {
        if (self::isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * Roots that may be read/written for story-editor files (includes sibling manji-stories).
     *
     * @return list<string>
     */
    public static function allowedFilesystemRoots(): array
    {
        $roots = [
            storage_path(),
            public_path(),
            base_path(),
        ];

        try {
            $stories = self::resolve();
            if (is_string($stories) && $stories !== '') {
                $roots[] = $stories;
            }
        } catch (\Throwable) {
            // Stories directory may not exist yet in some test/deploy setups.
        }

        return array_values(array_unique(array_filter($roots)));
    }

    public static function isInsideAllowedRoot(string $path): bool
    {
        if ($path === '' || str_contains($path, '://')) {
            return false;
        }

        $normalized = str_replace('\\', '/', $path);
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $normalized) !== 1
            && ! str_starts_with($normalized, '/')
            && ! str_starts_with($normalized, '\\')) {
            return false;
        }

        $real = realpath($path);
        if (is_string($real) && $real !== '') {
            $normalized = str_replace('\\', '/', $real);
        }

        foreach (self::allowedFilesystemRoots() as $root) {
            $rootReal = realpath($root) ?: $root;
            $rootNorm = rtrim(str_replace('\\', '/', $rootReal), '/');
            if ($normalized === $rootNorm || str_starts_with($normalized, $rootNorm.'/')) {
                return true;
            }
        }

        return false;
    }

    private static function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
