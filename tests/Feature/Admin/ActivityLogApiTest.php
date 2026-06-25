<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_list_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        ActivityLog::query()->create([
            'channel' => ActivityLog::CHANNEL_ADMIN,
            'actor_user_id' => $admin->id,
            'actor_type' => 'admin',
            'action' => 'created',
            'subject_type' => 'story',
            'subject_id' => '1',
            'description' => 'ایجاد در stories #1',
            'status' => ActivityLog::STATUS_SUCCESS,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/activity-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'channel',
                        'action',
                        'description',
                        'occurred_at',
                    ],
                ],
                'meta' => ['page', 'perPage', 'total', 'lastPage'],
            ]);
    }

    public function test_admin_without_audit_permission_cannot_list_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/activity-logs')
            ->assertStatus(403)
            ->assertJsonPath('required_permission', 'audit.view');
    }

    public function test_admin_with_audit_view_permission_can_list_logs(): void
    {
        $permission = Permission::query()->create([
            'name' => 'audit.view',
            'display_name' => 'مشاهده گزارش فعالیت',
            'group' => 'audit',
            'is_active' => true,
        ]);

        $role = Role::query()->create([
            'name' => 'auditor',
            'display_name' => 'بازرس',
            'description' => 'Test auditor role',
        ]);
        $role->permissions()->attach($permission);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole($role);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/activity-logs')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_post_creates_activity_log_row(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->postJson('/api/admin/coins', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'description' => 'Test coin transaction',
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'channel' => ActivityLog::CHANNEL_ADMIN,
            'actor_user_id' => $admin->id,
            'action' => 'created',
            'status' => ActivityLog::STATUS_SUCCESS,
        ]);
    }

    public function test_activity_log_detail_endpoint_returns_single_row(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $log = ActivityLog::query()->create([
            'channel' => ActivityLog::CHANNEL_ADMIN,
            'actor_user_id' => $admin->id,
            'actor_type' => 'admin',
            'action' => 'updated',
            'description' => 'ویرایش در stories #2',
            'status' => ActivityLog::STATUS_SUCCESS,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        $this->getJson("/api/admin/activity-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.action', 'updated');
    }

    public function test_stats_endpoint_returns_counts(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        ActivityLog::query()->create([
            'channel' => ActivityLog::CHANNEL_ADMIN,
            'actor_type' => 'admin',
            'action' => 'created',
            'status' => ActivityLog::STATUS_SUCCESS,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        $this->getJson('/api/admin/activity-logs/stats')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['last_24h', 'last_7d', 'by_channel', 'top_actions_7d', 'security_alerts', 'failed_logins_24h'],
            ]);
    }

    public function test_super_admin_can_export_activity_logs_csv(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        ActivityLog::query()->create([
            'channel' => ActivityLog::CHANNEL_ADMIN,
            'actor_user_id' => $admin->id,
            'actor_type' => 'admin',
            'action' => 'created',
            'description' => 'تست خروجی',
            'status' => ActivityLog::STATUS_SUCCESS,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->get('/api/admin/activity-logs/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('تست خروجی', $response->getContent());
    }

    public function test_admin_without_export_permission_cannot_export(): void
    {
        $permission = Permission::query()->create([
            'name' => 'audit.view',
            'display_name' => 'مشاهده گزارش فعالیت',
            'group' => 'audit',
            'is_active' => true,
        ]);

        $role = Role::query()->create([
            'name' => 'auditor',
            'display_name' => 'بازرس',
            'description' => 'Test auditor role',
        ]);
        $role->permissions()->attach($permission);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole($role);
        Sanctum::actingAs($admin);

        $this->get('/api/admin/activity-logs/export')
            ->assertStatus(403)
            ->assertJsonPath('required_permission', 'audit.export');
    }
}
