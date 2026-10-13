<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\ApiAdminPermissionMiddleware;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWritePermissionAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_role_receives_user_and_story_write_permissions_from_seeder(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $this->assertNotNull($adminRole);

        foreach (['users.create', 'users.update', 'users.delete', 'stories.create', 'stories.update', 'stories.delete', 'roles.update'] as $name) {
            $this->assertTrue(
                $adminRole->permissions()->where('name', $name)->exists(),
                "Expected admin role to include permission [{$name}]"
            );
        }
    }

    public function test_middleware_maps_team_members_segment_to_underscore_resource(): void
    {
        $middleware = app(ApiAdminPermissionMiddleware::class);
        $method = new \ReflectionMethod($middleware, 'resolvePermission');
        $method->setAccessible(true);

        $permission = $method->invoke($middleware, 'team-members', 'update', 'api/admin/team-members/1');

        $this->assertSame('team_members.update', $permission);
    }

    public function test_middleware_accepts_roles_edit_for_roles_update_action(): void
    {
        $user = new class extends User
        {
            public function hasPermission(string $permission): bool
            {
                return $permission === 'roles.edit';
            }

            public function isSuperAdmin(): bool
            {
                return false;
            }
        };

        $user->role = User::ROLE_ADMIN;
        $user->id = 999002;
        $user->exists = true;

        Sanctum::actingAs($user);

        Route::middleware(['auth:sanctum', ApiAdminPermissionMiddleware::class])
            ->put('/_test/admin-write-permission/roles/{id}', fn () => response()->json(['ok' => true]));

        $response = $this->putJson('/_test/admin-write-permission/roles/1');

        $response->assertOk()->assertJson(['ok' => true]);
    }

    public function test_admin_user_update_is_not_blocked_by_missing_users_update_permission(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $target = User::factory()->create();

        $response = $this->putJson("/api/admin/users/{$target->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'status' => 'active',
        ]);

        $this->assertNotSame(403, $response->status());
        if ($response->status() === 403) {
            $response->assertJsonMissing(['required_permission' => 'users.update']);
        }
    }

    public function test_middleware_returns_403_when_full_admin_lacks_required_write_permission(): void
    {
        $user = new class extends User
        {
            public function hasPermission(string $permission): bool
            {
                return false;
            }

            public function isSuperAdmin(): bool
            {
                return false;
            }
        };

        $user->role = User::ROLE_ADMIN;
        $user->id = 999001;
        $user->exists = true;

        Sanctum::actingAs($user);

        Route::middleware(['auth:sanctum', ApiAdminPermissionMiddleware::class])
            ->put('/_test/admin-write-permission/users/{id}', fn () => response()->json(['ok' => true]));

        $response = $this->putJson('/_test/admin-write-permission/users/1');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'FORBIDDEN',
                'required_permission' => 'users.update',
            ]);
    }

    public function test_super_admin_bypasses_write_permission_middleware(): void
    {
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $target = User::factory()->create();

        $response = $this->putJson("/api/admin/users/{$target->id}", [
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'status' => 'active',
        ]);

        $this->assertNotSame(403, $response->status());
        if ($response->status() === 403) {
            $response->assertJsonMissing(['required_permission' => 'users.update']);
        }
    }
}
