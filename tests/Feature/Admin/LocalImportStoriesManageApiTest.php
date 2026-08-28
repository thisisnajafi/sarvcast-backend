<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class LocalImportStoriesManageApiTest extends TestCase
{
    public function test_delete_story_requires_auth(): void
    {
        $response = $this->postJson('/api/admin/local-import/stories/manage/delete-story', [
            'folder_name' => '99 - test story',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_characters_requires_auth(): void
    {
        $response = $this->postJson('/api/admin/local-import/stories/manage/update-characters', [
            'folder_name' => '99 - test story',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_episode_requires_auth(): void
    {
        $response = $this->postJson('/api/admin/local-import/stories/manage/delete-episode', [
            'folder_name' => '99 - test story',
            'episode_number' => 1,
        ]);

        $response->assertStatus(401);
    }
}
