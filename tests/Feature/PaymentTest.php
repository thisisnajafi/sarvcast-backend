<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test initiate payment
     */
    public function test_can_initiate_payment()
    {
        $paymentData = [
            'amount' => 100000,
            'gateway' => 'zarinpal',
            'description' => 'Test payment'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/v1/payments/initiate', $paymentData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'payment_id',
                        'gateway',
                        'amount',
                        'status',
                        'payment_url'
                    ]
                ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'amount' => 100000,
            'gateway' => 'zarinpal',
            'status' => 'pending'
        ]);
    }

    /**
     * Test payment initiation validation
     */
    public function test_payment_initiation_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/v1/payments/initiate', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount', 'gateway']);
    }

    /**
     * Test verify payment
     */
    public function test_can_verify_payment()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'gateway' => 'zarinpal'
        ]);

        $verifyData = [
            'payment_id' => $payment->id,
            'transaction_id' => 'test_transaction_123'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/v1/payments/verify', $verifyData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'payment_id',
                        'status',
                        'transaction_id'
                    ]
                ]);
    }

    /**
     * Test get payment history
     */
    public function test_can_get_payment_history()
    {
        Payment::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/payments/history');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'payments' => [
                            '*' => [
                                'id',
                                'amount',
                                'gateway',
                                'status',
                                'created_at'
                            ]
                        ],
                        'pagination'
                    ]
                ]);
    }

    /**
     * Test payment history filtering
     */
    public function test_can_filter_payment_history()
    {
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'failed'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/payments/history?status=completed');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'payments'
                    ]
                ]);
    }

    /**
     * Test get subscription plans
     */
    public function test_can_get_subscription_plans()
    {
        $response = $this->getJson('/api/v1/subscriptions/plans');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'plans' => [
                            '*' => [
                                'id',
                                'name',
                                'description',
                                'price',
                                'duration_days',
                                'features'
                            ]
                        ]
                    ]
                ]);
    }

    /**
     * Test create subscription
     */
    public function test_can_create_subscription()
    {
        $subscriptionData = [
            'plan_id' => 1,
            'payment_method' => 'zarinpal'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/v1/subscriptions', $subscriptionData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'subscription_id',
                        'plan_id',
                        'status',
                        'expires_at'
                    ]
                ]);
    }

    /**
     * Test get user subscription status
     */
    public function test_can_get_subscription_status()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/subscriptions/status');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'has_subscription',
                        'subscription' => [
                            'id',
                            'plan_id',
                            'status',
                            'expires_at'
                        ]
                    ]
                ]);
    }

    /**
     * Test cancel subscription
     */
    public function test_can_cancel_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/v1/subscriptions/{$subscription->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Subscription cancelled successfully'
                ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'cancelled'
        ]);
    }

    /**
     * Test payment callback (ZarinPal)
     */
    public function test_zarinpal_payment_callback()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'gateway' => 'zarinpal',
            'status' => 'pending'
        ]);

        $callbackData = [
            'Authority' => 'test_authority_123',
            'Status' => 'OK'
        ];

        $response = $this->getJson("/payment/zarinpal/callback?" . http_build_query($callbackData));

        $response->assertStatus(302); // Redirect to success/failure page
    }

    /**
     * Test payment callback (Pay.ir)
     */
    public function test_payir_payment_callback()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'gateway' => 'payir',
            'status' => 'pending'
        ]);

        $callbackData = [
            'token' => 'test_token_123',
            'status' => '1'
        ];

        $response = $this->getJson("/payment/payir/callback?" . http_build_query($callbackData));

        $response->assertStatus(302); // Redirect to success/failure page
    }

    /**
     * Test payment success page
     */
    public function test_payment_success_page()
    {
        $response = $this->get('/payment/success');

        $response->assertStatus(200)
                ->assertViewIs('payment.success');
    }

    /**
     * Test payment failure page
     */
    public function test_payment_failure_page()
    {
        $response = $this->get('/payment/failure');

        $response->assertStatus(200)
                ->assertViewIs('payment.failure');
    }

    /**
     * Test payment requires authentication
     */
    public function test_payment_requires_authentication()
    {
        $response = $this->postJson('/api/v1/payments/initiate', [
            'amount' => 100000,
            'gateway' => 'zarinpal'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /**
     * Test subscription requires authentication
     */
    public function test_subscription_requires_authentication()
    {
        $response = $this->postJson('/api/v1/subscriptions', [
            'plan_id' => 1
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }
}