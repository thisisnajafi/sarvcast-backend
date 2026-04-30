<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminContentModerationApiStandardizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_moderation_index_returns_canonical_meta_and_legacy_pagination(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'content-mod-meta@test.com',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/content-moderation?page=1&perPage=10');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta' => ['page', 'perPage', 'total', 'lastPage'],
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_content_moderation_export_returns_csv(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'content-mod-export@test.com',
            'status' => 'active',
        ]);

        Sanctum::actingAs($super);

        $response = $this->get('/api/admin/content-moderation/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }
}
