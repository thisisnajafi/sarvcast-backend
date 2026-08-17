<?php

namespace Tests\Feature\Admin;

use App\Models\Episode;
use App\Models\Story;
use App\Models\StoryProductionFile;
use App\Models\User;
use App\Services\EpisodeEditorSyncService;
use App\Services\StoryEditorRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EpisodeEditorSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $storiesRoot;

    private User $admin;

    private Story $story;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storiesRoot = storage_path('app/testing-manji-stories-'.uniqid());
        mkdir($this->storiesRoot, 0755, true);
        config(['story_editor.stories_path' => $this->storiesRoot]);

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category '.uniqid(),
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->story = Story::query()->create([
            'title' => 'داستان همگام‌سازی',
            'description' => 'توضیحات داستان تست',
            'image_url' => 'https://example.com/cover.webp',
            'category_id' => $categoryId,
            'age_group' => '7+',
            'language' => 'fa',
            'duration' => 10,
            'status' => 'draft',
        ]);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->storiesRoot);
        parent::tearDown();
    }

    public function test_reorder_swaps_script_folders_with_the_episodes(): void
    {
        Sanctum::actingAs($this->admin);

        [$storySlug, $first, $second] = $this->seedLinkedEpisodes();

        $this->postJson('/api/admin/episodes/reorder', [
            'story_id' => $this->story->id,
            'episodes' => [
                ['id' => $first->id, 'episode_number' => 2],
                ['id' => $second->id, 'episode_number' => 1],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(2, $first->fresh()->episode_number);
        $this->assertSame(1, $second->fresh()->episode_number);

        $repo = app(StoryEditorRepository::class);
        $episodes = $repo->listEpisodes($storySlug);
        $byNumber = collect($episodes)->keyBy('episode_number');

        $this->assertStringContainsString('اول', (string) $byNumber[2]['title_persian']);
        $this->assertStringContainsString('دوم', (string) $byNumber[1]['title_persian']);

        $firstMd = file_get_contents($repo->getEpisode($storySlug, $byNumber[2]['id'])['file_path']);
        $this->assertStringContainsString('متن قسمت اول', $firstMd);
    }

    public function test_deleting_episode_removes_editor_folder(): void
    {
        Sanctum::actingAs($this->admin);

        [$storySlug, $first] = $this->seedLinkedEpisodes();
        $storyDir = app(StoryEditorRepository::class)->findStoryDirectory($storySlug);
        $this->assertNotNull($storyDir);
        $this->assertDirectoryExists($storyDir.DIRECTORY_SEPARATOR.'episode_1_first');

        $this->deleteJson('/api/admin/episodes/'.$first->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDirectoryDoesNotExist($storyDir.DIRECTORY_SEPARATOR.'episode_1_first');
        $this->assertDirectoryExists($storyDir.DIRECTORY_SEPARATOR.'episode_2_second');
    }

    public function test_creating_episode_writes_stub_markdown_when_story_is_linked(): void
    {
        $storyDir = $this->storiesRoot.DIRECTORY_SEPARATOR.'1 - داستان همگام‌سازی';
        $storySlug = app(StoryEditorRepository::class)->storyIdFromFolder('1 - داستان همگام‌سازی');
        mkdir($storyDir, 0755, true);
        StoryProductionFile::query()->create([
            'story_slug' => $storySlug,
            'episode_slug' => 'placeholder',
            'file_type' => StoryProductionFile::TYPE_CHARACTERS,
            'original_filename' => 'characters_and_objects.json',
            'storage_path' => 'stories/production/test/characters.json',
            'story_id' => $this->story->id,
        ]);

        $episode = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'قسمت تازه',
            'audio_url' => 'audio/episodes/test.mp3',
            'duration' => 60,
            'episode_number' => 1,
            'status' => 'draft',
        ]);

        app(EpisodeEditorSyncService::class)->ensureEpisodeScaffold($episode);

        $repo = app(StoryEditorRepository::class);
        $listed = $repo->listEpisodes($repo->findLinkedStorySlug($this->story->id));
        $this->assertCount(1, $listed);
        $this->assertSame(1, $listed[0]['episode_number']);
        $this->assertFileExists($listed[0]['file_path']);
    }

    /**
     * @return array{0: string, 1: Episode, 2?: Episode}
     */
    private function seedLinkedEpisodes(): array
    {
        $folder = '1 - داستان همگام‌سازی';
        $storyDir = $this->storiesRoot.DIRECTORY_SEPARATOR.$folder;
        mkdir($storyDir, 0755, true);

        $storySlug = app(StoryEditorRepository::class)->storyIdFromFolder($folder);

        $firstDir = $storyDir.DIRECTORY_SEPARATOR.'episode_1_first';
        $secondDir = $storyDir.DIRECTORY_SEPARATOR.'episode_2_second';
        mkdir($firstDir, 0755, true);
        mkdir($secondDir, 0755, true);
        file_put_contents($firstDir.DIRECTORY_SEPARATOR.'first_story.md', "# قسمت اول\n## قسمت ۱ از ۲\n\nمتن قسمت اول\n");
        file_put_contents($secondDir.DIRECTORY_SEPARATOR.'second_story.md', "# قسمت دوم\n## قسمت ۲ از ۲\n\nمتن قسمت دوم\n");

        $first = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'قسمت اول',
            'audio_url' => 'audio/episodes/a.mp3',
            'duration' => 60,
            'episode_number' => 1,
            'status' => 'draft',
        ]);
        $second = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'قسمت دوم',
            'audio_url' => 'audio/episodes/b.mp3',
            'duration' => 60,
            'episode_number' => 2,
            'status' => 'draft',
        ]);

        StoryProductionFile::query()->create([
            'story_slug' => $storySlug,
            'episode_slug' => 'episode_1_first',
            'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
            'original_filename' => 'first_story.md',
            'storage_path' => 'stories/production/test/episode_1.md',
            'story_id' => $this->story->id,
            'episode_id' => $first->id,
            'episode_number' => 1,
            'source_path' => $firstDir.DIRECTORY_SEPARATOR.'first_story.md',
        ]);
        StoryProductionFile::query()->create([
            'story_slug' => $storySlug,
            'episode_slug' => 'episode_2_second',
            'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
            'original_filename' => 'second_story.md',
            'storage_path' => 'stories/production/test/episode_2.md',
            'story_id' => $this->story->id,
            'episode_id' => $second->id,
            'episode_number' => 2,
            'source_path' => $secondDir.DIRECTORY_SEPARATOR.'second_story.md',
        ]);

        return [$storySlug, $first, $second];
    }

    private function deleteDir(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($directory);
    }
}
