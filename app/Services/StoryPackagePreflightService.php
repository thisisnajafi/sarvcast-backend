<?php

namespace App\Services;

/**
 * Validates a writer folder or staging package before remote upload.
 */
class StoryPackagePreflightService
{
    public function __construct(
        private readonly StoryMarkdownService $markdownService,
    ) {}

    /**
     * @return array{ok: bool, issues: list<string>, warnings: list<string>, folder: string}
     */
    public function checkWriterFolder(string $storyFolder): array
    {
        $storyFolder = $this->normalizeExistingDir($storyFolder);
        $issues = [];
        $warnings = [];
        $folder = basename($storyFolder);

        $charactersPath = $storyFolder . DIRECTORY_SEPARATOR . 'characters_and_objects.json';
        $characterKeys = [];
        $characterNames = [];

        if (! is_file($charactersPath)) {
            $issues[] = 'missing characters_and_objects.json';
        } else {
            $json = json_decode((string) file_get_contents($charactersPath), true);
            if (! is_array($json)) {
                $issues[] = 'characters_and_objects.json is invalid JSON';
            } else {
                $characters = $json['characters'] ?? [];
                if (! is_array($characters) || $characters === []) {
                    $issues[] = 'characters_and_objects.json has no characters';
                } else {
                    foreach ($characters as $key => $character) {
                        $characterKeys[] = (string) $key;
                        if (is_array($character)) {
                            foreach (['name_persian', 'name_english', 'name_en'] as $field) {
                                if (! empty($character[$field]) && is_string($character[$field])) {
                                    $characterNames[] = trim($character[$field]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $episodeDirs = [];
        foreach (scandir($storyFolder) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $storyFolder . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && preg_match('/^episode\s+\d+\b/u', $entry)) {
                $episodeDirs[] = $path;
            }
        }

        if ($episodeDirs === []) {
            $issues[] = "no episode folders (expected 'episode N - …')";
        }

        $allowedSpeakers = array_values(array_unique(array_filter(array_merge(
            $characterKeys,
            $characterNames,
            ['راوی', 'Narrator', 'narrator'],
        ))));

        foreach ($episodeDirs as $episodeDir) {
            $name = basename($episodeDir);
            $mdFiles = array_values(array_filter(
                glob($episodeDir . DIRECTORY_SEPARATOR . '*.md') ?: [],
                'is_file'
            ));
            $promptFiles = array_values(array_filter(
                array_merge(
                    glob($episodeDir . DIRECTORY_SEPARATOR . '*_image_prompts.json') ?: [],
                    glob($episodeDir . DIRECTORY_SEPARATOR . '*prompts*.json') ?: [],
                ),
                'is_file'
            ));

            if ($mdFiles === []) {
                $issues[] = "episode '{$name}': missing .md script";
            }
            if ($promptFiles === []) {
                $issues[] = "episode '{$name}': missing *_image_prompts.json";
            }

            if ($mdFiles !== [] && $allowedSpeakers !== []) {
                $unknown = $this->unknownSpeakers((string) file_get_contents($mdFiles[0]), $allowedSpeakers);
                foreach ($unknown as $speaker) {
                    $warnings[] = "episode '{$name}': speaker '{$speaker}' not found in characters_and_objects.json";
                }
            }
        }

        return [
            'ok' => $issues === [],
            'issues' => $issues,
            'warnings' => $warnings,
            'folder' => $folder,
        ];
    }

    /**
     * @param  list<string>  $allowedSpeakers
     * @return list<string>
     */
    public function unknownSpeakers(string $markdown, array $allowedSpeakers): array
    {
        $parsed = $this->markdownService->parse($markdown);
        $allowedNormalized = [];
        foreach ($allowedSpeakers as $speaker) {
            $allowedNormalized[$this->normalizeSpeaker($speaker)] = true;
        }

        $unknown = [];
        foreach ($parsed['scenes'] ?? [] as $scene) {
            foreach ($scene['dialogue_lines'] ?? [] as $line) {
                $speaker = trim((string) ($line['speaker'] ?? ''));
                if ($speaker === '') {
                    continue;
                }
                $key = $this->normalizeSpeaker($speaker);
                if (! isset($allowedNormalized[$key])) {
                    $unknown[$speaker] = true;
                }
            }
        }

        return array_keys($unknown);
    }

    private function normalizeSpeaker(string $speaker): string
    {
        $speaker = trim(mb_strtolower($speaker));
        $speaker = str_replace(['ي', 'ك'], ['ی', 'ک'], $speaker);

        return preg_replace('/\s+/u', ' ', $speaker) ?? $speaker;
    }

    private function normalizeExistingDir(string $path): string
    {
        if ($path !== '' && $path[0] !== '/' && $path[0] !== '\\' && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = base_path($path);
        }
        if (! is_dir($path)) {
            throw new \RuntimeException("Story folder not found: {$path}");
        }

        return realpath($path) ?: $path;
    }
}
