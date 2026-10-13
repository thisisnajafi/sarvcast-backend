<?php

namespace Tests\Feature\School;

use App\Models\SchoolLicense;
use App\Models\SchoolPartnership;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\SchoolLicenseEntitlementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolLicensePremiumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_school_license_grants_premium_access(): void
    {
        [$school, $user, $license] = $this->makeLicensedMember(memberLimit: 5);

        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($user->id));
        $resolved = app(SchoolLicenseEntitlementService::class)->resolveForUser($user->id);
        $this->assertTrue($resolved['has_premium']);
        $this->assertSame('school_license', $resolved['source']);
        $this->assertSame($license->id, $resolved['license_id']);
    }

    public function test_expired_school_license_does_not_grant_premium(): void
    {
        [$school, $user] = $this->makeLicensedMember(memberLimit: 5, endDate: now()->subDay()->toDateString());

        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($user->id));
    }

    public function test_over_limit_members_lose_premium_for_extra_seats(): void
    {
        $school = $this->makeSchool(memberLimit: 1);
        $first = User::factory()->create(['role' => 'basic']);
        $second = User::factory()->create(['role' => 'basic']);

        $this->attachLicense($school, $first);
        $this->attachLicense($school, $second);

        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($first->id));
        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($second->id));

        $summary = app(SchoolLicenseEntitlementService::class)->summarizePartnership($school->fresh());
        $this->assertTrue($summary['over_limit']);
        $this->assertSame(1, $summary['premium_seat_count']);
    }

    public function test_removed_or_cancelled_license_ends_premium(): void
    {
        [$school, $user, $license] = $this->makeLicensedMember(memberLimit: 5);

        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($user->id));

        $license->update(['status' => 'cancelled']);

        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($user->id));
    }

    public function test_admin_school_and_user_show_include_license_premium_status(): void
    {
        [$school, $user, $license] = $this->makeLicensedMember(memberLimit: 10);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/schools/{$school->id}")
            ->assertOk()
            ->assertJsonPath('data.license_premium.premium_seat_count', 1)
            ->assertJsonPath('data.license_premium.member_limit', 10)
            ->assertJsonPath('data.license_premium.licenses.0.id', $license->id)
            ->assertJsonPath('data.license_premium.licenses.0.grants_premium', true);

        $this->getJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.license_premium.has_premium', true)
            ->assertJsonPath('data.license_premium.source', 'school_license')
            ->assertJsonPath('data.license_premium.license_id', $license->id);
    }

    /**
     * @return array{0: SchoolPartnership, 1: User, 2: SchoolLicense}
     */
    private function makeLicensedMember(int $memberLimit, ?string $endDate = null): array
    {
        $school = $this->makeSchool($memberLimit);
        $user = User::factory()->create(['role' => 'basic']);
        $license = $this->attachLicense($school, $user, $endDate);

        return [$school, $user, $license];
    }

    private function makeSchool(int $memberLimit): SchoolPartnership
    {
        return SchoolPartnership::create([
            'school_name' => 'مدرسه لایسنس',
            'school_type' => 'public',
            'school_level' => 'elementary',
            'location' => 'تهران',
            'contact_person' => 'مدیر',
            'contact_email' => 'school@test.com',
            'contact_phone' => '09120000001',
            'student_count' => 0,
            'teacher_count' => 0,
            'partnership_model' => 'licensing',
            'max_student_capacity' => $memberLimit,
            'partnership_start_date' => now()->subMonth()->toDateString(),
            'partnership_end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    private function attachLicense(SchoolPartnership $school, User $user, ?string $endDate = null): SchoolLicense
    {
        return SchoolLicense::create([
            'school_partnership_id' => $school->id,
            'user_id' => $user->id,
            'license_type' => 'student',
            'user_role' => 'student',
            'original_price' => 100000,
            'discounted_price' => 0,
            'discount_amount' => 100000,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => $endDate ?? now()->addMonths(6)->toDateString(),
            'status' => 'active',
            'is_activated' => true,
            'activated_at' => now(),
        ]);
    }
}
