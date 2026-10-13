<?php

namespace Tests\Feature\School;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolManagerProfile;
use App\Models\User;
use App\Services\AdminLoginCaptchaService;
use App\Support\SchoolAccessCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolManagerAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_school_manager_on_the_user_form_keeps_the_app_login_and_opens_the_school_panel(): void
    {
        $super = User::factory()->superAdmin()->create();
        $phone = '09120001101';
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => $phone,
            'first_name' => 'والی',
            'last_name' => 'مددکار',
            'onboarding_completed' => true,
        ]);
        $schoolRoleId = (int) Role::query()->where('name', 'school_manager')->value('id');
        $permissionIds = Permission::query()
            ->whereIn('name', SchoolAccessCatalog::panelPermissionNames())
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        Sanctum::actingAs($super);

        $saved = $this->putJson('/api/admin/users/'.$parent->id, [
            'first_name' => 'والی',
            'last_name' => 'مددکار',
            'phone_number' => $phone,
            'role' => User::ROLE_PARENT,
            'status' => User::STATUS_ACTIVE,
            'role_ids' => [$schoolRoleId],
            'direct_permission_ids' => $permissionIds,
            'member_limit' => 25,
            'label' => 'مدرسه تست',
        ])->assertOk();

        $assigned = collect($saved->json('data.assigned_roles'))->pluck('name');
        $this->assertTrue($assigned->contains('school_manager'));
        $this->assertSame(25, (int) $saved->json('data.school_manager_profile.member_limit'));
        $this->assertSame('مدرسه تست', $saved->json('data.school_manager_profile.label'));

        $parent->refresh();
        $this->assertSame(User::ROLE_SCHOOL_MANAGER, $parent->role);
        $this->assertSame($phone, $parent->phone_number);
        $this->assertSame(User::STATUS_ACTIVE, $parent->status);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $parent->id,
            'role_id' => $schoolRoleId,
        ]);
        $this->assertDatabaseHas('school_manager_profiles', [
            'user_id' => $parent->id,
            'status' => SchoolManagerProfile::STATUS_ACTIVE,
            'member_limit' => 25,
            'label' => 'مدرسه تست',
        ]);

        Cache::put('otp_'.$phone.'_login', '123456', 300);
        $this->postJson('/api/v1/auth/login', [
            'phone_number' => $phone,
            'verification_code' => '123456',
        ])->assertOk()->assertJsonPath('data.user.id', $parent->id);

        $this->postJson('/api/admin/v1/auth/send-otp', array_merge(
            ['phone_number' => $phone],
            $this->adminCaptchaPayload(),
        ))->assertOk()->assertJsonPath('success', true);

        $adminCode = Cache::get('otp_'.$phone.'_admin_login');
        $this->assertNotEmpty($adminCode);

        $login = $this->postJson('/api/admin/v1/auth/login', [
            'phone_number' => $phone,
            'verification_code' => $adminCode,
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'school_manager')
            ->assertJsonPath('data.redirect_to', '/school-panel');

        $this->assertNotEmpty($login->json('data.token'));

        Sanctum::actingAs($parent->fresh());

        $this->getJson('/api/admin/v1/school-panel/me')
            ->assertOk()
            ->assertJsonPath('data.manager.id', $parent->id);

        $this->postJson('/api/admin/v1/auth/2fa/verify', [
            'code' => Cache::get('otp_'.$phone.'_admin_2fa'),
        ])->assertOk();
    }

    public function test_school_manager_created_from_the_managers_screen_can_log_in_to_the_school_panel(): void
    {
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $phone = '09120001102';
        $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => $phone,
            'first_name' => 'سارا',
            'last_name' => 'مدرسه',
            'member_limit' => 10,
        ])->assertCreated();

        $this->postJson('/api/admin/v1/auth/send-otp', array_merge(
            ['phone_number' => $phone],
            $this->adminCaptchaPayload(),
        ))->assertOk();

        $this->postJson('/api/admin/v1/auth/login', [
            'phone_number' => $phone,
            'verification_code' => Cache::get('otp_'.$phone.'_admin_login'),
        ])->assertOk()->assertJsonPath('data.redirect_to', '/school-panel');

        $manager = User::query()->where('phone_number', $phone)->firstOrFail();
        Sanctum::actingAs($manager);

        $this->getJson('/api/admin/v1/school-panel/me')->assertOk();
    }

    public function test_saved_panel_permissions_without_a_role_are_repaired_on_admin_login(): void
    {
        $phone = '09120001103';
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'phone_number' => $phone,
            'status' => User::STATUS_ACTIVE,
            'onboarding_completed' => true,
        ]);
        $parent->directPermissions()->sync(
            Permission::query()->whereIn('name', SchoolAccessCatalog::panelPermissionNames())->pluck('id')->all()
        );

        $this->assertSame(User::ROLE_PARENT, $parent->fresh()->role);
        $this->assertFalse($parent->roles()->where('name', 'school_manager')->exists());

        $this->postJson('/api/admin/v1/auth/send-otp', array_merge(
            ['phone_number' => $phone],
            $this->adminCaptchaPayload(),
        ))->assertOk();

        $parent->refresh();
        $this->assertSame(User::ROLE_SCHOOL_MANAGER, $parent->role);
        $this->assertTrue($parent->roles()->where('name', 'school_manager')->exists());

        $this->postJson('/api/admin/v1/auth/login', [
            'phone_number' => $phone,
            'verification_code' => Cache::get('otp_'.$phone.'_admin_login'),
        ])->assertOk()->assertJsonPath('data.redirect_to', '/school-panel');
    }

    public function test_admin_otp_rejects_a_missing_captcha_without_sending_sms(): void
    {
        $admin = User::factory()->superAdmin()->create(['phone_number' => '09120001104']);

        $this->postJson('/api/admin/v1/auth/send-otp', [
            'phone_number' => $admin->phone_number,
        ])->assertStatus(422);

        $this->assertNull(Cache::get('otp_'.$admin->phone_number.'_admin_login'));
    }

    public function test_admin_otp_rejects_a_wrong_captcha_and_does_not_reuse_it(): void
    {
        $admin = User::factory()->superAdmin()->create(['phone_number' => '09120001105']);
        $captcha = $this->adminCaptchaPayload();

        $this->postJson('/api/admin/v1/auth/send-otp', [
            'phone_number' => $admin->phone_number,
            'captcha_token' => $captcha['captcha_token'],
            'captcha_answer' => '0',
        ])->assertStatus(422)->assertJsonPath('message', 'کد امنیتی نادرست یا منقضی شده است.');

        $this->assertNull(Cache::get('otp_'.$admin->phone_number.'_admin_login'));

        $this->postJson('/api/admin/v1/auth/send-otp', [
            'phone_number' => $admin->phone_number,
            'captcha_token' => $captcha['captcha_token'],
            'captcha_answer' => $captcha['captcha_answer'],
        ])->assertStatus(422);

        $this->assertNull(Cache::get('otp_'.$admin->phone_number.'_admin_login'));
    }

    public function test_admin_otp_rejects_a_reused_captcha(): void
    {
        $admin = User::factory()->superAdmin()->create(['phone_number' => '09120001106']);
        $captcha = $this->adminCaptchaPayload();

        $this->postJson('/api/admin/v1/auth/send-otp', array_merge(
            ['phone_number' => $admin->phone_number],
            $captcha,
        ))->assertOk();

        $this->postJson('/api/admin/v1/auth/send-otp', array_merge(
            ['phone_number' => $admin->phone_number],
            $captcha,
        ))->assertStatus(422)->assertJsonPath('message', 'کد امنیتی نادرست یا منقضی شده است.');
    }

    public function test_admin_otp_rejects_an_expired_captcha(): void
    {
        $admin = User::factory()->superAdmin()->create(['phone_number' => '09120001107']);
        $captcha = app(AdminLoginCaptchaService::class);
        $challenge = $captcha->createChallenge();
        Cache::put($captcha->cacheKey($challenge['token']), [
            'answer_hash' => hash('sha256', $challenge['answer']),
            'expires_at' => now()->subMinute()->getTimestamp(),
        ], 180);

        $this->postJson('/api/admin/v1/auth/send-otp', [
            'phone_number' => $admin->phone_number,
            'captcha_token' => $challenge['token'],
            'captcha_answer' => $challenge['answer'],
        ])->assertStatus(422);

        $this->assertNull(Cache::get('otp_'.$admin->phone_number.'_admin_login'));
    }

    public function test_admin_otp_accepts_a_valid_captcha_and_hides_the_answer(): void
    {
        $admin = User::factory()->superAdmin()->create(['phone_number' => '09120001108']);

        $issued = $this->postJson('/api/admin/v1/auth/captcha', [
            'phone_number' => $admin->phone_number,
        ])->assertOk();

        $this->assertNotSame('', (string) $issued->json('data.prompt'));
        $body = $issued->json('data');
        $this->assertArrayNotHasKey('answer', $body);
        $this->assertArrayNotHasKey('captcha_answer', $body);
        $this->assertTrue(
            (is_string($body['image'] ?? null) && str_starts_with($body['image'], 'data:image/png;base64,'))
            || is_string($body['question'] ?? null)
        );

        $captcha = $this->adminCaptchaPayload();
        $this->postJson('/api/admin/v1/auth/send-otp', array_merge(
            ['phone_number' => $admin->phone_number],
            $captcha,
        ))->assertOk()->assertJsonPath('message', 'کد تایید به شماره شما ارسال شد');

        $this->assertNotEmpty(Cache::get('otp_'.$admin->phone_number.'_admin_login'));
    }

    public function test_captcha_issue_is_rate_limited_per_phone(): void
    {
        $phone = '09120001109';

        for ($i = 0; $i < AdminLoginCaptchaService::PHONE_LIMIT; $i++) {
            $this->postJson('/api/admin/v1/auth/captcha', [
                'phone_number' => $phone,
            ])->assertOk();
        }

        $this->postJson('/api/admin/v1/auth/captcha', [
            'phone_number' => $phone,
        ])->assertStatus(429);
    }
}
