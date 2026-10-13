<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register()
    {
        $phone = '09123456789';
        Cache::put("otp_{$phone}_login", '123456', 300);

        $response = $this->postJson('/api/v1/auth/register', [
            'phone_number' => $phone,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'verification_code' => '123456',
            'role' => 'parent',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'phone_number',
                        'first_name',
                        'last_name',
                        'role',
                    ],
                    'token',
                ],
            ])
            ->assertJsonPath('data.user.phone_number', $phone);

        $this->assertDatabaseHas('users', [
            'phone_number' => $phone,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    public function test_user_registration_validation()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number', 'first_name', 'last_name', 'verification_code']);
    }

    public function test_user_can_login()
    {
        $phone = '09123456780';
        $user = User::factory()->create([
            'phone_number' => $phone,
        ]);
        Cache::put("otp_{$phone}_login", '654321', 300);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone_number' => $phone,
            'verification_code' => '654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'phone_number',
                        'first_name',
                        'last_name',
                    ],
                    'token',
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.phone_number', $phone);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $phone = '09123456781';
        User::factory()->create(['phone_number' => $phone]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone_number' => $phone,
            'verification_code' => '000000',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'کد تایید نامعتبر یا منقضی شده است',
            ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'خروج با موفقیت انجام شد',
            ]);
    }

    public function test_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/auth/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'phone_number',
                    'first_name',
                    'last_name',
                    'role',
                    'status',
                ],
            ])
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson('/api/v1/auth/profile', [
            'first_name' => 'Updated First Name',
            'last_name' => 'Updated Last Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'پروفایل با موفقیت به‌روزرسانی شد',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated First Name',
            'last_name' => 'Updated Last Name',
        ]);
    }

    public function test_user_can_change_password()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'oldpassword',
        ]);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'رمز عبور با موفقیت تغییر کرد',
            ]);

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newpassword123', $admin->fresh()->password)
        );
    }

    public function test_protected_routes_require_authentication()
    {
        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_admin_routes_require_admin_role()
    {
        $user = User::factory()->create(['role' => 'parent']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'FORBIDDEN',
            ]);
    }

    public function test_admin_can_access_admin_routes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(200);
    }
}
