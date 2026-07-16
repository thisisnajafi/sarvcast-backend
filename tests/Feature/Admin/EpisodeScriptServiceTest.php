<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Story;
use App\Models\User;
use App\Services\EpisodeScriptService;
use App\Services\StoryEditorRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EpisodeScriptServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_for_episode_prefers_story_editor_markdown_over_stale_upload(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();
        $story = Story::factory()->create(['category_id' => $category->id]);
        $episode = Episode::create([
            'story_id' => $story->id,
            'title' => 'Episode 1',
            'audio_url' => 'audio/episodes/test.mp3',
            'duration' => 120,
            'episode_number' => 1,
            'status' => 'draft',
        ]);

        $stalePath = 'episodes/scripts/stale.md';
        Storage::disk('public')->put($stalePath, '# stale script');
        $episode->update(['script_file_url' => Storage::url($stalePath)]);

        $storySlug = 'test-story';
        $episodeSlug = 'episode_1_test';
        $canonicalPath = storage_path('app/testing-canonical-script.md');
        file_put_contents($canonicalPath, '# updated script');

        $repository = $this->mock(StoryEditorRepository::class);
        $repository->shouldReceive('findStorySlugByDbStoryId')
            ->with($story->id)
            ->andReturn($storySlug);
        $repository->shouldReceive('listEpisodes')
            ->with($storySlug)
            ->andReturn([
                [
                    'id' => $episodeSlug,
                    'episode_number' => 1,
                    'title_persian' => 'Episode 1',
                    'file_path' => $canonicalPath,
                    'last_modified' => now()->toIso8601String(),
                ],
            ]);
        $repository->shouldReceive('getEpisode')
            ->with($storySlug, $episodeSlug)
            ->andReturn([
                'file_path' => $canonicalPath,
                'raw_markdown' => '# updated script',
            ]);

        $service = app(EpisodeScriptService::class);
        $result = $service->readForEpisode($episode->fresh());

        $this->assertNotNull($result);
        $this->assertSame('# updated script', $result['script_content']);
        $this->assertSame('story_editor', $result['source']);

        @unlink($canonicalPath);
    }

    public function test_publish_script_content_is_returned_by_episode_script_endpoint(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $category = Category::factory()->create();
        $story = Story::factory()->create(['category_id' => $category->id]);
        $episode = Episode::create([
            'story_id' => $story->id,
            'title' => 'Episode 1',
            'audio_url' => 'audio/episodes/test.mp3',
            'duration' => 120,
            'episode_number' => 1,
            'status' => 'draft',
        ]);

        $markdown = "# Episode 1\n\nUpdated dialogue";

        app(EpisodeScriptService::class)->publishScriptContent($episode, $markdown);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/episodes/{$episode->id}/script");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.script_content', $markdown);
    }

    public function test_read_from_stored_path_supports_public_scripts_directory(): void
    {
        $relativePath = 'scripts/episodes/legacy-script.md';
        $absolutePath = public_path($relativePath);
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($absolutePath, '# legacy script');

        $content = app(EpisodeScriptService::class)->readFromStoredPath($relativePath);

        $this->assertSame('# legacy script', $content);

        @unlink($absolutePath);
    }
}
