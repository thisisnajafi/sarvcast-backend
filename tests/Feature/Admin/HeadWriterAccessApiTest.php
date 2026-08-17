<?php

namespace Tests\Feature\Admin;

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HeadWriterAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_writer_can_list_stories_they_do_not_author(): void
    {
        $head = User::factory()->headWriter()->create();
        $story = $this->makeStory();

        Sanctum::actingAs($head);

        $this->getJson('/api/admin/stories')
            ->assertOk()
            ->assertJsonPath('success', true);

        $ids = collect($this->getJson('/api/admin/stories')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($story->id));
    }

    public function test_writer_cannot_see_other_stories(): void
    {
        $writer = User::factory()->writer()->create();
        $other = User::factory()->writer()->create();
        $mine = $this->makeStory(['author_id' => $writer->id]);
        $theirs = $this->makeStory(['author_id' => $other->id]);

        Sanctum::actingAs($writer);

        $ids = collect($this->getJson('/api/admin/stories')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));

        $this->getJson('/api/admin/stories/'.$theirs->id)
            ->assertForbidden();
    }

    public function test_head_writer_is_forbidden_from_payments_and_package(): void
    {
        $head = User::factory()->headWriter()->create();
        Sanctum::actingAs($head);

        $this->getJson('/api/admin/payments')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/story-editor/stories/demo/package')->assertForbidden();
    }

    public function test_parent_cannot_hit_admin_stories(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        Sanctum::actingAs($parent);

        $this->getJson('/api/admin/stories')->assertForbidden();
    }

    public function test_me_access_payload_for_head_writer(): void
    {
        $head = User::factory()->headWriter()->create();
        Sanctum::actingAs($head);

        $this->getJson('/api/admin/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'head_writer')
            ->assertJsonPath('data.access.is_full_admin', false)
            ->assertJsonPath('data.access.is_head_writer', true)
            ->assertJsonPath('data.access.can_view_all_stories', true)
            ->assertJsonPath('data.access.can_assign_story_writers', true)
            ->assertJsonPath('data.access.can_access_story_package', false);
    }

    public function test_grant_promotes_parent_when_confirmed(): void
    {
        $head = User::factory()->headWriter()->create();
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $story = $this->makeStory();

        Sanctum::actingAs($head);

        $this->postJson('/api/admin/stories/'.$story->id.'/author', [
            'user_id' => $parent->id,
            'promote_to_writer' => true,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('stories', [
            'id' => $story->id,
            'author_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $parent->id,
            'role' => User::ROLE_WRITER,
        ]);
    }

    public function test_grant_parent_without_promote_is_rejected(): void
    {
        $head = User::factory()->headWriter()->create();
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $story = $this->makeStory();

        Sanctum::actingAs($head);

        $this->postJson('/api/admin/stories/'.$story->id.'/author', [
            'user_id' => $parent->id,
        ])->assertStatus(422);
    }

    public function test_grant_child_is_rejected(): void
    {
        $head = User::factory()->headWriter()->create();
        $child = User::factory()->create(['role' => User::ROLE_CHILD]);
        $story = $this->makeStory();

        Sanctum::actingAs($head);

        $this->postJson('/api/admin/stories/'.$story->id.'/author', [
            'user_id' => $child->id,
            'promote_to_writer' => true,
        ])->assertStatus(422);
    }

    public function test_revoke_clears_author_and_keeps_writer_role(): void
    {
        $head = User::factory()->headWriter()->create();
        $writer = User::factory()->writer()->create();
        $story = $this->makeStory(['author_id' => $writer->id]);

        Sanctum::actingAs($head);

        $this->deleteJson('/api/admin/stories/'.$story->id.'/author')
            ->assertOk();

        $this->assertDatabaseHas('stories', [
            'id' => $story->id,
            'author_id' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $writer->id,
            'role' => User::ROLE_WRITER,
        ]);
    }

    public function test_writer_cannot_assign_author(): void
    {
        $writer = User::factory()->writer()->create();
        $other = User::factory()->writer()->create();
        $story = $this->makeStory(['author_id' => $writer->id]);

        Sanctum::actingAs($writer);

        $this->postJson('/api/admin/stories/'.$story->id.'/author', [
            'user_id' => $other->id,
        ])->assertForbidden();
    }

    public function test_head_writer_can_list_writers(): void
    {
        $head = User::factory()->headWriter()->create();
        User::factory()->writer()->create(['first_name' => 'نرگس']);

        Sanctum::actingAs($head);

        $this->getJson('/api/admin/writers')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_super_admin_can_assign_head_writer_role_and_rbac_pivot(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->writer()->create();
        $headRole = \App\Models\Role::query()->create([
            'name' => User::ROLE_HEAD_WRITER,
            'display_name' => 'سرپرست نویسندگان',
            'is_active' => true,
        ]);
        \App\Models\Role::query()->create([
            'name' => User::ROLE_WRITER,
            'display_name' => 'نویسنده',
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/users/'.$target->id, [
            'first_name' => $target->first_name,
            'last_name' => $target->last_name,
            'phone_number' => $target->phone_number,
            'role' => User::ROLE_HEAD_WRITER,
            'status' => User::STATUS_ACTIVE,
            'role_ids' => [],
        ])->assertOk()->assertJsonPath('success', true);

        $target->refresh();
        $this->assertSame(User::ROLE_HEAD_WRITER, $target->role);
        $this->assertTrue($target->roles()->where('roles.id', $headRole->id)->exists());

        $ids = collect($this->getJson('/api/admin/writers')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
    }

    public function test_cannot_promote_child_to_head_writer(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $child = User::factory()->create(['role' => User::ROLE_CHILD]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/users/'.$child->id, [
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'phone_number' => $child->phone_number,
            'role' => User::ROLE_HEAD_WRITER,
            'status' => User::STATUS_ACTIVE,
        ])->assertStatus(422);

        $this->postJson('/api/admin/users/bulk-action', [
            'action' => 'change_role',
            'user_ids' => [$child->id],
            'role' => User::ROLE_HEAD_WRITER,
        ])->assertOk();

        $child->refresh();
        $this->assertSame(User::ROLE_CHILD, $child->role);
    }

    public function test_head_writer_cannot_assign_head_writer_role(): void
    {
        $head = User::factory()->headWriter()->create();
        $writer = User::factory()->writer()->create();

        Sanctum::actingAs($head);

        $this->putJson('/api/admin/users/'.$writer->id, [
            'first_name' => $writer->first_name,
            'last_name' => $writer->last_name,
            'phone_number' => $writer->phone_number,
            'role' => User::ROLE_HEAD_WRITER,
            'status' => User::STATUS_ACTIVE,
        ])->assertForbidden();
    }

    public function test_candidates_exclude_children_and_include_promotable_parent(): void
    {
        $head = User::factory()->headWriter()->create();
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'first_name' => 'کاندید',
            'last_name' => 'والد',
        ]);
        User::factory()->create([
            'role' => User::ROLE_CHILD,
            'first_name' => 'کاندید',
            'last_name' => 'کودک',
        ]);

        Sanctum::actingAs($head);

        $ids = collect($this->getJson('/api/admin/writers/candidates?q=کاندید')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($parent->id));
        $this->assertFalse(
            collect($this->getJson('/api/admin/writers/candidates?q=کاندید')->json('data'))
                ->contains(fn (array $row) => $row['role'] === User::ROLE_CHILD)
        );
    }

    public function test_grant_to_admin_does_not_change_their_role(): void
    {
        $head = User::factory()->headWriter()->create();
        $admin = User::factory()->admin()->create();
        $story = $this->makeStory();

        Sanctum::actingAs($head);

        $this->postJson('/api/admin/stories/'.$story->id.'/author', [
            'user_id' => $admin->id,
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('stories', [
            'id' => $story->id,
            'author_id' => $admin->id,
        ]);
    }

    public function test_head_writer_and_writer_can_request_admin_otp_parent_cannot(): void
    {
        $sms = \Mockery::mock(\App\Services\SmsService::class);
        $sms->shouldReceive('hasTooManyAttempts')->andReturn(false);
        $sms->shouldReceive('sendOtp')->andReturn(['success' => true]);
        $this->app->instance(\App\Services\SmsService::class, $sms);

        $head = User::factory()->headWriter()->create(['phone_number' => '09121111111']);
        $writer = User::factory()->writer()->create(['phone_number' => '09122222222']);
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'phone_number' => '09123333333',
        ]);

        $this->postJson('/api/admin/v1/auth/send-otp', ['phone_number' => $head->phone_number])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/admin/v1/auth/send-otp', ['phone_number' => $writer->phone_number])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/admin/v1/auth/send-otp', ['phone_number' => $parent->phone_number])
            ->assertNotFound();
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
}
