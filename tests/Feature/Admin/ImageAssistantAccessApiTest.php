<?php

namespace Tests\Feature\Admin;

use App\Models\Episode;
use App\Models\Story;
use App\Models\StoryImageAssistant;
use App\Models\StoryProductionAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImageAssistantAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_access_payload_for_image_assistant(): void
    {
        $assistant = User::factory()->imageAssistant()->create();
        Sanctum::actingAs($assistant);

        $this->getJson('/api/admin/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'image_assistant')
            ->assertJsonPath('data.access.is_full_admin', false)
            ->assertJsonPath('data.access.is_image_assistant', true)
            ->assertJsonPath('data.access.can_view_prompts', true)
            ->assertJsonPath('data.access.can_manage_timeline', true)
            ->assertJsonPath('data.access.can_assign_image_assistants', false)
            ->assertJsonPath('data.access.can_access_story_package', false);
    }

    public function test_image_assistant_only_sees_assigned_stories(): void
    {
        $assistant = User::factory()->imageAssistant()->create();
        $mine = $this->makeStory(['title' => 'داستان من']);
        $theirs = $this->makeStory(['title' => 'داستان دیگر']);
        $this->assignAssistant($mine, $assistant);

        Sanctum::actingAs($assistant);

        $ids = collect($this->getJson('/api/admin/stories')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));

        $this->getJson('/api/admin/stories/'.$theirs->id)->assertForbidden();
        $this->getJson('/api/admin/stories/'.$mine->id)
            ->assertOk()
            ->assertJsonPath('data.permissions.can_view_prompts', true)
            ->assertJsonPath('data.permissions.can_manage_timeline', true);
    }

    public function test_image_assistant_can_read_prompts_only_for_assigned_story(): void
    {
        $assistant = User::factory()->imageAssistant()->create();
        $mine = $this->makeStory();
        $theirs = $this->makeStory();
        $this->assignAssistant($mine, $assistant);
        $this->makeAsset($mine, 'cover', 'cover-prompt');
        $this->makeAsset($theirs, 'cover', 'secret-prompt');

        Sanctum::actingAs($assistant);

        $this->getJson('/api/admin/stories/'.$mine->id.'/production-assets')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['prompt' => 'cover-prompt']);

        $this->getJson('/api/admin/stories/'.$theirs->id.'/production-assets')
            ->assertForbidden();
    }

    public function test_image_assistant_forbidden_from_admin_segments(): void
    {
        $assistant = User::factory()->imageAssistant()->create();
        Sanctum::actingAs($assistant);

        $this->getJson('/api/admin/payments')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/story-editor/stories/demo/package')->assertForbidden();
    }

    public function test_admin_can_assign_and_revoke_image_assistant(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $story = $this->makeStory();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/stories/'.$story->id.'/image-assistants', [
            'user_id' => $parent->id,
            'promote_to_image_assistant' => true,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('story_image_assistants', [
            'story_id' => $story->id,
            'user_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $parent->id,
            'role' => User::ROLE_IMAGE_ASSISTANT,
        ]);

        $this->deleteJson('/api/admin/stories/'.$story->id.'/image-assistants/'.$parent->id)
            ->assertOk();

        $this->assertDatabaseMissing('story_image_assistants', [
            'story_id' => $story->id,
            'user_id' => $parent->id,
        ]);
    }

    public function test_image_assistant_can_manage_timeline_for_assigned_episode(): void
    {
        $assistant = User::factory()->imageAssistant()->create();
        $story = $this->makeStory();
        $other = $this->makeStory();
        $this->assignAssistant($story, $assistant);
        $episode = $this->makeEpisode($story);
        $otherEpisode = $this->makeEpisode($other);

        Sanctum::actingAs($assistant);

        $this->postJson('/api/admin/timeline-management', [
            'episode_id' => $episode->id,
            'story_id' => $story->id,
            'start_time' => 0,
            'end_time' => 10,
            'image_url' => 'https://example.com/frame.webp',
            'image_order' => 1,
            'transition_type' => 'fade',
            'is_key_frame' => true,
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/admin/timeline-management', [
            'episode_id' => $otherEpisode->id,
            'story_id' => $other->id,
            'start_time' => 0,
            'end_time' => 10,
            'image_url' => 'https://example.com/frame2.webp',
            'image_order' => 1,
            'transition_type' => 'fade',
            'is_key_frame' => true,
        ])->assertForbidden();
    }

    public function test_image_assistant_can_receive_admin_otp(): void
    {
        $sms = \Mockery::mock(\App\Services\SmsService::class);
        $sms->shouldReceive('hasTooManyAttempts')->andReturn(false);
        $sms->shouldReceive('sendOtp')->andReturn(['success' => true]);
        $this->app->instance(\App\Services\SmsService::class, $sms);

        $assistant = User::factory()->imageAssistant()->create(['phone_number' => '09124444444']);

        $this->postJson('/api/admin/v1/auth/send-otp', ['phone_number' => $assistant->phone_number])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    private function assignAssistant(Story $story, User $user): void
    {
        StoryImageAssistant::query()->create([
            'story_id' => $story->id,
            'user_id' => $user->id,
            'assigned_by' => null,
        ]);
    }

    private function makeAsset(Story $story, string $type, string $prompt): StoryProductionAsset
    {
        return StoryProductionAsset::query()->create([
            'story_slug' => 'story-'.$story->id,
            'episode_slug' => null,
            'asset_type' => $type,
            'asset_key' => $type.'-1',
            'name_persian' => 'تست',
            'prompt' => $prompt,
            'story_id' => $story->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function makeStory(array $attrs = []): Story
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category '.uniqid(),
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Story::query()->create(array_merge([
            'title' => 'داستان تست',
            'description' => 'توضیحات داستان تست',
            'image_url' => 'https://example.com/cover.webp',
            'category_id' => $categoryId,
            'age_group' => '7+',
            'language' => 'fa',
            'duration' => 10,
            'status' => 'draft',
        ], $attrs));
    }

    private function makeEpisode(Story $story): Episode
    {
        return Episode::query()->create([
            'story_id' => $story->id,
            'title' => 'قسمت ۱',
            'description' => 'توضیح',
            'episode_number' => 1,
            'duration' => 5,
            'status' => 'draft',
            'is_premium' => false,
            'age_rating' => '7+',
        ]);
    }
}
