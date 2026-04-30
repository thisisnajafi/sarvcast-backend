<?php

namespace Tests\Feature\Admin;

use App\Models\CouponCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCouponsApiStandardizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupons_index_returns_canonical_meta_and_legacy_pagination(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'coupons-meta@test.com',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/coupons?page=1&perPage=10');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['page', 'perPage', 'total', 'lastPage'],
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_coupons_export_returns_csv_for_super_admin(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'coupons-export@test.com',
            'status' => 'active',
        ]);

        Sanctum::actingAs($super);

        $response = $this->get('/api/admin/coupons/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function test_coupons_bulk_accepts_selected_items_alias(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'coupons-bulk@test.com',
            'status' => 'active',
        ]);

        $coupon = CouponCode::create([
            'code' => 'BULKTEST1',
            'name' => 'BULKTEST1',
            'type' => 'percentage',
            'discount_value' => 10,
            'created_by' => $admin->id,
            'is_active' => false,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/coupons/bulk-action', [
            'action' => 'activate',
            'selected_items' => [$coupon->id],
        ]);

        $response->assertOk();
        $this->assertTrue($coupon->fresh()->is_active);
    }
}
