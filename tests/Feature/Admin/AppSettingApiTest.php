<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppSettingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_get_and_update_app_settings(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/app-settings')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['settings']]);

        $this->putJson('/api/admin/app-settings', [
            'key' => AppSetting::PUBLIC_VOICE_ACTORS_REQUIRE_PHOTO,
            'value' => true,
        ])->assertOk()
            ->assertJsonPath('data.value', true);

        $this->assertTrue(AppSetting::getBool(AppSetting::PUBLIC_VOICE_ACTORS_REQUIRE_PHOTO, false));

        $this->putJson('/api/admin/app-settings', [
            'key' => AppSetting::SITE_ANNOUNCEMENT,
            'value' => 'پیام تست',
        ])->assertOk()
            ->assertJsonPath('data.value', 'پیام تست');
    }

    public function test_non_admin_gets_forbidden_on_update(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/admin/app-settings', [
            'key' => AppSetting::MAINTENANCE_MODE,
            'value' => '1',
        ])->assertForbidden();
    }
}
