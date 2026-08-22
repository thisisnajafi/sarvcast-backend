<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Story;
use App\Models\StoryProductionFile;
use App\Models\User;
use App\Services\StoryDeletionService;
use App\Services\StoryEditorRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoryDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Story $story;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Test category',
            'slug' => 'test-category',
            'description' => 'Test',
            'color' => '#00BCD4',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->story = Story::create([
            'title' => 'Story to delete',
            'description' => 'Test story',
            'image_url' => 'images/stories/test.jpg',
            'category_id' => $category->id,
            'age_group' => 'all',
            'duration' => 10,
            'status' => 'draft',
            'script_file_url' => Storage::url('stories/scripts/story-level.md'),
        ]);

        Storage::disk('public')->put('stories/scripts/story-level.md', '# story script');
    }

    public function test_story_deletion_service_removes_episodes_characters_and_script_files(): void
    {
        $scriptPath = 'episodes/scripts/story-delete-test.md';
        Storage::disk('public')->put($scriptPath, '# episode script');

        $episode = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'Episode to delete',
            'audio_url' => 'audio/episodes/story-delete-test.mp3',
            'duration' => 5,
            'episode_number' => 1,
            'status' => 'draft',
            'script_file_url' => Storage::url($scriptPath),
        ]);

        $character = Character::create([
            'story_id' => $this->story->id,
            'name' => 'Hero',
            'image_url' => Storage::url('characters/hero.png'),
        ]);
        Storage::disk('public')->put('characters/hero.png', 'png-bytes');

        StoryProductionFile::create([
            'story_slug' => 'delete-me-story',
            'episode_slug' => 'ep-1',
            'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
            'original_filename' => 'ep-1_story.md',
            'storage_path' => $scriptPath,
            'story_id' => $this->story->id,
            'episode_id' => $episode->id,
            'episode_number' => 1,
            'imported_at' => now(),
        ]);

        app(StoryDeletionService::class)->delete($this->story);

        $this->assertDatabaseMissing('stories', ['id' => $this->story->id]);
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);
        $this->assertDatabaseMissing('characters', ['id' => $character->id]);
        $this->assertDatabaseMissing('story_production_files', ['story_id' => $this->story->id]);
        Storage::disk('public')->assertMissing($scriptPath);
        Storage::disk('public')->assertMissing('stories/scripts/story-level.md');
        Storage::disk('public')->assertMissing('characters/hero.png');
    }

    public function test_admin_api_destroy_deletes_story_and_related_records(): void
    {
        Sanctum::actingAs($this->admin);

        $episode = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'API delete episode',
            'audio_url' => 'audio/episodes/api-delete.mp3',
            'duration' => 4,
            'episode_number' => 1,
            'status' => 'draft',
        ]);

        Character::create([
            'story_id' => $this->story->id,
            'name' => 'Sidekick',
        ]);

        $response = $this->deleteJson('/api/admin/stories/'.$this->story->id);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('stories', ['id' => $this->story->id]);
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);
        $this->assertDatabaseMissing('characters', ['story_id' => $this->story->id]);
    }

    public function test_admin_bulk_delete_uses_full_story_cleanup(): void
    {
        Sanctum::actingAs($this->admin);

        $scriptPath = 'episodes/scripts/bulk-delete.md';
        Storage::disk('public')->put($scriptPath, '# bulk delete');

        $episode = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'Bulk delete episode',
            'audio_url' => 'audio/episodes/bulk-delete.mp3',
            'duration' => 3,
            'episode_number' => 1,
            'status' => 'draft',
            'script_file_url' => Storage::url($scriptPath),
        ]);

        $response = $this->postJson('/api/admin/stories/bulk-action', [
            'action' => 'delete',
            'story_ids' => [$this->story->id],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('stories', ['id' => $this->story->id]);
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);
        Storage::disk('public')->assertMissing($scriptPath);
    }

    public function test_story_deletion_removes_story_editor_directory(): void
    {
        $storySlug = '99 - حذف داستان تست';
        $storyDir = storage_path('app/manji-stories/'.$storySlug);
        if (is_dir($storyDir)) {
            $this->deleteDirectoryForTest($storyDir);
        }

        $episodeDir = $storyDir.'/1 - قسمت اول';
        mkdir($episodeDir, 0755, true);
        file_put_contents($episodeDir.'/1 - قسمت اول_story.md', '# markdown script');

        StoryProductionFile::create([
            'story_slug' => $storySlug,
            'episode_slug' => '1 - قسمت اول',
            'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
            'original_filename' => '1 - قسمت اول_story.md',
            'storage_path' => $episodeDir.'/1 - قسمت اول_story.md',
            'story_id' => $this->story->id,
            'episode_number' => 1,
            'imported_at' => now(),
        ]);

        $this->assertDirectoryExists($storyDir);

        app(StoryDeletionService::class)->delete($this->story);

        $this->assertDirectoryDoesNotExist($storyDir);
        $this->assertNull(app(StoryEditorRepository::class)->findStoryDirectory($storySlug));
    }

    private function deleteDirectoryForTest(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($path)) {
                $this->deleteDirectoryForTest($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
