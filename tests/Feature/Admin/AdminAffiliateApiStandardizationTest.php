<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAffiliateApiStandardizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliate_index_returns_canonical_meta_and_legacy_pagination(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/affiliate?page=1&perPage=10');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta' => ['page', 'perPage', 'total', 'lastPage'],
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_affiliate_export_returns_csv_for_super_admin(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        Sanctum::actingAs($super);

        $response = $this->get('/api/admin/affiliate/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }
}
