<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BladeAdminRetiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_next_admin_dashboard(): void
    {
        config(['app.admin_dashboard_url' => 'https://admin.manjiapp.ir']);

        $this->get('/')
            ->assertRedirect('https://admin.manjiapp.ir');
    }

    public function test_old_admin_web_paths_redirect_and_do_not_render_blade(): void
    {
        config(['app.admin_dashboard_url' => 'https://admin.manjiapp.ir']);

        $admin = User::factory()->admin()->create();

        foreach ([
            '/admin',
            '/admin/auth/login',
            '/admin/stories',
            '/admin/schools',
            '/admin/users',
            '/admin/dashboard',
        ] as $path) {
            $response = $this->actingAs($admin, 'web')->get($path);

            $response->assertRedirect('https://admin.manjiapp.ir');
            $this->assertFalse(
                $response->headers->has('Content-Type') && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
                && $response->status() === 200,
                "Expected redirect away from Blade for {$path}"
            );
            $this->assertStringNotContainsString('admin.layouts.app', (string) $response->getContent());
        }
    }

    public function test_admin_api_routes_remain_available(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $admin = User::factory()->admin()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin);

        // Sanity: admin JSON API still serves (not a redirect to Next).
        $this->getJson('/api/admin/schools')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
