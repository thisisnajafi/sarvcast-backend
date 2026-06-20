<?php

namespace App\Services;

use App\Support\PersianNumerals;

class StoryMarkdownService
{
    private const NARRATOR = 'راوی';

    public function parse(string $rawMarkdown): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $rawMarkdown);
        $content = trim($content);

        $result = [
            'metadata' => [
                'title_persian' => '',
                'episode_number' => 0,
                'total_episodes' => 0,
                'age_range' => '',
                'duration_estimate' => '',
                'genre_tags' => [],
                'main_message' => '',
            ],
            'characters' => [],
            'scenes' => [],
            'closing' => [
                'episode_summary' => '',
                'educational_message' => '',
                'is_final_episode' => false,
                'soft_hook_text' => '',
            ],
        ];

        if ($content === '') {
            return $result;
        }

        $this->parseTitleAndEpisode($content, $result);
        $this->parseMetadataSection($content, $result);
        $this->parseCharactersSection($content, $result);
        $this->parseScenesSection($content, $result);
        $this->parseClosingSection($content, $result);

        return $result;
    }

    public function serialize(array $structuredEpisode): string
    {
        $metadata = $structuredEpisode['metadata'] ?? [];
        $characters = $structuredEpisode['characters'] ?? [];
        $scenes = $structuredEpisode['scenes'] ?? [];
        $closing = $structuredEpisode['closing'] ?? [];

        $title = trim((string) ($metadata['title_persian'] ?? ''));
        $episodeNumber = (int) ($metadata['episode_number'] ?? 0);
        $totalEpisodes = (int) ($metadata['total_episodes'] ?? 0);
        $ageRange = trim((string) ($metadata['age_range'] ?? ''));
        $duration = trim((string) ($metadata['duration_estimate'] ?? ''));
        $genreTags = $metadata['genre_tags'] ?? [];
        $mainMessage = trim((string) ($metadata['main_message'] ?? ''));
        $isFinal = (bool) ($closing['is_final_episode'] ?? false);
        $softHook = trim((string) ($closing['soft_hook_text'] ?? ''));

        $lines = [];
        $lines[] = '---';
        $lines[] = '# ' . $title;
        $lines[] = '## قسمت ' . PersianNumerals::toPersian($episodeNumber) . ' از ' . PersianNumerals::toPersian($totalEpisodes);
        $lines[] = '';
        $lines[] = '## اطلاعات داستان';
        $lines[] = '- **رده سنی**: ' . $ageRange;
        $lines[] = '- **مدت زمان تخمینی**: ' . $duration;
        $lines[] = '- **دسته‌بندی**: ' . $this->formatGenreTags($genreTags);
        $lines[] = '- **پیام اصلی**: ' . $mainMessage;
        $lines[] = '- **شخصیت‌های این قسمت**: ' . $this->formatEpisodeCharacterNames($characters);
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '## شخصیت‌های حاضر در این قسمت';
        $lines[] = '';

        foreach ($characters as $character) {
            $name = trim((string) ($character['name_persian'] ?? ''));
            $id = trim((string) ($character['character_id'] ?? ''));
            $description = trim((string) ($character['description'] ?? ''));
            $lines[] = '- **' . $name . '** (' . $id . '): ' . $description;
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '## متن داستان';
        $lines[] = '';

        $sceneIndex = 0;
        foreach ($scenes as $scene) {
            $sceneIndex++;
            $sceneTitle = trim((string) ($scene['title'] ?? ''));
            $environment = trim((string) ($scene['environment_description'] ?? ''));
            $dialogueLines = $scene['dialogue_lines'] ?? [];

            $lines[] = '### صحنه ' . PersianNumerals::toPersian($sceneIndex) . ': ' . $sceneTitle;
            if ($environment !== '') {
                $lines[] = '*' . $environment . '*';
            }
            $lines[] = '';

            foreach ($dialogueLines as $line) {
                $speaker = trim((string) ($line['speaker'] ?? ''));
                $emotion = $line['emotion_tag'] ?? null;
                $text = trim((string) ($line['text'] ?? ''));

                if ($speaker === self::NARRATOR) {
                    if ($emotion !== null && trim((string) $emotion) !== '') {
                        $lines[] = '**' . self::NARRATOR . '** (' . trim((string) $emotion) . '): «' . $text . '»';
                    } else {
                        $lines[] = '**' . self::NARRATOR . '**: «' . $text . '»';
                    }
                } else {
                    $emotionPart = ($emotion !== null && trim((string) $emotion) !== '')
                        ? ' (' . trim((string) $emotion) . ')'
                        : '';
                    $lines[] = '**' . $speaker . '**' . $emotionPart . ': «' . $text . '»';
                }
                $lines[] = '';
            }

            $lines[] = '---';
            $lines[] = '';
        }

        $lines[] = '## خلاصه قسمت (Episode Summary)';
        $lines[] = trim((string) ($closing['episode_summary'] ?? ''));
        $lines[] = '';
        $lines[] = '## پیام آموزشی (Educational Message)';
        $lines[] = trim((string) ($closing['educational_message'] ?? ''));
        $lines[] = '';

        if ($isFinal) {
            $lines[] = '## پایان (Closed Ending)';
            if ($softHook !== '') {
                $lines[] = '**' . self::NARRATOR . '** (آرام و گرم): «' . $softHook . '»';
            }
        } else {
            $lines[] = '## هوک پایانی (Soft Hook)';
            if ($softHook !== '') {
                $lines[] = '**' . self::NARRATOR . '** (آهسته و کنجکاوانه): «' . $softHook . '»';
            }
        }

        $lines[] = '';
        $lines[] = '---';

        return implode("\n", $lines) . "\n";
    }

    private function parseTitleAndEpisode(string $content, array &$result): void
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            $result['metadata']['title_persian'] = trim($matches[1]);
        }

        if (preg_match('/##\s*قسمت\s+([۰-۹٠-٩\d]+)\s+از\s+([۰-۹٠-٩\d]+)/u', $content, $matches)) {
            $result['metadata']['episode_number'] = PersianNumerals::parseInt($matches[1]);
            $result['metadata']['total_episodes'] = PersianNumerals::parseInt($matches[2]);
        }
    }

    private function parseMetadataSection(string $content, array &$result): void
    {
        if (!preg_match('/##\s*اطلاعات داستان\s*\n(.*?)(?=\n---|\n##\s)/su', $content, $matches)) {
            return;
        }

        $section = $matches[1];

        if (preg_match('/\*\*رده سنی\*\*:\s*(.+)/u', $section, $m)) {
            $result['metadata']['age_range'] = trim($m[1]);
        }
        if (preg_match('/\*\*مدت زمان تخمینی\*\*:\s*(.+)/u', $section, $m)) {
            $result['metadata']['duration_estimate'] = trim($m[1]);
        }
        if (preg_match('/\*\*دسته‌بندی\*\*:\s*(.+)/u', $section, $m)) {
            $result['metadata']['genre_tags'] = $this->splitGenreTags(trim($m[1]));
        }
        if (preg_match('/\*\*پیام اصلی\*\*:\s*(.+)/u', $section, $m)) {
            $result['metadata']['main_message'] = trim($m[1]);
        }
    }

    private function parseCharactersSection(string $content, array &$result): void
    {
        if (!preg_match('/##\s*شخصیت‌های حاضر در این قسمت\s*\n(.*?)(?=\n---|\n##\s*متن داستان)/su', $content, $matches)) {
            return;
        }

        $section = trim($matches[1]);
        if ($section === '') {
            return;
        }

        $rawLines = [];
        foreach (preg_split('/\n+/', $section) as $line) {
            $line = trim($line);
            if ($line === '' || !str_starts_with($line, '-')) {
                continue;
            }

            $parsed = $this->parseCharacterLine($line);
            if ($parsed !== null) {
                foreach ($parsed as $character) {
                    $result['characters'][] = $character;
                }
            } else {
                $rawLines[] = $line;
            }
        }

        if ($rawLines !== []) {
            $result['characters_raw_unparsed'] = implode("\n", $rawLines);
        }
    }

    /**
     * @return array<int, array{name_persian: string, character_id: string, description: string}>|null
     */
    private function parseCharacterLine(string $line): ?array
    {
        if (preg_match('/^-\s*\*\*(.+?)\*\*\s*\(([^)]+)\)\s*:\s*(.+)$/u', $line, $matches)) {
            return [[
                'name_persian' => trim($matches[1]),
                'character_id' => trim($matches[2]),
                'description' => trim($matches[3]),
            ]];
        }

        if (preg_match('/^-\s*\*\*(.+?)\*\*\s*\(([^)]+)\)\s+و\s+\*\*(.+?)\*\*\s*\(([^)]+)\)\s*:\s*(.+)$/u', $line, $matches)) {
            $description = trim($matches[5]);

            return [
                [
                    'name_persian' => trim($matches[1]),
                    'character_id' => trim($matches[2]),
                    'description' => $description,
                ],
                [
                    'name_persian' => trim($matches[3]),
                    'character_id' => trim($matches[4]),
                    'description' => $description,
                ],
            ];
        }

        return null;
    }

    private function parseScenesSection(string $content, array &$result): void
    {
        if (!preg_match('/##\s*متن داستان\s*\n(.*)$/su', $content, $matches)) {
            return;
        }

        $storyBody = $matches[1];
        $closingPattern = '/\n##\s*خلاصه قسمت/su';
        if (preg_match($closingPattern, $storyBody, $closingMatch, PREG_OFFSET_CAPTURE)) {
            $storyBody = substr($storyBody, 0, $closingMatch[0][1]);
        }

        $chunks = preg_split('/\n(?=###\s*صحنه\s+)/u', trim($storyBody));
        if ($chunks === false) {
            $result['scenes_raw_unparsed'] = trim($storyBody);

            return;
        }

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            $scene = $this->parseSceneChunk($chunk);
            if ($scene !== null) {
                $result['scenes'][] = $scene;
            } else {
                $result['scenes_raw_unparsed'] = trim(
                    ($result['scenes_raw_unparsed'] ?? '') . "\n" . $chunk
                );
            }
        }
    }

    private function parseSceneChunk(string $chunk): ?array
    {
        if (!preg_match('/^###\s*صحنه\s+([۰-۹٠-٩\d]+)\s*:\s*(.+?)\n(.*)$/su', $chunk, $matches)) {
            return null;
        }

        $sceneNumber = PersianNumerals::parseInt($matches[1]);
        $title = trim($matches[2]);
        $body = trim($matches[3]);

        $environment = '';
        $dialogueBody = $body;

        if (preg_match('/^\*(.+?)\*\s*\n?(.*)$/su', $body, $envMatches)) {
            $environment = trim($envMatches[1]);
            $dialogueBody = trim($envMatches[2]);
        }

        $dialogueBody = trim(preg_replace('/^---+$/m', '', $dialogueBody) ?? $dialogueBody);

        $dialogueLines = [];
        $rawDialogue = [];

        foreach (preg_split('/\n+/', $dialogueBody) as $line) {
            $line = trim($line);
            if ($line === '' || $line === '---') {
                continue;
            }

            $parsedLine = $this->parseDialogueLine($line);
            if ($parsedLine !== null) {
                $dialogueLines[] = $parsedLine;
            } else {
                $rawDialogue[] = $line;
            }
        }

        $scene = [
            'scene_number' => $sceneNumber,
            'title' => $title,
            'environment_description' => $environment,
            'dialogue_lines' => $dialogueLines,
        ];

        if ($rawDialogue !== []) {
            $scene['raw_unparsed'] = implode("\n", $rawDialogue);
        }

        return $scene;
    }

    private function parseDialogueLine(string $line): ?array
    {
        if (preg_match('/^\*\*' . preg_quote(self::NARRATOR, '/') . '\*\*\s*\(([^)]+)\)\s*:\s*[«"](.+)[»"]\s*$/u', $line, $matches)) {
            return [
                'speaker' => self::NARRATOR,
                'emotion_tag' => trim($matches[1]),
                'text' => trim($matches[2]),
            ];
        }

        if (preg_match('/^\*\*' . preg_quote(self::NARRATOR, '/') . '\*\*\s*:\s*[«"](.+)[»"]\s*$/u', $line, $matches)) {
            return [
                'speaker' => self::NARRATOR,
                'emotion_tag' => null,
                'text' => trim($matches[1]),
            ];
        }

        if (preg_match('/^\*\*(.+?)\*\*\s*\(([^)]+)\)\s*:\s*[«"](.+)[»"]\s*$/u', $line, $matches)) {
            return [
                'speaker' => trim($matches[1]),
                'emotion_tag' => trim($matches[2]),
                'text' => trim($matches[3]),
            ];
        }

        if (preg_match('/^\*\*(.+?)\*\*\s*:\s*[«"](.+)[»"]\s*$/u', $line, $matches)) {
            return [
                'speaker' => trim($matches[1]),
                'emotion_tag' => null,
                'text' => trim($matches[2]),
            ];
        }

        return null;
    }

    private function parseClosingSection(string $content, array &$result): void
    {
        if (preg_match('/##\s*خلاصه قسمت \(Episode Summary\)\s*\n(.*?)(?=\n##\s|\z)/su', $content, $matches)) {
            $result['closing']['episode_summary'] = trim($matches[1]);
        }

        if (preg_match('/##\s*پیام آموزشی \(Educational Message\)\s*\n(.*?)(?=\n##\s|\z)/su', $content, $matches)) {
            $result['closing']['educational_message'] = trim($matches[1]);
        }

        if (preg_match('/##\s*هوک پایانی \(Soft Hook\)\s*\n(.*?)(?=\n---|\z)/su', $content, $matches)) {
            $result['closing']['is_final_episode'] = false;
            $result['closing']['soft_hook_text'] = $this->extractHookText(trim($matches[1]));
        } elseif (preg_match('/##\s*پایان \(Closed Ending\)\s*\n(.*?)(?=\n---|\z)/su', $content, $matches)) {
            $result['closing']['is_final_episode'] = true;
            $result['closing']['soft_hook_text'] = $this->extractHookText(trim($matches[1]));
        }
    }

    private function extractHookText(string $section): string
    {
        if (preg_match('/^\*\*' . preg_quote(self::NARRATOR, '/') . '\*\*.*?:\s*[«"](.+)[»"]\s*$/u', $section, $matches)) {
            return trim($matches[1]);
        }

        return $section;
    }

    /**
     * @param  array<int, string>|string  $tags
     */
    private function formatGenreTags(array|string $tags): string
    {
        if (is_string($tags)) {
            return trim($tags);
        }

        return implode(', ', array_filter(array_map('trim', $tags)));
    }

    /**
     * @param  array<int, array{name_persian?: string}>  $characters
     */
    private function formatEpisodeCharacterNames(array $characters): string
    {
        $names = [];
        foreach ($characters as $character) {
            $name = trim((string) ($character['name_persian'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return implode('، ', $names);
    }

    /**
     * @return array<int, string>
     */
    private function splitGenreTags(string $value): array
    {
        $parts = preg_split('/\s*,\s*/u', $value) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }
}
