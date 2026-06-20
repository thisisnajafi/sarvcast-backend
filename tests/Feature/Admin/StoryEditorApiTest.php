<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoryEditorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_story_editor_lists_filesystem_stories(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/story-editor/stories');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'folder_name', 'name_persian', 'name_english', 'episode_count', 'target_age'],
            ],
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_story_editor_returns_episodes_for_story(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $stories = $this->getJson('/api/admin/story-editor/stories')->json('data');
        $storyId = $stories[0]['id'];

        $response = $this->getJson("/api/admin/story-editor/stories/{$storyId}/episodes");

        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'episode_number', 'title_persian', 'last_modified'],
            ],
        ]);
    }

    public function test_story_editor_returns_structured_episode(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $stories = $this->getJson('/api/admin/story-editor/stories')->json('data');
        $storyId = $stories[0]['id'];
        $episodes = $this->getJson("/api/admin/story-editor/stories/{$storyId}/episodes")->json('data');
        $episodeId = $episodes[0]['id'];

        $response = $this->getJson("/api/admin/story-editor/stories/{$storyId}/episodes/{$episodeId}");

        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                'episode' => ['metadata', 'characters', 'scenes', 'closing'],
                'master_characters',
                'invalid_character_ids',
                'file_path',
                'last_modified',
            ],
        ]);
    }
}
