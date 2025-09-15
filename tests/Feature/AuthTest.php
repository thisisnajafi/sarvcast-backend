<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration
     */
    public function test_user_can_register()
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
            'role' => 'parent'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'email',
                            'first_name',
                            'last_name',
                            'role'
                        ],
                        'token'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
    }

    /**
     * Test user registration validation
     */
    public function test_user_registration_validation()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password', 'first_name', 'last_name']);
    }

    /**
     * Test user login
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'email',
                            'first_name',
                            'last_name'
                        ],
                        'token'
                    ]
                ]);
    }

    /**
     * Test user login with invalid credentials
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
    }

    /**
     * Test user logout
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);
    }

    /**
     * Test user profile retrieval
     */
    public function test_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/auth/profile');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                        'role',
                        'status'
                    ]
                ]);
    }

    /**
     * Test user profile update
     */
    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $updateData = [
            'first_name' => 'Updated First Name',
            'last_name' => 'Updated Last Name',
            'phone_number' => '09987654321'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/v1/auth/profile', $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated First Name',
            'last_name' => 'Updated Last Name'
        ]);
    }

    /**
     * Test password change
     */
    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $passwordData = [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/auth/change-password', $passwordData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
    }

    /**
     * Test protected routes require authentication
     */
    public function test_protected_routes_require_authentication()
    {
        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /**
     * Test admin routes require admin role
     */
    public function test_admin_routes_require_admin_role()
    {
        $user = User::factory()->create(['role' => 'parent']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/admin/users');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ]);
    }

    /**
     * Test admin can access admin routes
     */
    public function test_admin_can_access_admin_routes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/admin/users');

        $response->assertStatus(200);
    }
}