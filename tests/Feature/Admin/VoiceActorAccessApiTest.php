<?php

namespace Tests\Feature\Admin;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Role;
use App\Models\Story;
use App\Models\StoryImageAssistant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoiceActorAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_access_payload_for_voice_actor(): void
    {
        $va = User::factory()->voiceActor()->create();
        Sanctum::actingAs($va);

        $this->getJson('/api/admin/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'voice_actor')
            ->assertJsonPath('data.access.is_full_admin', false)
            ->assertJsonPath('data.access.is_head_writer', false)
            ->assertJsonPath('data.access.is_voice_actor', true)
            ->assertJsonPath('data.access.is_contributor', true)
            ->assertJsonPath('data.access.can_view_all_stories', false)
            ->assertJsonPath('data.access.can_assign_story_writers', false);
    }

    public function test_stale_head_writer_rbac_does_not_elevate_voice_actor(): void
    {
        $va = User::factory()->voiceActor()->create();
        $headRole = Role::query()->firstOrCreate(
            ['name' => 'head_writer'],
            ['display_name' => 'سرپرست', 'description' => 'test', 'is_active' => true]
        );
        $va->roles()->syncWithoutDetaching([$headRole->id]);

        $this->assertTrue($va->fresh()->isVoiceActor());
        $this->assertFalse($va->fresh()->isHeadWriter());

        Sanctum::actingAs($va);

        $this->getJson('/api/admin/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.access.is_head_writer', false)
            ->assertJsonPath('data.access.can_view_all_stories', false);
    }

    public function test_voice_actor_only_sees_cast_and_image_assigned_stories(): void
    {
        $va = User::factory()->voiceActor()->create();
        $asNarrator = $this->makeStory(['title' => 'راوی', 'narrator_id' => $va->id]);
        $asCharacter = $this->makeStory(['title' => 'شخصیت']);
        Character::query()->create([
            'story_id' => $asCharacter->id,
            'name' => 'قهرمان',
            'voice_actor_id' => $va->id,
        ]);
        $asImage = $this->makeStory(['title' => 'تصویر']);
        $this->assignImageAssistant($asImage, $va);
        $other = $this->makeStory(['title' => 'غریبه']);

        Sanctum::actingAs($va);

        $ids = collect($this->getJson('/api/admin/stories')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($asNarrator->id));
        $this->assertTrue($ids->contains($asCharacter->id));
        $this->assertTrue($ids->contains($asImage->id));
        $this->assertFalse($ids->contains($other->id));

        $this->getJson('/api/admin/stories/'.$other->id)->assertForbidden();
        $this->getJson('/api/admin/stories/'.$asNarrator->id)->assertOk();
        $this->getJson('/api/admin/stories/'.$asImage->id)
            ->assertOk()
            ->assertJsonPath('data.permissions.can_view_prompts', true)
            ->assertJsonPath('data.permissions.can_manage_timeline', true);
    }

    public function test_voice_actor_episode_list_is_scoped(): void
    {
        $va = User::factory()->voiceActor()->create();
        $mine = $this->makeStory(['narrator_id' => $va->id]);
        $theirs = $this->makeStory();
        $mineEp = $this->makeEpisode($mine);
        $theirsEp = $this->makeEpisode($theirs);

        Sanctum::actingAs($va);

        $ids = collect($this->getJson('/api/admin/episodes')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($mineEp->id));
        $this->assertFalse($ids->contains($theirsEp->id));
    }

    public function test_voice_actor_forbidden_from_admin_segments(): void
    {
        $va = User::factory()->voiceActor()->create();
        Sanctum::actingAs($va);

        $this->getJson('/api/admin/payments')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/writers')->assertForbidden();
    }

    private function assignImageAssistant(Story $story, User $user): void
    {
        StoryImageAssistant::query()->create([
            'story_id' => $story->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
            'notes' => null,
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
