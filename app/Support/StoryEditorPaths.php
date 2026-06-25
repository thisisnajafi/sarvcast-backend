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

        throw new \RuntimeException(
            'Stories directory not found. Set STORY_EDITOR_STORIES_PATH in .env '
            . 'or default_stories_path in config/story_editor.php, then upload sarvcast-stories to that path.'
        );
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
