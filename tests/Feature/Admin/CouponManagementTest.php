<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\CouponCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
    }

    /** @test */
    public function admin_can_view_coupons_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.coupons.index');
        $response->assertSee('مدیریت کدهای تخفیف');
    }

    /** @test */
    public function admin_can_create_coupon()
    {
        $couponData = [
            'code' => 'TEST2024',
            'type' => 'percentage',
            'value' => 20,
            'description' => 'Test coupon',
            'usage_limit' => 100,
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), $couponData);

        $response->assertRedirect(route('admin.coupons.index'));
        $response->assertSessionHas('success', 'کد تخفیف با موفقیت ایجاد شد.');
        
        $this->assertDatabaseHas('coupon_codes', [
            'code' => 'TEST2024',
            'type' => 'percentage',
            'value' => 20,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_can_view_coupon()
    {
        $coupon = CouponCode::factory()->create([
            'code' => 'VIEW2024',
            'type' => 'fixed_amount',
            'value' => 50000,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.coupons.show', $coupon));

        $response->assertStatus(200);
        $response->assertViewIs('admin.coupons.show');
        $response->assertSee($coupon->code);
    }

    /** @test */
    public function admin_can_update_coupon()
    {
        $coupon = CouponCode::factory()->create([
            'code' => 'UPDATE2024',
            'type' => 'percentage',
            'value' => 10,
        ]);

        $updateData = [
            'code' => 'UPDATED2024',
            'type' => 'fixed_amount',
            'value' => 25000,
            'description' => 'Updated coupon',
            'status' => 'inactive',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.coupons.update', $coupon), $updateData);

        $response->assertRedirect(route('admin.coupons.index'));
        $response->assertSessionHas('success', 'کد تخفیف با موفقیت به‌روزرسانی شد.');
        
        $this->assertDatabaseHas('coupon_codes', [
            'id' => $coupon->id,
            'code' => 'UPDATED2024',
            'type' => 'fixed_amount',
            'value' => 25000,
        ]);
    }

    /** @test */
    public function admin_can_delete_coupon()
    {
        $coupon = CouponCode::factory()->create([
            'code' => 'DELETE2024',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.coupons.destroy', $coupon));

        $response->assertRedirect(route('admin.coupons.index'));
        $response->assertSessionHas('success', 'کد تخفیف با موفقیت حذف شد.');
        
        $this->assertDatabaseMissing('coupon_codes', [
            'id' => $coupon->id,
        ]);
    }

    /** @test */
    public function admin_can_perform_bulk_actions_on_coupons()
    {
        $coupons = CouponCode::factory()->count(3)->create([
            'status' => 'inactive',
        ]);

        $couponIds = $coupons->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.bulk-action'), [
                'action' => 'activate',
                'selected_items' => $couponIds,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'کدهای تخفیف انتخاب شده با موفقیت فعال شدند.');
        
        foreach ($coupons as $coupon) {
            $this->assertDatabaseHas('coupon_codes', [
                'id' => $coupon->id,
                'status' => 'active',
            ]);
        }
    }

    /** @test */
    public function admin_can_export_coupons()
    {
        CouponCode::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.coupons.export'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'گزارش کدهای تخفیف آماده دانلود است.');
    }

    /** @test */
    public function admin_can_view_coupon_statistics()
    {
        CouponCode::factory()->count(10)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.coupons.statistics'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.coupons.statistics');
        $response->assertSee('آمار کدهای تخفیف');
    }

    /** @test */
    public function coupon_validation_works()
    {
        $invalidData = [
            'code' => '', // Empty code
            'type' => 'invalid_type',
            'value' => -10, // Negative value
            'usage_limit' => -5, // Negative usage limit
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), $invalidData);

        $response->assertSessionHasErrors(['code', 'type', 'value', 'usage_limit']);
    }

    /** @test */
    public function coupon_code_must_be_unique()
    {
        CouponCode::factory()->create(['code' => 'UNIQUE2024']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'UNIQUE2024',
                'type' => 'percentage',
                'value' => 20,
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    /** @test */
    public function percentage_coupon_value_cannot_exceed_100()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'PERCENT2024',
                'type' => 'percentage',
                'value' => 150, // Exceeds 100%
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors(['value']);
    }

    /** @test */
    public function coupon_expiration_date_must_be_future()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'EXPIRED2024',
                'type' => 'percentage',
                'value' => 20,
                'expires_at' => now()->subDays(1), // Past date
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors(['expires_at']);
    }

    /** @test */
    public function non_admin_cannot_access_coupon_management()
    {
        $user = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($user)
            ->get(route('admin.coupons.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function coupon_code_is_automatically_uppercased()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'lowercase2024',
                'type' => 'percentage',
                'value' => 20,
                'status' => 'active',
            ]);

        $this->assertDatabaseHas('coupon_codes', [
            'code' => 'LOWERCASE2024',
        ]);
    }
}
