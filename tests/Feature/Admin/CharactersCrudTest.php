<?php

namespace Tests\Feature\Admin;

use App\Models\Character;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CharactersCrudTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────────────────
    // Auth guards
    // ────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_characters(): void
    {
        $this->getJson('/api/admin/characters')->assertStatus(401);
    }

    public function test_non_admin_cannot_list_characters(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        Sanctum::actingAs($user);
        $this->getJson('/api/admin/characters')->assertStatus(403);
    }

    // ────────────────────────────────────────────────────────
    // Index
    // ────────────────────────────────────────────────────────

    public function test_super_admin_can_list_characters(): void
    {
        $admin = $this->makeAdmin();
        $story  = $this->makeStory();
        Character::query()->create(['story_id' => $story->id, 'name' => 'آرش']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/characters')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'pagination']);
    }

    public function test_index_filters_by_story_id(): void
    {
        $admin  = $this->makeAdmin();
        $story1 = $this->makeStory();
        $story2 = $this->makeStory();
        Character::query()->create(['story_id' => $story1->id, 'name' => 'آرش']);
        Character::query()->create(['story_id' => $story2->id, 'name' => 'کامیار']);

        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/admin/characters?story_id=' . $story1->id);
        $res->assertOk();
        $names = collect($res->json('data'))->pluck('name');
        $this->assertTrue($names->contains('آرش'));
        $this->assertFalse($names->contains('کامیار'));
    }

    public function test_index_filters_by_search(): void
    {
        $admin = $this->makeAdmin();
        $story = $this->makeStory();
        Character::query()->create(['story_id' => $story->id, 'name' => 'نیلوفر']);
        Character::query()->create(['story_id' => $story->id, 'name' => 'رستم']);

        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/admin/characters?search=نیلوفر');
        $res->assertOk();
        $names = collect($res->json('data'))->pluck('name');
        $this->assertTrue($names->contains('نیلوفر'));
        $this->assertFalse($names->contains('رستم'));
    }

    // ────────────────────────────────────────────────────────
    // Show
    // ────────────────────────────────────────────────────────

    public function test_admin_can_show_character(): void
    {
        $admin = $this->makeAdmin();
        $story = $this->makeStory();
        /** @var Character $char */
        $char = Character::query()->create(['story_id' => $story->id, 'name' => 'دارا']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/characters/' . $char->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'دارا');
    }

    // ────────────────────────────────────────────────────────
    // Store
    // ────────────────────────────────────────────────────────

    public function test_admin_can_create_character(): void
    {
        $admin = $this->makeAdmin();
        $story = $this->makeStory();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/characters', [
            'story_id' => $story->id,
            'name'     => 'شهرزاد',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'شهرزاد');

        $this->assertDatabaseHas('characters', ['name' => 'شهرزاد', 'story_id' => $story->id]);
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/characters', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['story_id', 'name']);
    }

    // ────────────────────────────────────────────────────────
    // Update
    // ────────────────────────────────────────────────────────

    public function test_admin_can_update_character(): void
    {
        $admin = $this->makeAdmin();
        $story = $this->makeStory();
        /** @var Character $char */
        $char = Character::query()->create(['story_id' => $story->id, 'name' => 'قدیم']);

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/characters/' . $char->id, [
            'story_id' => $story->id,
            'name'     => 'جدید',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'جدید');

        $this->assertDatabaseHas('characters', ['id' => $char->id, 'name' => 'جدید']);
    }

    // ────────────────────────────────────────────────────────
    // Destroy
    // ────────────────────────────────────────────────────────

    public function test_admin_can_delete_character(): void
    {
        $admin = $this->makeAdmin();
        $story = $this->makeStory();
        /** @var Character $char */
        $char = Character::query()->create(['story_id' => $story->id, 'name' => 'حذفی']);

        Sanctum::actingAs($admin);

        $this->deleteJson('/api/admin/characters/' . $char->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('characters', ['id' => $char->id]);
    }

    // ────────────────────────────────────────────────────────
    // Stories dropdown
    // ────────────────────────────────────────────────────────

    public function test_admin_can_list_stories_for_dropdown(): void
    {
        $admin = $this->makeAdmin();
        $this->makeStory();
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/characters/stories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    // ────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    private function makeStory(): Story
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name'       => 'دسته آزمایشی ' . uniqid(),
            'slug'       => 'test-' . uniqid(),
            'is_active'  => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var Story $story */
        $story = Story::query()->create([
            'title'       => 'داستان آزمایشی ' . uniqid(),
            'description' => 'توضیحات آزمایشی',
            'image_url'   => 'https://example.com/cover.webp',
            'category_id' => $categoryId,
            'age_group'   => '7+',
            'language'    => 'fa',
            'duration'    => 10,
            'status'      => 'draft',
        ]);

        return $story;
    }
}
