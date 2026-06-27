<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MyketSubscriptionVerifyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        SubscriptionPlan::create([
            'name' => 'اشتراک یک ماهه',
            'slug' => '1month',
            'description' => 'Test plan',
            'duration_days' => 30,
            'price' => 50000,
            'currency' => 'IRT',
            'discount_percentage' => 0,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
            'myket_product_id' => '1-month-sub',
            'cafebazaar_product_id' => '1-month-sub',
            'features' => [],
        ]);
    }

    public function test_myket_verify_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/subscriptions/myket/verify', [
            'purchase_token' => 'token-123',
            'product_id' => '1-month-sub',
            'billing_platform' => 'myket',
        ]);

        $response->assertStatus(401);
    }

    public function test_myket_verify_rejects_non_myket_flavor(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/subscriptions/myket/verify', [
            'purchase_token' => 'token-123',
            'product_id' => '1-month-sub',
            'billing_platform' => 'cafebazaar',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_myket_verify_returns_503_when_api_key_missing(): void
    {
        config(['services.myket.api_key' => null]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/subscriptions/myket/verify', [
            'purchase_token' => 'token-123',
            'product_id' => '1-month-sub',
            'billing_platform' => 'myket',
        ]);

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'error_code' => 'myket_config_missing',
            ]);
    }

    public function test_myket_verify_creates_subscription_on_success(): void
    {
        config([
            'services.myket.api_key' => 'test-access-token',
            'services.myket.package_name' => 'com.avinpishtazan.manji',
        ]);

        Http::fake([
            'developer.myket.ir/*' => Http::response([
                'purchaseState' => 0,
                'purchaseTime' => now()->getTimestampMs(),
                'orderId' => 'MK-ORDER-1',
                'consumptionState' => 0,
                'kind' => 'androidpublisher#productPurchase',
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/subscriptions/myket/verify', [
            'purchase_token' => 'myket-purchase-token-abc',
            'product_id' => '1-month-sub',
            'order_id' => 'MK-ORDER-1',
            'billing_platform' => 'myket',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'payment',
                    'subscription',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'purchase_token' => 'myket-purchase-token-abc',
            'billing_platform' => 'myket',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'type' => '1month',
            'billing_platform' => 'myket',
            'status' => 'active',
        ]);
    }
}
