<?php

namespace Tests\Unit;

use App\Services\StoryMarkdownService;
use Tests\TestCase;

class StoryMarkdownServiceTest extends TestCase
{
    private StoryMarkdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StoryMarkdownService();
    }

    /**
     * @return array<int, string>
     */
    private function fixtureFiles(): array
    {
        return [
            base_path('tests/fixtures/story_markdown/episode_1.md'),
            base_path('tests/fixtures/story_markdown/episode_4.md'),
            base_path('tests/fixtures/story_markdown/episode_10_final.md'),
        ];
    }

    public function test_round_trip_preserves_structure_for_real_fixtures(): void
    {
        foreach ($this->fixtureFiles() as $fixture) {
            $this->assertFileExists($fixture, "Missing fixture: {$fixture}");

            $original = file_get_contents($fixture);
            $this->assertIsString($original);

            $parsedOnce = $this->service->parse($original);
            $serialized = $this->service->serialize($parsedOnce);
            $parsedTwice = $this->service->parse($serialized);

            $this->assertSame(
                $this->normalizeStructure($parsedOnce),
                $this->normalizeStructure($parsedTwice),
                'Round-trip mismatch for fixture: ' . basename($fixture)
            );
        }
    }

    public function test_parses_final_episode_without_soft_hook(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/story_markdown/episode_10_final.md'));
        $parsed = $this->service->parse($content);

        $this->assertTrue($parsed['closing']['is_final_episode']);
        $this->assertNotEmpty($parsed['closing']['soft_hook_text']);
    }

    public function test_parses_persian_scene_numbers(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/story_markdown/episode_1.md'));
        $parsed = $this->service->parse($content);

        $this->assertGreaterThan(0, count($parsed['scenes']));
        $this->assertSame(1, $parsed['scenes'][0]['scene_number']);
        $this->assertSame('سرزمین تازی', $parsed['scenes'][0]['title']);
    }

    public function test_serialize_renumbers_scenes_in_order(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/story_markdown/episode_1.md'));
        $parsed = $this->service->parse($content);

        $reordered = $parsed;
        $reordered['scenes'] = array_reverse($parsed['scenes']);
        $serialized = $this->service->serialize($reordered);

        $this->assertStringContainsString('### صحنه ۱: ' . $reordered['scenes'][0]['title'], $serialized);
        $this->assertStringContainsString('### صحنه ' . $this->persian(count($reordered['scenes'])) . ': ' . $reordered['scenes'][count($reordered['scenes']) - 1]['title'], $serialized);
    }

    private function persian(int $n): string
    {
        return strtr((string) $n, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    private function normalizeStructure(array $data): array
    {
        $normalized = [
            'metadata' => $data['metadata'] ?? [],
            'characters' => $data['characters'] ?? [],
            'scenes' => [],
            'closing' => $data['closing'] ?? [],
        ];

        foreach ($normalized['metadata']['genre_tags'] ?? [] as $index => $tag) {
            $normalized['metadata']['genre_tags'][$index] = trim((string) $tag);
        }

        foreach ($data['scenes'] ?? [] as $index => $scene) {
            $normalized['scenes'][$index] = [
                'title' => trim((string) ($scene['title'] ?? '')),
                'environment_description' => trim((string) ($scene['environment_description'] ?? '')),
                'dialogue_lines' => array_map(function (array $line): array {
                    return [
                        'speaker' => trim((string) ($line['speaker'] ?? '')),
                        'emotion_tag' => $line['emotion_tag'] !== null ? trim((string) $line['emotion_tag']) : null,
                        'text' => trim((string) ($line['text'] ?? '')),
                    ];
                }, $scene['dialogue_lines'] ?? []),
            ];
        }

        foreach ($normalized['closing'] as $key => $value) {
            if (is_string($value)) {
                $normalized['closing'][$key] = trim($value);
            }
        }

        return $normalized;
    }
}
