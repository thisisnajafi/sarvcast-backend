<?php

namespace App\Support;

class StoryEditorPaths
{
    /**
     * Resolve the sarvcast-stories directory (env → config default → discovery list).
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
            . (is_string($storageDefault) ? $storageDefault : storage_path('app/sarvcast-stories'))
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
