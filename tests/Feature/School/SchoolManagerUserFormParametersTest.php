<?php

namespace Tests\Feature\School;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolManagerProfile;
use App\Models\User;
use App\Support\SchoolAccessCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolManagerUserFormParametersTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cannot_promote_without_member_limit(): void
    {
        $super = User::factory()->superAdmin()->create();
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => '09120002201',
        ]);
        $schoolRoleId = (int) Role::query()->where('name', 'school_manager')->value('id');

        Sanctum::actingAs($super);

        $this->putJson('/api/admin/users/'.$parent->id, [
            'first_name' => $parent->first_name,
            'last_name' => $parent->last_name,
            'phone_number' => $parent->phone_number,
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'role_ids' => [$schoolRoleId],
        ])->assertStatus(422)
            ->assertJsonPath('errors.member_limit.0', 'سقف اعضا را وارد کنید.');

        $this->assertDatabaseMissing('school_manager_profiles', [
            'user_id' => $parent->id,
        ]);
    }

    public function test_super_admin_create_and_update_persist_member_limit_and_label(): void
    {
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $created = $this->postJson('/api/admin/users', [
            'first_name' => 'نیما',
            'last_name' => 'مدیر',
            'phone_number' => '09120002202',
            'password' => 'password123',
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'member_limit' => 50,
            'label' => 'مدرسه الف',
        ])->assertCreated()
            ->assertJsonPath('data.role', 'school_manager')
            ->assertJsonPath('data.school_manager_profile.member_limit', 50)
            ->assertJsonPath('data.school_manager_profile.label', 'مدرسه الف');

        $userId = (int) $created->json('data.id');
        $this->assertDatabaseHas('school_manager_profiles', [
            'user_id' => $userId,
            'member_limit' => 50,
            'label' => 'مدرسه الف',
            'status' => SchoolManagerProfile::STATUS_ACTIVE,
        ]);

        $this->putJson('/api/admin/users/'.$userId, [
            'first_name' => 'نیما',
            'last_name' => 'مدیر',
            'phone_number' => '09120002202',
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'member_limit' => 80,
            'label' => 'مدرسه ب',
        ])->assertOk()
            ->assertJsonPath('data.school_manager_profile.member_limit', 80)
            ->assertJsonPath('data.school_manager_profile.label', 'مدرسه ب');
    }

    public function test_seeded_admin_cannot_change_member_limit(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => '09120002203',
        ]);
        $admin->syncRoles([$adminRole->id]);

        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);
        $manager = User::factory()->create([
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => '09120002204',
        ]);
        $manager->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        SchoolManagerProfile::query()->create([
            'user_id' => $manager->id,
            'member_limit' => 40,
            'label' => 'ثابت',
            'status' => SchoolManagerProfile::STATUS_ACTIVE,
            'created_by' => $super->id,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/users/'.$manager->id, [
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name,
            'phone_number' => $manager->phone_number,
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'member_limit' => 99,
            'label' => 'هک',
        ])->assertStatus(403);

        $this->assertDatabaseHas('school_manager_profiles', [
            'user_id' => $manager->id,
            'member_limit' => 40,
            'label' => 'ثابت',
        ]);
    }

    public function test_school_manager_cannot_change_own_limit_via_users_api(): void
    {
        $super = User::factory()->superAdmin()->create();
        $manager = User::factory()->create([
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => '09120002205',
        ]);
        $manager->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        SchoolManagerProfile::query()->create([
            'user_id' => $manager->id,
            'member_limit' => 12,
            'label' => 'خودم',
            'status' => SchoolManagerProfile::STATUS_ACTIVE,
            'created_by' => $super->id,
        ]);

        Sanctum::actingAs($manager);

        $this->putJson('/api/admin/users/'.$manager->id, [
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name,
            'phone_number' => $manager->phone_number,
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'member_limit' => 999,
        ])->assertStatus(403);

        $this->assertDatabaseHas('school_manager_profiles', [
            'user_id' => $manager->id,
            'member_limit' => 12,
        ]);
    }

    public function test_rbac_promotion_still_requires_member_limit(): void
    {
        $super = User::factory()->superAdmin()->create();
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => '09120002206',
        ]);
        $schoolRoleId = (int) Role::query()->where('name', 'school_manager')->value('id');
        $permissionIds = Permission::query()
            ->whereIn('name', SchoolAccessCatalog::panelPermissionNames())
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        Sanctum::actingAs($super);

        $this->putJson('/api/admin/users/'.$parent->id, [
            'first_name' => $parent->first_name,
            'last_name' => $parent->last_name,
            'phone_number' => $parent->phone_number,
            'role' => User::ROLE_PARENT,
            'status' => User::STATUS_ACTIVE,
            'role_ids' => [$schoolRoleId],
            'direct_permission_ids' => $permissionIds,
        ])->assertStatus(422);

        $this->putJson('/api/admin/users/'.$parent->id, [
            'first_name' => $parent->first_name,
            'last_name' => $parent->last_name,
            'phone_number' => $parent->phone_number,
            'role' => User::ROLE_PARENT,
            'status' => User::STATUS_ACTIVE,
            'role_ids' => [$schoolRoleId],
            'direct_permission_ids' => $permissionIds,
            'member_limit' => 0,
            'label' => null,
        ])->assertOk()
            ->assertJsonPath('data.school_manager_profile.member_limit', 0);
    }
}
