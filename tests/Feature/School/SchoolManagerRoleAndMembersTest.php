<?php

namespace Tests\Feature\School;

use App\Models\Role;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ContributorStoryAccessService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolManagerRoleAndMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_seeds_school_permissions_without_giving_them_to_admin(): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => 'school_managers.create',
            'group' => 'school_managers',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'school_panel.members.add',
            'group' => 'school_panel',
        ]);
        $this->assertDatabaseHas('roles', ['name' => 'school_manager']);

        $this->seed(RolePermissionSeeder::class);

        $admin = Role::query()->where('name', 'admin')->firstOrFail();
        $school = Role::query()->where('name', 'school_manager')->firstOrFail();
        $super = Role::query()->where('name', 'super_admin')->firstOrFail();

        $this->assertFalse($admin->permissions()->where('name', 'school_managers.create')->exists());
        $this->assertFalse($admin->permissions()->where('name', 'school_panel.members.add')->exists());
        $this->assertTrue($school->permissions()->where('name', 'school_panel.members.add')->exists());
        $this->assertFalse($school->permissions()->where('name', 'users.view')->exists());
        $this->assertFalse($school->permissions()->where('name', 'dashboard.view')->exists());
        $this->assertTrue($super->permissions()->where('name', 'school_managers.update')->exists());
    }

    public function test_super_admin_can_create_a_manager_from_a_new_phone_and_from_a_parent(): void
    {
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $created = $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '09120000001',
            'first_name' => 'سارا',
            'last_name' => 'مدیری',
            'member_limit' => 50,
            'label' => 'دبستان سرو',
        ])->assertCreated()
            ->assertJsonPath('data.member_limit', 50)
            ->assertJsonPath('data.label', 'دبستان سرو')
            ->assertJsonPath('data.manager.first_name', 'سارا');

        $managerId = $created->json('data.manager.id');
        $this->assertDatabaseHas('users', [
            'id' => $managerId,
            'role' => User::ROLE_SCHOOL_MANAGER,
            'requires_2fa' => true,
            'phone_number' => '09120000001',
        ]);
        $roleId = Role::query()->where('name', 'school_manager')->value('id');
        $this->assertDatabaseHas('user_role', [
            'user_id' => $managerId,
            'role_id' => $roleId,
        ]);

        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'phone_number' => '09120000002',
        ]);

        $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '09120000002',
            'member_limit' => 10,
        ])->assertCreated()
            ->assertJsonPath('data.member_limit', 10)
            ->assertJsonPath('data.manager.id', $parent->id);

        $this->assertDatabaseHas('users', [
            'id' => $parent->id,
            'role' => User::ROLE_SCHOOL_MANAGER,
            'requires_2fa' => true,
        ]);
    }

    public function test_new_manager_phone_requires_a_name_and_staff_phones_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '09120000003',
            'member_limit' => 5,
        ])->assertStatus(422)->assertJsonPath('error', 'name_required');

        $writer = User::factory()->writer()->create(['phone_number' => '09120000004']);
        $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => $writer->phone_number,
            'member_limit' => 5,
        ])->assertStatus(422)->assertJsonPath('error', 'not_promotable');

        $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '9120000005',
            'first_name' => 'بد',
            'last_name' => 'شماره',
            'member_limit' => 5,
        ])->assertStatus(422);
    }

    public function test_admin_cannot_manage_school_managers(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/v1/school-managers')->assertForbidden();
        $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '09120000006',
            'first_name' => 'ندا',
            'last_name' => 'آزاد',
            'member_limit' => 5,
        ])->assertForbidden();
    }

    public function test_super_admin_can_change_the_member_limit_and_close_the_panel(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09120000007', 40);
        $member = User::factory()->create(['phone_number' => '09120000008']);
        $this->attach($manager, $profile, $member);
        $manager->createToken('admin-auth-token');

        Sanctum::actingAs($super);

        $this->patchJson('/api/admin/v1/school-managers/'.$profile->id, [
            'member_limit' => 2,
            'label' => 'سقف تازه',
            'status' => 'suspended',
        ])->assertOk()
            ->assertJsonPath('data.member_limit', 2)
            ->assertJsonPath('data.status', 'suspended')
            ->assertJsonPath('data.active_member_count', 1);

        $this->deleteJson('/api/admin/v1/school-managers/'.$profile->id)
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'role' => User::ROLE_BASIC,
        ]);
        $this->assertDatabaseHas('school_manager_profiles', [
            'id' => $profile->id,
            'status' => SchoolManagerProfile::STATUS_DELETED,
        ]);
        $this->assertDatabaseHas('users', ['id' => $member->id]);
        $this->assertNull(SchoolMembership::query()->find(
            SchoolMembership::query()->where('user_id', $member->id)->value('id'),
        )?->active_user_id);
        $this->assertSame(0, $manager->fresh()->tokens()->count());

        $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '09120000007',
            'member_limit' => 8,
        ])->assertCreated()->assertJsonPath('data.id', $profile->id)->assertJsonPath('data.status', 'active');

        $this->assertSame(User::ROLE_SCHOOL_MANAGER, $manager->fresh()->role);
    }

    public function test_school_manager_can_open_the_admin_session_but_not_global_admin_routes(): void
    {
        [$manager] = $this->panel('09120000009', 10);
        $access = app(ContributorStoryAccessService::class);

        $this->assertTrue($access->mayAccessAdminPanel($manager));
        $this->assertTrue($access->mayReceiveAdminOtp($manager));

        Sanctum::actingAs($manager);

        $me = $this->getJson('/api/admin/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'school_manager')
            ->assertJsonPath('data.access.is_school_manager', true)
            ->assertJsonPath('data.access.is_full_admin', false)
            ->assertJsonPath('data.access.is_contributor', false);

        $permissions = $me->json('data.permissions');
        $this->assertContains('school_panel.members.add', $permissions);
        $this->assertNotContains('users.view', $permissions);
        $this->assertNotContains('stories.read', $permissions);
        $this->assertNotContains('dashboard.view', $permissions);

        $this->getJson('/api/admin/stories')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/v1/dashboard/stats')->assertForbidden();
        $this->getJson('/api/admin/v1/school-managers')->assertForbidden();
    }

    public function test_lookup_shows_name_without_attaching_and_rejects_bad_or_unknown_numbers(): void
    {
        [$manager, $profile] = $this->panel('09120000010', 5);
        $person = User::factory()->create([
            'phone_number' => '09120000011',
            'first_name' => 'آرمین',
            'last_name' => 'کودک',
            'role' => User::ROLE_BASIC,
        ]);
        Subscription::query()->create([
            'user_id' => $person->id,
            'type' => '1month',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price' => 1000,
            'currency' => 'IRR',
        ]);

        Sanctum::actingAs($manager);

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => '09120000011',
        ])->assertOk()
            ->assertJsonPath('data.id', $person->id)
            ->assertJsonPath('data.first_name', 'آرمین')
            ->assertJsonPath('data.last_name', 'کودک')
            ->assertJsonPath('data.phone_number', '09120000011')
            ->assertJsonPath('data.can_add', true)
            ->assertJsonPath('data.block_reason', null)
            ->assertJsonPath('data.subscription.type', '1month');

        $this->assertSame(0, SchoolMembership::query()->count());

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => '09129999999',
        ])->assertNotFound()->assertJsonPath('error', 'not_found');

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => '+989120000011',
        ])->assertStatus(422);
    }

    public function test_manager_adds_lists_and_removes_members_within_the_limit(): void
    {
        [$manager, $profile] = $this->panel('09120000012', 1);
        $first = User::factory()->create(['phone_number' => '09120000013', 'first_name' => 'یک']);
        $second = User::factory()->create(['phone_number' => '09120000014', 'first_name' => 'دو']);
        $finishing = User::factory()->create([
            'phone_number' => '09120000015',
            'status' => User::STATUS_PROFILE_COMPLETION_NEEDED,
        ]);
        $inactive = User::factory()->create([
            'phone_number' => '09120000016',
            'status' => User::STATUS_INACTIVE,
        ]);

        Sanctum::actingAs($manager);

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => $manager->phone_number,
        ])->assertOk()->assertJsonPath('data.block_reason', 'is_self')->assertJsonPath('data.can_add', false);

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $manager->id,
        ])->assertStatus(422)->assertJsonPath('error', 'is_self');

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $first->id,
        ])->assertCreated()->assertJsonPath('data.user_id', $first->id);

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $first->id,
        ])->assertStatus(409)->assertJsonPath('error', 'already_in_panel');

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => $second->phone_number,
        ])->assertOk()->assertJsonPath('data.block_reason', 'limit_reached')->assertJsonPath('data.first_name', 'دو');

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $second->id,
        ])->assertStatus(422)->assertJsonPath('error', 'limit_reached');

        $this->getJson('/api/admin/v1/school-panel/members')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $first->id)
            ->assertJsonPath('meta.total', 1);

        $this->deleteJson('/api/admin/v1/school-panel/members/'.$first->id)->assertOk();
        $this->assertDatabaseHas('users', ['id' => $first->id]);
        $this->assertNull(
            SchoolMembership::query()->where('user_id', $first->id)->value('active_user_id'),
        );

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $finishing->id,
        ])->assertCreated();

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $inactive->id,
        ])->assertStatus(422)->assertJsonPath('error', 'suspended_user');

        $this->assertSame(2, SchoolMembership::query()->where('school_manager_profile_id', $profile->id)->count());
        $this->assertSame(1, SchoolMembership::query()->where('school_manager_profile_id', $profile->id)->active()->count());
    }

    public function test_a_manager_cannot_see_or_change_another_managers_members(): void
    {
        [$managerA] = $this->panel('09120000017', 5, 'مدرسه-راز-آلفا');
        [$managerB, $profileB] = $this->panel('09120000018', 5, 'مدرسه-راز-بتا');
        $memberA = User::factory()->create(['phone_number' => '09120000019', 'first_name' => 'عضوالف']);
        $memberB = User::factory()->create(['phone_number' => '09120000020', 'first_name' => 'عضوب']);
        $staff = User::factory()->writer()->create(['phone_number' => '09120000021']);
        $this->attach($managerA, $managerA->schoolManagerProfile, $memberA);
        $this->attach($managerB, $profileB, $memberB);

        Sanctum::actingAs($managerA);

        $lookup = $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => $memberB->phone_number,
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'عضوب')
            ->assertJsonPath('data.can_add', false)
            ->assertJsonPath('data.block_reason', 'managed_elsewhere');

        $this->assertStringNotContainsString('مدرسه-راز-بتا', $lookup->getContent());

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $memberB->id,
        ])->assertStatus(409)->assertJsonPath('error', 'managed_elsewhere');

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => $staff->phone_number,
        ])->assertOk()->assertJsonPath('data.block_reason', 'not_a_consumer');

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $staff->id,
        ])->assertStatus(422)->assertJsonPath('error', 'not_a_consumer');

        $this->deleteJson('/api/admin/v1/school-panel/members/'.$memberB->id)->assertNotFound();
        $this->assertNotNull(
            SchoolMembership::query()->where('user_id', $memberB->id)->value('active_user_id'),
        );

        $ids = collect($this->getJson('/api/admin/v1/school-panel/members')->json('data'))->pluck('user_id');
        $this->assertTrue($ids->contains($memberA->id));
        $this->assertFalse($ids->contains($memberB->id));

        Sanctum::actingAs(User::factory()->superAdmin()->create());
        $adminIds = collect($this->getJson('/api/admin/v1/school-managers/'.$profileB->id.'/members')->json('data'))->pluck('user_id');
        $this->assertTrue($adminIds->contains($memberB->id));
        $this->assertFalse($adminIds->contains($memberA->id));
    }

    public function test_lowering_the_limit_keeps_current_members_and_blocks_new_ones(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09120000022', 2);
        $kept = User::factory()->create(['phone_number' => '09120000023']);
        $alsoKept = User::factory()->create(['phone_number' => '09120000024']);
        $extra = User::factory()->create(['phone_number' => '09120000025']);
        $this->attach($manager, $profile, $kept);
        $this->attach($manager, $profile, $alsoKept);

        Sanctum::actingAs($super);
        $this->patchJson('/api/admin/v1/school-managers/'.$profile->id, [
            'member_limit' => 1,
        ])->assertOk()->assertJsonPath('data.active_member_count', 2);

        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $extra->id,
        ])->assertStatus(422)->assertJsonPath('error', 'limit_reached');

        $this->assertSame(2, SchoolMembership::query()->where('school_manager_profile_id', $profile->id)->active()->count());
    }

    public function test_suspended_profile_cannot_use_the_panel(): void
    {
        [$manager, $profile] = $this->panel('09120000026', 5);
        $profile->update(['status' => SchoolManagerProfile::STATUS_SUSPENDED]);

        Sanctum::actingAs($manager);

        $this->getJson('/api/admin/v1/school-panel/me')->assertForbidden()->assertJsonPath('error', 'PANEL_SUSPENDED');
        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => '09120000027',
        ])->assertForbidden();
        $this->getJson('/api/admin/v1/school-panel/members')->assertForbidden();
    }

    /**
     * @return array{0: User, 1: SchoolManagerProfile}
     */
    private function panel(string $phone, int $limit, ?string $label = null): array
    {
        $manager = User::factory()->schoolManager()->create(['phone_number' => $phone]);
        $manager->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        $profile = SchoolManagerProfile::query()->create([
            'user_id' => $manager->id,
            'member_limit' => $limit,
            'label' => $label,
            'status' => SchoolManagerProfile::STATUS_ACTIVE,
        ]);

        return [$manager->fresh(), $profile];
    }

    private function attach(User $manager, SchoolManagerProfile $profile, User $member): void
    {
        SchoolMembership::query()->create([
            'school_manager_profile_id' => $profile->id,
            'user_id' => $member->id,
            'active_user_id' => $member->id,
            'attached_by' => $manager->id,
        ]);
    }
}
