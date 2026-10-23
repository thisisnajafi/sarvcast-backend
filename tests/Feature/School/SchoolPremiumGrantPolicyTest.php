<?php

namespace Tests\Feature\School;

use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolPremiumGrantPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['notification.push.enabled' => false]);
        $this->travelTo(Carbon::parse('2026-06-01 09:00:00'));
    }

    public function test_only_super_admin_sets_grant_deadline_and_plans_from_user_form(): void
    {
        $super = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        [$manager, $profile] = $this->panel('09141100001');
        $plan = SubscriptionPlan::factory()->create(['duration_days' => 90]);

        $body = [
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name,
            'phone_number' => $manager->phone_number,
            'role' => User::ROLE_SCHOOL_MANAGER,
            'status' => User::STATUS_ACTIVE,
            'premium_grant_expires_at' => now()->addDays(10)->toIso8601String(),
            'allowed_grant_plan_ids' => [$plan->id],
        ];

        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/users/'.$manager->id, $body)->assertForbidden();

        Sanctum::actingAs($super);
        $this->putJson('/api/admin/users/'.$manager->id, $body)
            ->assertOk()
            ->assertJsonPath('data.allowed_grant_plan_ids', [$plan->id]);

        $profile->refresh();
        $this->assertSame([$plan->id], $profile->allowedGrantPlanIds());
        $this->assertTrue($profile->premium_grant_expires_at->equalTo(now()->addDays(10)->startOfSecond()));

        Sanctum::actingAs($manager);
        $this->getJson('/api/admin/v1/school-panel/me')
            ->assertOk()
            ->assertJsonPath('data.premium_grant_policy.ended', false)
            ->assertJsonPath('data.premium_grant_policy.days_remaining', 10)
            ->assertJsonPath('data.premium_grant_policy.allowed_plans.0.id', $plan->id);
    }

    public function test_grant_uses_allowed_plan_and_rejects_others_and_after_deadline(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141100002');
        $allowed = SubscriptionPlan::factory()->create(['duration_days' => 90]);
        $other = SubscriptionPlan::factory()->create(['duration_days' => 30]);
        $memberA = User::factory()->create(['phone_number' => '09141100003']);
        $memberB = User::factory()->create(['phone_number' => '09141100004']);
        $this->attach($manager, $profile, $memberA);
        $this->attach($manager, $profile, $memberB);

        $profile->update([
            'premium_grant_expires_at' => now()->addDays(5),
            'allowed_grant_plan_ids' => [$allowed->id],
        ]);

        Sanctum::actingAs($super);
        $this->putJson('/api/admin/v1/school-managers/'.$profile->id.'/premium-window', [
            'duration_months' => 1,
            'seat_limit' => 10,
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ])->assertCreated();

        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-panel/members/'.$memberA->id.'/premium-grant', ['plan_id' => $other->id])
            ->assertStatus(422)
            ->assertJsonPath('error', 'plan_not_allowed');

        $this->postJson('/api/admin/v1/school-panel/members/'.$memberA->id.'/premium-grant', ['plan_id' => $allowed->id])
            ->assertCreated()
            ->assertJsonPath('data.grant.duration_months', 3);

        $this->travelTo(now()->addDays(6));
        $this->postJson('/api/admin/v1/school-panel/members/'.$memberB->id.'/premium-grant')
            ->assertStatus(422)
            ->assertJsonPath('error', 'grant_period_ended');
    }

    public function test_app_profile_exposes_school_manager_badge_data(): void
    {
        [$manager, $profile] = $this->panel('09141100009');
        $profile->update(['label' => 'دبستان نمونه', 'premium_grant_expires_at' => now()->addDays(3)]);
        $parent = User::factory()->create(['phone_number' => '09141100010', 'role' => User::ROLE_PARENT]);

        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.is_school_manager', true)
            ->assertJsonPath('data.school_manager.role_label', 'مدیر مدرسه')
            ->assertJsonPath('data.school_manager.panel_name', 'دبستان نمونه')
            ->assertJsonPath('data.school_manager.premium_grant_policy.days_remaining', 3);

        Sanctum::actingAs($parent);
        $this->getJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.is_school_manager', false)
            ->assertJsonPath('data.school_manager', null);
    }

    private function panel(string $phone): array
    {
        $manager = User::factory()->schoolManager()->create(['phone_number' => $phone]);
        $manager->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        $profile = SchoolManagerProfile::query()->create([
            'user_id' => $manager->id,
            'member_limit' => 50,
            'status' => SchoolManagerProfile::STATUS_ACTIVE,
        ]);

        return [$manager->fresh(), $profile];
    }

    private function attach(User $manager, SchoolManagerProfile $profile, User $member): void
    {
        SchoolMembership::query()->create([
            'school_manager_profile_id' => $profile->id,
            'user_id' => $member->id,
            'active_user_id' => $member->id,
            'attached_by' => $manager->id,
        ]);
    }
}
