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

    public function test_parses_multiline_quoted_dialogue(): void
    {
        $markdown = <<<'MD'
# تست چندخطی
## قسمت ۱ از ۱

## اطلاعات داستان
- **رده سنی**: ۸–۱۲
- **مدت زمان تخمینی**: ۵ دقیقه
- **دسته‌بندی**: test
- **پیام اصلی**: پیام
- **شخصیت‌های این قسمت**: راوی

---

## شخصیت‌های حاضر در این قسمت

- **راوی** (narrator): راوی

---

## متن داستان

### صحنه ۱: یادآوری
*آبادی نور*

**راوی**: «آبادی نور بنا شده بود و مردم کنار هم زندگی می کردند . حالا در خانه پادشاه جوانی بزرگ شده بود که همه او را دوست داشتند: سیامک 

پسر بد مر او را یکی خوب‌روی

هنرمند و همچون پدر نامجوی

سیامک بُدش نام و فرخنده بود

کیومرث را دل بدو زنده بود»

---

### صحنه ۲: سیامک در میدان
*میدان*

**راوی**: «زن جنگاور که زنی جنگجو و شجاع بود مربی رزم و خرد سیامک شده بود و از تلاش سیامک برای یادگیری لذت می برد»

---

## خلاصه قسمت (Episode Summary)
خلاصه

## پیام آموزشی (Educational Message)
پیام

## هوک پایانی (Soft Hook)
**راوی** (آهسته و کنجکاوانه): «ادامه»

---
MD;

        $parsed = $this->service->parse($markdown);

        $this->assertCount(2, $parsed['scenes']);
        $this->assertCount(1, $parsed['scenes'][0]['dialogue_lines']);
        $this->assertSame('راوی', $parsed['scenes'][0]['dialogue_lines'][0]['speaker']);
        $this->assertStringContainsString("پسر بد مر او را یکی خوب‌روی", $parsed['scenes'][0]['dialogue_lines'][0]['text']);
        $this->assertStringContainsString("کیومرث را دل بدو زنده بود", $parsed['scenes'][0]['dialogue_lines'][0]['text']);
        $this->assertArrayNotHasKey('raw_unparsed', $parsed['scenes'][0]);

        $this->assertCount(1, $parsed['scenes'][1]['dialogue_lines']);
        $this->assertStringContainsString('زن جنگاور', $parsed['scenes'][1]['dialogue_lines'][0]['text']);

        $serialized = $this->service->serialize($parsed);
        $reparsed = $this->service->parse($serialized);

        $this->assertSame(
            $parsed['scenes'][0]['dialogue_lines'][0]['text'],
            $reparsed['scenes'][0]['dialogue_lines'][0]['text']
        );
    }

    public function test_recovers_raw_unparsed_multiline_into_dialogue_lines(): void
    {
        // Simulate legacy parse leftover: empty dialogue_lines + raw_unparsed poetry block.
        $service = new StoryMarkdownService();
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('recoverUnparsedSceneDialogue');
        $method->setAccessible(true);

        $episode = [
            'scenes' => [[
                'title' => 'یادآوری',
                'environment_description' => 'آبادی',
                'dialogue_lines' => [],
                'raw_unparsed' => "**راوی**: «خط اول\n\nپسر بد مر او را یکی خوب‌روی\n\nکیومرث را دل بدو زنده بود»",
            ]],
        ];

        $method->invokeArgs($service, [&$episode]);

        $this->assertCount(1, $episode['scenes'][0]['dialogue_lines']);
        $this->assertStringContainsString('پسر بد مر', $episode['scenes'][0]['dialogue_lines'][0]['text']);
        $this->assertArrayNotHasKey('raw_unparsed', $episode['scenes'][0]);
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
