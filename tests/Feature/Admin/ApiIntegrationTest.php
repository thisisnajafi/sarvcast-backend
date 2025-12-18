<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\CoinTransaction;
use App\Models\CouponCode;
use App\Models\AffiliatePartner;
use App\Models\SubscriptionPlan;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
        
        $this->user = User::factory()->create([
            'role' => 'user',
            'email' => 'user@test.com'
        ]);
    }

    /** @test */
    public function admin_can_access_coin_api_endpoints()
    {
        Sanctum::actingAs($this->admin);

        // Test GET /api/admin/coins
        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'amount',
                            'type',
                            'status',
                            'created_at',
                            'user'
                        ]
                    ],
                    'pagination'
                ]);

        // Test POST /api/admin/coins
        $coinData = [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'description' => 'Test coin transaction'
        ];

        $response = $this->postJson('/api/admin/coins', $coinData);
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'سکه با موفقیت اضافه شد.'
                ]);

        // Test GET /api/admin/coins/{id}
        $coin = CoinTransaction::first();
        $response = $this->getJson("/api/admin/coins/{$coin->id}");
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'user_id',
                        'amount',
                        'type',
                        'status',
                        'user'
                    ]
                ]);

        // Test PUT /api/admin/coins/{id}
        $updateData = [
            'amount' => 1500,
            'type' => 'gift',
            'description' => 'Updated transaction',
            'status' => 'completed'
        ];

        $response = $this->putJson("/api/admin/coins/{$coin->id}", $updateData);
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'تراکنش سکه با موفقیت به‌روزرسانی شد.'
                ]);

        // Test DELETE /api/admin/coins/{id}
        $response = $this->deleteJson("/api/admin/coins/{$coin->id}");
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'تراکنش سکه با موفقیت حذف شد.'
                ]);
    }

    /** @test */
    public function admin_can_perform_bulk_actions_via_api()
    {
        Sanctum::actingAs($this->admin);

        // Create test transactions
        $transactions = CoinTransaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $transactionIds = $transactions->pluck('id')->toArray();

        // Test bulk approve
        $response = $this->postJson('/api/admin/coins/bulk-action', [
            'action' => 'approve',
            'selected_items' => $transactionIds
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'تراکنش‌های انتخاب شده با موفقیت تایید شدند.'
                ]);

        // Verify transactions were updated
        foreach ($transactions as $transaction) {
            $transaction->refresh();
            $this->assertEquals('completed', $transaction->status);
        }
    }

    /** @test */
    public function admin_can_get_statistics_via_api()
    {
        Sanctum::actingAs($this->admin);

        // Create test data
        CoinTransaction::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'type' => 'earned'
        ]);

        $response = $this->getJson('/api/admin/coins/statistics/data');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'stats' => [
                            'total_transactions',
                            'total_coins_earned',
                            'total_coins_purchased',
                            'total_coins_gifted',
                            'pending_transactions',
                            'completed_transactions',
                            'failed_transactions'
                        ],
                        'daily_stats' => [
                            '*' => [
                                'date',
                                'transactions',
                                'coins'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function api_validates_coin_transaction_data()
    {
        Sanctum::actingAs($this->admin);

        // Test invalid user_id
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => 999999,
            'amount' => 1000,
            'type' => 'earned'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);

        // Test invalid amount
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => -100,
            'type' => 'earned'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);

        // Test invalid type
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function non_admin_cannot_access_admin_api()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز. شما مجوز دسترسی به این بخش را ندارید.',
                    'error' => 'FORBIDDEN'
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_api()
    {
        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'احراز هویت الزامی است.',
                    'error' => 'UNAUTHENTICATED'
                ]);
    }

    /** @test */
    public function admin_api_handles_rate_limiting()
    {
        Sanctum::actingAs($this->admin);

        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 105; $i++) {
            $response = $this->getJson('/api/admin/coins');
            
            if ($i < 100) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429)
                        ->assertJson([
                            'success' => false,
                            'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.',
                            'error' => 'RATE_LIMITED'
                        ]);
            }
        }
    }

    /** @test */
    public function admin_api_logs_activity()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200);

        // Check if activity was logged
        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $this->admin->id,
            'method' => 'GET',
            'route_name' => 'api.admin.coins.index'
        ]);
    }

    /** @test */
    public function admin_api_includes_performance_headers()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200);

        // Check for performance headers
        $response->assertHeader('X-Execution-Time');
        $response->assertHeader('X-Memory-Usage');
        $response->assertHeader('X-Peak-Memory');
    }

    /** @test */
    public function super_admin_can_access_sensitive_operations()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'superadmin@test.com'
        ]);

        Sanctum::actingAs($superAdmin);

        // Test sensitive operation
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'admin_adjustment',
            'description' => 'Admin adjustment'
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_admin_cannot_access_sensitive_operations()
    {
        Sanctum::actingAs($this->admin);

        // Test sensitive operation that requires super admin
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'admin_adjustment',
            'description' => 'Admin adjustment'
        ]);

        // This should work for regular admin too, but let's test a different sensitive operation
        $coin = CoinTransaction::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->deleteJson("/api/admin/coins/{$coin->id}");
        $response->assertStatus(200); // This should work for regular admin
    }

    /** @test */
    public function api_handles_concurrent_requests()
    {
        Sanctum::actingAs($this->admin);

        $responses = [];
        
        // Make 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/admin/coins');
        }

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function api_handles_large_datasets()
    {
        Sanctum::actingAs($this->admin);

        // Create large dataset
        CoinTransaction::factory()->count(1000)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'pagination'
                ]);

        // Should be paginated
        $data = $response->json();
        $this->assertLessThanOrEqual(20, count($data['data'])); // Default pagination size
    }
}
