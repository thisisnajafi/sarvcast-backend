<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EpisodeCreateApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Story $story;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Test category',
            'slug' => 'test-category-ep',
            'description' => 'Test',
            'color' => '#00BCD4',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->story = Story::create([
            'title' => 'Story for episode create',
            'description' => 'Test story',
            'image_url' => 'images/stories/test.jpg',
            'category_id' => $category->id,
            'age_group' => 'all',
            'duration' => 0,
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_create_episode_without_audio(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/episodes', [
            'story_id' => $this->story->id,
            'title' => 'قسمت اول',
            'description' => 'توضیح',
            'episode_number' => 1,
            'duration' => 12,
            'status' => 'draft',
            'is_premium' => false,
            'age_rating' => 'all',
            'audio_file_url' => '',
            'tags' => ['کودک'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'قسمت اول');

        $this->assertDatabaseHas('episodes', [
            'story_id' => $this->story->id,
            'episode_number' => 1,
            'title' => 'قسمت اول',
            'status' => 'draft',
            'age_rating' => 'all',
        ]);
    }

    public function test_duplicate_episode_number_returns_validation_error_not_503(): void
    {
        Sanctum::actingAs($this->admin);

        Episode::create([
            'story_id' => $this->story->id,
            'title' => 'Existing',
            'audio_url' => null,
            'duration' => 5,
            'episode_number' => 1,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/admin/episodes', [
            'story_id' => $this->story->id,
            'title' => 'Duplicate number',
            'episode_number' => 1,
            'duration' => 8,
            'status' => 'draft',
            'age_rating' => '7+',
        ]);

        $response->assertStatus(422);
        $this->assertNotEquals(503, $response->status());
        $body = $response->json();
        $message = (string) ($body['message'] ?? '');
        $errors = $body['errors']['episode_number'][0] ?? '';
        $this->assertTrue(
            str_contains($message, 'شماره اپیزود') || str_contains((string) $errors, 'شماره اپیزود'),
            'Expected Persian duplicate episode_number message'
        );
    }
}
