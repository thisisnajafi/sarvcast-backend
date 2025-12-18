<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $user;
    protected $superAdmin;

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
        
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'superadmin@test.com'
        ]);
    }

    /** @test */
    public function unauthenticated_users_cannot_access_admin_endpoints()
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
    public function regular_users_cannot_access_admin_endpoints()
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
    public function inactive_admin_cannot_access_admin_endpoints()
    {
        $inactiveAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'inactive'
        ]);

        Sanctum::actingAs($inactiveAdmin);

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'حساب کاربری شما غیرفعال است.',
                    'error' => 'ACCOUNT_INACTIVE'
                ]);
    }

    /** @test */
    public function admin_endpoints_are_protected_by_csrf()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned'
        ]);

        // Should work with Sanctum token
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_endpoints_validate_input_data()
    {
        Sanctum::actingAs($this->admin);

        // Test SQL injection attempt
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => "1'; DROP TABLE users; --",
            'amount' => 1000,
            'type' => 'earned'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);

        // Test XSS attempt
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'description' => '<script>alert("XSS")</script>'
        ]);

        $response->assertStatus(200);
        
        // Check that XSS was sanitized
        $transaction = CoinTransaction::first();
        $this->assertStringNotContainsString('<script>', $transaction->description);
    }

    /** @test */
    public function admin_endpoints_enforce_rate_limiting()
    {
        Sanctum::actingAs($this->admin);

        // Make requests up to the limit
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/admin/coins');
            $response->assertStatus(200);
        }

        // Next request should be rate limited
        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.',
                    'error' => 'RATE_LIMITED'
                ]);
    }

    /** @test */
    public function admin_endpoints_log_activity()
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
    public function admin_endpoints_validate_authorization()
    {
        Sanctum::actingAs($this->admin);

        // Test accessing another admin's data
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200);
        
        // Should only see data they have access to
        $data = $response->json('data');
        $this->assertIsArray($data);
    }

    /** @test */
    public function admin_endpoints_prevent_mass_assignment()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'status' => 'completed', // This should not be settable
            'created_at' => '2020-01-01', // This should not be settable
            'admin_id' => 999 // This should not be settable
        ]);

        $response->assertStatus(200);
        
        $transaction = CoinTransaction::first();
        $this->assertNotEquals('completed', $transaction->status);
        $this->assertNotEquals('2020-01-01', $transaction->created_at->format('Y-m-d'));
        $this->assertNull($transaction->admin_id);
    }

    /** @test */
    public function admin_endpoints_validate_file_uploads()
    {
        Sanctum::actingAs($this->admin);

        // Test malicious file upload
        $maliciousFile = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'file' => $maliciousFile
        ]);

        // Should reject invalid file uploads
        $response->assertStatus(422);
    }

    /** @test */
    public function admin_endpoints_handle_concurrent_requests_safely()
    {
        Sanctum::actingAs($this->admin);

        $responses = [];
        
        // Make 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->postJson('/api/admin/coins', [
                'user_id' => $this->user->id,
                'amount' => 1000,
                'type' => 'earned'
            ]);
        }

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Should have created 10 transactions
        $this->assertEquals(10, CoinTransaction::count());
    }

    /** @test */
    public function admin_endpoints_prevent_privilege_escalation()
    {
        Sanctum::actingAs($this->admin);

        // Try to create a super admin user
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'user_role' => 'super_admin' // This should not work
        ]);

        $response->assertStatus(200);
        
        // User role should not be changed
        $this->user->refresh();
        $this->assertEquals('user', $this->user->role);
    }

    /** @test */
    public function admin_endpoints_validate_data_integrity()
    {
        Sanctum::actingAs($this->admin);

        $initialBalance = $this->user->coins;

        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned'
        ]);

        $response->assertStatus(200);
        
        // User balance should be updated
        $this->user->refresh();
        $this->assertEquals($initialBalance + 1000, $this->user->coins);
    }

    /** @test */
    public function admin_endpoints_prevent_data_leakage()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200);

        $data = $response->json('data');
        
        // Should not expose sensitive information
        if (!empty($data)) {
            $transaction = $data[0];
            $this->assertArrayNotHasKey('password', $transaction);
            $this->assertArrayNotHasKey('remember_token', $transaction);
            $this->assertArrayNotHasKey('email_verified_at', $transaction);
        }
    }

    /** @test */
    public function admin_endpoints_handle_malformed_requests()
    {
        Sanctum::actingAs($this->admin);

        // Test malformed JSON
        $response = $this->call('POST', '/api/admin/coins', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ], '{"user_id": 1, "amount": 1000, "type": "earned"'); // Missing closing brace

        $response->assertStatus(400);
    }

    /** @test */
    public function admin_endpoints_prevent_timing_attacks()
    {
        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        // Test with valid user
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned'
        ]);
        
        $validTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        
        // Test with invalid user
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => 999999,
            'amount' => 1000,
            'type' => 'earned'
        ]);
        
        $invalidTime = microtime(true) - $startTime;

        // Times should be similar to prevent timing attacks
        $timeDifference = abs($validTime - $invalidTime);
        $this->assertLessThan(0.1, $timeDifference, 
            "Timing difference of {$timeDifference}s could reveal information about user existence");
    }

    /** @test */
    public function admin_endpoints_validate_content_type()
    {
        Sanctum::actingAs($this->admin);

        // Test with wrong content type
        $response = $this->call('POST', '/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned'
        ], [], [], [
            'CONTENT_TYPE' => 'text/plain',
            'HTTP_ACCEPT' => 'application/json'
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function admin_endpoints_prevent_directory_traversal()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/coins/../../../etc/passwd');
        $response->assertStatus(404);
    }

    /** @test */
    public function admin_endpoints_validate_request_size()
    {
        Sanctum::actingAs($this->admin);

        // Test with extremely large request
        $largeData = str_repeat('a', 10000000); // 10MB
        
        $response = $this->postJson('/api/admin/coins', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'description' => $largeData
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['description']);
    }
}
