<?php

namespace Tests\Unit\Services;

use App\Services\StoryMarkdownService;
use App\Services\StoryPackagePrepareService;
use App\Services\StoryPackagePreflightService;
use Tests\TestCase;

class StoryPackagePrepareAndPreflightTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'story_pkg_' . uniqid();
        mkdir($this->tempRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempRoot);
        parent::tearDown();
    }

    public function test_preflight_fails_when_required_files_missing(): void
    {
        $storyDir = $this->tempRoot . DIRECTORY_SEPARATOR . '99 - sample story';
        mkdir($storyDir, 0755, true);
        mkdir($storyDir . DIRECTORY_SEPARATOR . 'episode 1 - تست', 0755, true);

        $service = app(StoryPackagePreflightService::class);
        $result = $service->checkWriterFolder($storyDir);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['issues']);
        $this->assertTrue(collect($result['issues'])->contains(fn (string $i) => str_contains($i, 'characters_and_objects.json')));
    }

    public function test_preflight_warns_on_unknown_speakers(): void
    {
        $storyDir = $this->buildValidWriterFolder(includeUnknownSpeaker: true);

        $service = app(StoryPackagePreflightService::class);
        $result = $service->checkWriterFolder($storyDir);

        $this->assertTrue($result['ok']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertTrue(collect($result['warnings'])->contains(
            fn (string $w) => str_contains($w, 'ناشناس')
        ));
    }

    public function test_preflight_accepts_known_character_speakers(): void
    {
        $storyDir = $this->buildValidWriterFolder(includeUnknownSpeaker: false);

        $service = app(StoryPackagePreflightService::class);
        $result = $service->checkWriterFolder($storyDir);

        $this->assertTrue($result['ok']);
        $this->assertSame([], $result['issues']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_prepare_builds_manifest_and_normalized_episode_folders(): void
    {
        $storyDir = $this->buildValidWriterFolder(includeUnknownSpeaker: false);
        $staging = $this->tempRoot . DIRECTORY_SEPARATOR . 'staging';

        $service = app(StoryPackagePrepareService::class);
        $result = $service->prepareFromWriterFolder($storyDir, $staging, force: true);

        $this->assertTrue(is_dir($result['destination']));
        $this->assertSame(1, $result['episode_count']);
        $this->assertTrue($result['has_characters']);
        $this->assertFileExists($result['destination'] . DIRECTORY_SEPARATOR . 'import_manifest.json');
        $this->assertFileExists($result['destination'] . DIRECTORY_SEPARATOR . 'characters_and_objects.json');

        $manifest = json_decode(
            (string) file_get_contents($result['destination'] . DIRECTORY_SEPARATOR . 'import_manifest.json'),
            true
        );
        $this->assertIsArray($manifest);
        $this->assertSame(1, $manifest['total_episodes']);
        $this->assertSame('sample_story', $manifest['episodes'][0]['episode_slug']);
        $this->assertTrue(is_dir(
            $result['destination'] . DIRECTORY_SEPARATOR . $manifest['episodes'][0]['target_folder']
        ));
    }

    public function test_unknown_speakers_helper_normalizes_persian_letters(): void
    {
        $markdown = <<<'MD'
## متن داستان

### صحنه ۱: آغاز

**راوی**: «روزی روزگاری»

**گالیور**: «سلام»
MD;

        $service = new StoryPackagePreflightService(app(StoryMarkdownService::class));
        $unknown = $service->unknownSpeakers($markdown, ['راوی', 'گاليور']); // Arabic Yeh in allowed list

        $this->assertSame([], $unknown);
    }

    /**
     * @return string Absolute path to writer folder
     */
    private function buildValidWriterFolder(bool $includeUnknownSpeaker): string
    {
        $storyDir = $this->tempRoot . DIRECTORY_SEPARATOR . '99 - sample story';
        $episodeDir = $storyDir . DIRECTORY_SEPARATOR . 'episode 1 - قسمت اول';
        mkdir($episodeDir, 0755, true);

        $characters = [
            'story_title' => 'داستان نمونه (Sample Story)',
            'target_age' => '7-12',
            'story_summary' => 'خلاصه تست',
            'characters' => [
                'gulliver' => [
                    'name_persian' => 'گالیور',
                    'name_english' => 'Gulliver',
                ],
            ],
            'objects' => [],
            'settings' => [],
        ];
        file_put_contents(
            $storyDir . DIRECTORY_SEPARATOR . 'characters_and_objects.json',
            json_encode($characters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        $speakerLine = $includeUnknownSpeaker
            ? '**ناشناس**: «من کیستم؟»'
            : '**گالیور**: «سلام دنیا»';

        $md = <<<MD
# داستان نمونه

## متن داستان

### صحنه ۱: آغاز

*محیط تست*

**راوی**: «شروع داستان»

{$speakerLine}
MD;
        file_put_contents($episodeDir . DIRECTORY_SEPARATOR . 'sample_story_story.md', $md);

        $prompts = [
            'episode_number' => 1,
            'scenes' => [
                ['scene_number' => 1, 'prompt' => 'a test scene'],
            ],
        ];
        file_put_contents(
            $episodeDir . DIRECTORY_SEPARATOR . 'sample_story_image_prompts.json',
            json_encode($prompts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return $storyDir;
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
