<?php

namespace Tests\Feature\Payment;

use App\Models\CouponCode;
use App\Models\CouponUsage;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentCallbackIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_callback_uses_plan_duration_days_from_database(): void
    {
        Http::fake([
            'https://sandbox.zarinpal.com/*' => Http::response([
                'data' => [
                    'code' => 100,
                    'ref_id' => 123456,
                    'card_pan' => '621986******1234',
                    'fee' => 0,
                ],
            ], 200),
        ]);

        SubscriptionPlan::factory()->create([
            'slug' => '1month',
            'duration_days' => 45,
            'price' => 100000,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'type' => '1month',
            'status' => 'pending',
            'start_date' => null,
            'end_date' => null,
            'price' => 100000,
        ]);

        $authority = 'A000000000000000000000000000test45';
        Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 100000,
            'payment_method' => 'zarinpal',
            'status' => 'pending',
            'transaction_id' => $authority,
            'payment_metadata' => [
                'plan_id' => SubscriptionPlan::where('slug', '1month')->value('id'),
            ],
        ]);

        $service = app(PaymentService::class);
        $result = $service->processCallback([
            'Authority' => $authority,
            'Status' => 'OK',
        ]);

        $this->assertTrue($result['success']);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->end_date);

        $durationDays = $subscription->start_date->diffInDays($subscription->end_date);
        $this->assertGreaterThanOrEqual(44, $durationDays);
        $this->assertLessThanOrEqual(46, $durationDays);
    }

    public function test_process_callback_records_coupon_usage_when_coupon_code_in_metadata(): void
    {
        Http::fake([
            'https://sandbox.zarinpal.com/*' => Http::response([
                'data' => [
                    'code' => 100,
                    'ref_id' => 654321,
                    'fee' => 0,
                ],
            ], 200),
        ]);

        SubscriptionPlan::factory()->create([
            'slug' => '1month',
            'duration_days' => 30,
            'price' => 100000,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $coupon = CouponCode::factory()->create([
            'type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'usage_limit' => 100,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'type' => '1month',
            'status' => 'pending',
            'start_date' => null,
            'end_date' => null,
            'price' => 90000,
        ]);

        $authority = 'A000000000000000000000000000coupon';
        Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 90000,
            'payment_method' => 'zarinpal',
            'status' => 'pending',
            'transaction_id' => $authority,
            'payment_metadata' => [
                'coupon_code' => $coupon->code,
                'plan_id' => SubscriptionPlan::where('slug', '1month')->value('id'),
            ],
        ]);

        $service = app(PaymentService::class);
        $result = $service->processCallback([
            'Authority' => $authority,
            'Status' => 'OK',
        ]);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('coupon_usages', [
            'coupon_code_id' => $coupon->id,
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);

        $this->assertSame(1, CouponUsage::where('coupon_code_id', $coupon->id)->count());
    }
}
