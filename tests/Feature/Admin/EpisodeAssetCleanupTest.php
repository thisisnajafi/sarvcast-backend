<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Models\Story;
use App\Models\StoryProductionFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EpisodeAssetCleanupTest extends TestCase
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

        $category = Category::factory()->create();
        $this->story = Story::factory()->create(['category_id' => $category->id]);
    }

    public function test_deleting_episode_removes_script_timeline_and_audio_files(): void
    {
        Sanctum::actingAs($this->admin);

        $scriptPath = 'episodes/scripts/test-episode.md';
        $audioPath = 'audio/episodes/test-episode.mp3';
        $timelineImagePath = 'media/timeline/test-frame.jpg';

        Storage::disk('public')->put($scriptPath, '# script');
        Storage::disk('public')->put($timelineImagePath, 'image-bytes');

        $publicAudioDir = public_path('audio/episodes');
        if (! is_dir($publicAudioDir)) {
            mkdir($publicAudioDir, 0755, true);
        }
        file_put_contents(public_path($audioPath), 'audio-bytes');

        $episode = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'Episode cleanup test',
            'audio_url' => $audioPath,
            'duration' => 5,
            'episode_number' => 1,
            'status' => 'draft',
            'script_file_url' => Storage::url($scriptPath),
        ]);

        ImageTimeline::create([
            'episode_id' => $episode->id,
            'story_id' => $this->story->id,
            'start_time' => 0,
            'end_time' => 30,
            'image_url' => Storage::url($timelineImagePath),
            'image_order' => 1,
            'transition_type' => 'fade',
            'is_key_frame' => false,
        ]);

        $response = $this->deleteJson("/api/admin/episodes/{$episode->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);
        $this->assertDatabaseMissing('image_timelines', ['episode_id' => $episode->id]);
        Storage::disk('public')->assertMissing($scriptPath);
        Storage::disk('public')->assertMissing($timelineImagePath);
        $this->assertFileDoesNotExist(public_path($audioPath));
    }

    public function test_admin_can_delete_episode_script_without_deleting_episode(): void
    {
        Sanctum::actingAs($this->admin);

        $scriptPath = 'episodes/scripts/only-script.md';
        Storage::disk('public')->put($scriptPath, '# only script');

        $episode = Episode::create([
            'story_id' => $this->story->id,
            'title' => 'Script delete test',
            'audio_url' => 'audio/episodes/keep-me.mp3',
            'duration' => 5,
            'episode_number' => 2,
            'status' => 'draft',
            'script_file_url' => Storage::url($scriptPath),
        ]);

        StoryProductionFile::create([
            'story_slug' => 'test-story',
            'episode_slug' => 'ep-1',
            'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
            'original_filename' => 'ep-1_story.md',
            'storage_path' => $scriptPath,
            'story_id' => $this->story->id,
            'episode_id' => $episode->id,
            'episode_number' => 2,
            'imported_at' => now(),
        ]);

        $response = $this->deleteJson("/api/admin/episodes/{$episode->id}/script");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $episode->refresh();
        $this->assertNull($episode->script_file_url);
        $this->assertDatabaseMissing('story_production_files', [
            'episode_id' => $episode->id,
            'file_type' => StoryProductionFile::TYPE_STORY_SCRIPT,
        ]);
        Storage::disk('public')->assertMissing($scriptPath);
        $this->assertDatabaseHas('episodes', ['id' => $episode->id]);
    }
}
