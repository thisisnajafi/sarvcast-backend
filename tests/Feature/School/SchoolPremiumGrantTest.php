<?php

namespace Tests\Feature\School;

use App\Models\Notification;
use App\Models\Role;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\SchoolPremiumGrant;
use App\Models\SchoolPremiumWindow;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\NotificationService;
use App\Services\UserDeletionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolPremiumGrantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['notification.push.enabled' => false]);
        $this->travelTo(Carbon::parse('2026-06-01 09:00:00'));
    }

    public function test_super_admin_opens_a_window_and_the_manager_reads_the_summary(): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => 'school_panel.premium.grant',
            'group' => 'school_panel',
        ]);
        $roleId = Role::query()->where('name', 'school_manager')->value('id');
        $this->assertDatabaseHas('role_permission', [
            'role_id' => $roleId,
            'permission_id' => \App\Models\Permission::query()->where('name', 'school_panel.premium.grant')->value('id'),
        ]);

        $super = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        [$manager, $profile] = $this->panel('09141000001', 50);

        Sanctum::actingAs($admin);
        $this->putJson($this->windowUrl($profile), $this->windowBody())->assertForbidden();
        $this->getJson('/api/admin/v1/school-managers/premium-overview')->assertForbidden();

        Sanctum::actingAs($manager);
        $this->putJson($this->windowUrl($profile), $this->windowBody())->assertForbidden();

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(2, 20))
            ->assertCreated()
            ->assertJsonPath('data.duration_months', 2)
            ->assertJsonPath('data.seat_limit', 20)
            ->assertJsonPath('data.seats_used', 0)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.accepting_grants', true);

        $this->putJson($this->windowUrl($profile), [
            'duration_months' => 0,
            'seat_limit' => 5,
            'expires_at' => now()->addDays(14)->toIso8601String(),
        ])->assertStatus(422);

        $this->putJson($this->windowUrl($profile), $this->windowBody(13, 5))->assertStatus(422);

        Sanctum::actingAs($manager);
        $this->getJson('/api/admin/v1/school-panel/me')
            ->assertOk()
            ->assertJsonPath('data.premium_window.duration_months', 2)
            ->assertJsonPath('data.premium_window.seat_limit', 20)
            ->assertJsonPath('data.premium_window.seats_remaining', 20)
            ->assertJsonPath('data.premium_window.accepting_grants', true);
    }

    public function test_grant_writes_subscription_and_notification_when_push_throws(): void
    {
        $this->app->instance(NotificationService::class, new class extends NotificationService
        {
            public function __construct() {}

            public function sendPushNotification(User $user, string $title, string $body, array $data = []): bool
            {
                throw new \RuntimeException('fcm down');
            }
        });

        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000002', 10);
        $member = User::factory()->create(['phone_number' => '09141000003', 'first_name' => 'نیما']);
        $this->attach($manager, $profile, $member);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(2, 5))->assertCreated();

        Sanctum::actingAs($manager);
        $response = $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')
            ->assertCreated()
            ->assertJsonPath('data.subscription.type', 'school_2m')
            ->assertJsonPath('data.subscription.payment_method', 'school_grant')
            ->assertJsonPath('data.grant.duration_months', 2)
            ->assertJsonPath('data.grant.user_id', $member->id)
            ->assertJsonPath('data.premium_window.seats_used', 1);

        $grantId = $response->json('data.grant.id');
        $subscription = Subscription::query()->findOrFail($response->json('data.subscription.id'));
        $this->assertSame('school_manager', $subscription->billing_platform);
        $this->assertFalse((bool) $subscription->auto_renew);
        $this->assertSame(0.0, (float) $subscription->price);
        $this->assertSame('school_grant', $subscription->subscription_metadata['source']);
        $this->assertSame($grantId, $subscription->subscription_metadata['grant_id']);
        $this->assertSame(
            now()->addMonths(2)->format('Y-m-d H:i'),
            $subscription->end_date->format('Y-m-d H:i'),
        );
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($member->id));

        $notification = Notification::query()->findOrFail($response->json('data.grant.notification_id'));
        $this->assertSame($member->id, $notification->user_id);
        $this->assertSame('subscription', $notification->type);
        $this->assertSame('subscription', $notification->category);
        $this->assertSame('high', $notification->priority);
        $this->assertSame('اشتراک رایگان برای شما فعال شد', $notification->title);
        $this->assertSame($grantId, $notification->data['grant_id']);
        $this->assertStringContainsString('2', $notification->message);

        $this->assertDatabaseHas('school_premium_grants', [
            'id' => $grantId,
            'notification_id' => $notification->id,
            'status' => 'active',
        ]);
    }

    public function test_seat_limit_blocks_the_next_grant_and_one_month_uses_the_legacy_slug(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000010', 25);
        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(1, 20))->assertCreated();

        Sanctum::actingAs($manager);
        $members = [];
        for ($i = 0; $i < 21; $i++) {
            $members[] = User::factory()->create();
            $this->attach($manager, $profile, $members[$i]);
        }

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/admin/v1/school-panel/members/'.$members[$i]->id.'/premium-grant')->assertCreated();
        }

        $this->assertSame('1month', Subscription::query()->where('user_id', $members[0]->id)->value('type'));
        $this->postJson('/api/admin/v1/school-panel/members/'.$members[20]->id.'/premium-grant')
            ->assertStatus(422)
            ->assertJsonPath('error', 'seats_full');
        $this->assertSame(20, SchoolPremiumGrant::query()->where('school_manager_profile_id', $profile->id)->count());
    }

    public function test_expired_window_blocks_grants_before_and_after_the_command(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000040', 5);
        $member = User::factory()->create();
        $other = User::factory()->create();
        $this->attach($manager, $profile, $member);
        $this->attach($manager, $profile, $other);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(1, 5, now()->addDays(14)))->assertCreated();

        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')->assertCreated();

        $this->travelTo(now()->addDays(14)->addMinute());

        $this->postJson('/api/admin/v1/school-panel/members/'.$other->id.'/premium-grant')
            ->assertStatus(422)
            ->assertJsonPath('error', 'window_closed');
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($member->id));

        $this->artisan('school:close-premium-windows')
            ->expectsOutput('Windows closed: 1. Grants expired: 0.')
            ->assertSuccessful();

        $this->assertDatabaseHas('school_premium_windows', [
            'school_manager_profile_id' => $profile->id,
            'status' => 'expired',
            'active_profile_id' => null,
        ]);
        $this->assertSame('active', SchoolPremiumGrant::query()->where('user_id', $member->id)->value('status'));

        $this->artisan('school:close-premium-windows')
            ->expectsOutput('Windows closed: 0. Grants expired: 0.')
            ->assertSuccessful();
    }

    public function test_regrant_opens_a_fresh_seat_count_and_keeps_old_grants(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000050', 5);
        $member = User::factory()->create();
        $this->attach($manager, $profile, $member);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(1, 1))->assertCreated();
        $firstWindow = SchoolPremiumWindow::query()->where('active_profile_id', $profile->id)->firstOrFail();

        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')->assertCreated();
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')
            ->assertStatus(409)
            ->assertJsonPath('error', 'already_granted');

        Sanctum::actingAs($super);
        $this->postJson($this->windowUrl($profile).'/regrant', $this->windowBody(3, 4))
            ->assertCreated()
            ->assertJsonPath('data.duration_months', 3)
            ->assertJsonPath('data.seat_limit', 4)
            ->assertJsonPath('data.seats_used', 0)
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('school_premium_windows', [
            'id' => $firstWindow->id,
            'status' => 'revoked',
            'active_profile_id' => null,
        ]);
        $this->assertSame(1, SchoolPremiumGrant::query()->where('school_premium_window_id', $firstWindow->id)->count());

        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')
            ->assertCreated()
            ->assertJsonPath('data.grant.duration_months', 3);
        $this->assertSame(2, SchoolPremiumGrant::query()->where('user_id', $member->id)->count());
    }

    public function test_seat_limit_cannot_drop_below_seats_already_used(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000060', 5);
        $member = User::factory()->create();
        $this->attach($manager, $profile, $member);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(1, 2))->assertCreated();
        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')->assertCreated();

        Sanctum::actingAs($super);
        $this->patchJson($this->windowUrl($profile), ['seat_limit' => 0])
            ->assertStatus(422)
            ->assertJsonPath('error', 'seats_below_used');

        $this->patchJson($this->windowUrl($profile), [
            'seat_limit' => 1,
            'duration_months' => 6,
        ])->assertOk()
            ->assertJsonPath('data.seat_limit', 1)
            ->assertJsonPath('data.duration_months', 6)
            ->assertJsonPath('data.seats_used', 1);

        $this->assertSame(1, SchoolPremiumGrant::query()->where('user_id', $member->id)->value('duration_months'));
    }

    public function test_existing_premium_is_extended_and_revoke_restores_the_previous_end(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000070', 5);
        $member = User::factory()->create();
        $this->attach($manager, $profile, $member);
        $previousEnd = now()->addDays(10);
        $subscription = Subscription::query()->create([
            'user_id' => $member->id,
            'type' => '1month',
            'status' => 'active',
            'start_date' => now()->subDays(20),
            'end_date' => $previousEnd,
            'price' => 50000,
            'currency' => 'IRR',
            'auto_renew' => true,
            'payment_method' => 'zarinpal',
            'billing_platform' => 'website',
        ]);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(2, 2))->assertCreated();
        Sanctum::actingAs($manager);
        $granted = $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')
            ->assertCreated()
            ->assertJsonPath('data.subscription.id', $subscription->id);

        $subscription->refresh();
        $this->assertSame('1month', $subscription->type);
        $this->assertSame('zarinpal', $subscription->payment_method);
        $this->assertSame(
            $previousEnd->copy()->addMonths(2)->format('Y-m-d H:i'),
            $subscription->end_date->format('Y-m-d H:i'),
        );
        $this->assertSame(
            $previousEnd->format('Y-m-d H:i'),
            Carbon::parse($granted->json('data.grant.previous_end_date'))->format('Y-m-d H:i'),
        );

        Sanctum::actingAs($super);
        $this->postJson('/api/admin/v1/school-managers/grants/'.$granted->json('data.grant.id').'/revoke')
            ->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $subscription->refresh();
        $this->assertSame($previousEnd->format('Y-m-d H:i'), $subscription->end_date->format('Y-m-d H:i'));
        $this->assertSame('active', $subscription->status);
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($member->id));

        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-managers/grants/'.$granted->json('data.grant.id').'/revoke')
            ->assertForbidden();
    }

    public function test_removing_member_revokes_school_grant_but_keeps_purchased_premium(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000075', 5);
        $member = User::factory()->create();
        $this->attach($manager, $profile, $member);

        $previousEnd = now()->addDays(20);
        $subscription = Subscription::query()->create([
            'user_id' => $member->id,
            'type' => '1month',
            'status' => 'active',
            'start_date' => now()->subDays(5),
            'end_date' => $previousEnd,
            'price' => 75000,
            'currency' => 'IRR',
            'auto_renew' => true,
            'payment_method' => 'zarinpal',
            'billing_platform' => 'website',
        ]);

        $profile->update(['premium_grant_expires_at' => now()->addDays(90)]);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(1, 2))->assertCreated();

        Sanctum::actingAs($manager);
        $granted = $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')
            ->assertCreated()
            ->assertJsonPath('data.subscription.id', $subscription->id);
        $grantId = (int) $granted->json('data.grant.id');
        $this->assertDatabaseHas('school_premium_grants', [
            'id' => $grantId,
            'status' => 'active',
            'grant_source' => 'school_panel',
            'source_profile_id' => $profile->id,
        ]);

        $this->deleteJson('/api/admin/v1/school-panel/members/'.$member->id)->assertOk();

        $subscription->refresh();
        $this->assertSame($previousEnd->format('Y-m-d H:i'), $subscription->end_date->format('Y-m-d H:i'));
        $this->assertSame('active', $subscription->status);
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($member->id));

        $this->assertDatabaseHas('school_premium_grants', [
            'id' => $grantId,
            'status' => 'revoked',
            'revoked_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('school_memberships', [
            'school_manager_profile_id' => $profile->id,
            'user_id' => $member->id,
            'active_user_id' => null,
        ]);
    }

    public function test_revoke_ends_a_free_grant_immediately(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000080', 5);
        $member = User::factory()->create();
        $this->attach($manager, $profile, $member);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(4, 2))->assertCreated();
        Sanctum::actingAs($manager);
        $granted = $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')->assertCreated();
        $this->assertSame('school_4m', $granted->json('data.subscription.type'));
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($member->id));

        Sanctum::actingAs($super);
        $this->postJson('/api/admin/v1/school-managers/grants/'.$granted->json('data.grant.id').'/revoke')->assertOk();

        $subscription = Subscription::query()->findOrFail($granted->json('data.subscription.id'));
        $this->assertFalse($subscription->end_date->isFuture());
        $this->assertSame('expired', $subscription->status);
        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($member->id));
        $this->assertSame('revoked', SchoolPremiumGrant::query()->find($granted->json('data.grant.id'))->status);
    }

    public function test_natural_premium_end_marks_the_grant_expired(): void
    {
        [$manager, $profile] = $this->panel('09141000090', 5);
        $member = User::factory()->create();
        $this->attach($manager, $profile, $member);

        Sanctum::actingAs(User::factory()->superAdmin()->create());
        $this->putJson($this->windowUrl($profile), $this->windowBody(1, 2, now()->addYear()))->assertCreated();
        Sanctum::actingAs($manager);
        $granted = $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')->assertCreated();

        $this->travelTo(Carbon::parse($granted->json('data.subscription.end_date'))->addMinute());
        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($member->id));

        $this->artisan('school:close-premium-windows')
            ->expectsOutput('Windows closed: 0. Grants expired: 1.')
            ->assertSuccessful();

        $this->assertSame('expired', SchoolPremiumGrant::query()->find($granted->json('data.grant.id'))->status);
        $this->assertSame('open', SchoolPremiumWindow::query()->where('school_manager_profile_id', $profile->id)->value('status'));
    }

    public function test_a_manager_cannot_grant_or_see_another_managers_users(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$managerA, $profileA] = $this->panel('09141000100', 5, 'مدرسه-الف-پنهان');
        [$managerB, $profileB] = $this->panel('09141000101', 5, 'مدرسه-ب-پنهان');
        $memberA = User::factory()->create(['first_name' => 'فقطالف']);
        $memberB = User::factory()->create(['first_name' => 'فقطب']);
        $stranger = User::factory()->create();
        $this->attach($managerA, $profileA, $memberA);
        $this->attach($managerB, $profileB, $memberB);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profileA), $this->windowBody(1, 3))->assertCreated();
        $this->putJson($this->windowUrl($profileB), $this->windowBody(1, 3))->assertCreated();

        Sanctum::actingAs($managerA);
        $hidden = $this->postJson('/api/admin/v1/school-panel/members/'.$memberB->id.'/premium-grant')->assertNotFound();
        $this->assertStringNotContainsString('مدرسه-ب-پنهان', $hidden->getContent());
        $this->postJson('/api/admin/v1/school-panel/members/'.$stranger->id.'/premium-grant')->assertNotFound();
        $this->postJson('/api/admin/v1/school-panel/members/'.$memberA->id.'/premium-grant')->assertCreated();

        $this->assertSame(0, SchoolPremiumGrant::query()->where('user_id', $memberB->id)->count());
        $this->assertSame(1, SchoolPremiumGrant::query()->where('school_manager_profile_id', $profileA->id)->count());

        Sanctum::actingAs($super);
        $history = $this->getJson('/api/admin/v1/school-managers/'.$profileA->id.'/grants')->assertOk();
        $ids = collect($history->json('data'))->pluck('user_id');
        $this->assertTrue($ids->contains($memberA->id));
        $this->assertFalse($ids->contains($memberB->id));
    }

    public function test_overview_lists_each_managers_window_numbers(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$managerA, $profileA] = $this->panel('09141000110', 40, 'نمای الف');
        [$managerB, $profileB] = $this->panel('09141000111', 8, 'نمای ب');
        $member = User::factory()->create();
        $this->attach($managerA, $profileA, $member);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profileA), $this->windowBody(5, 10, now()->addDays(14)))->assertCreated();
        Sanctum::actingAs($managerA);
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')->assertCreated();

        Sanctum::actingAs($super);
        $overview = $this->getJson('/api/admin/v1/school-managers/premium-overview?per_page=50')->assertOk();
        $rows = collect($overview->json('data'))->keyBy('id');

        $this->assertSame(40, $rows[$profileA->id]['member_limit']);
        $this->assertSame(1, $rows[$profileA->id]['active_member_count']);
        $this->assertSame(1, $rows[$profileA->id]['active_grant_count']);
        $this->assertSame('open', $rows[$profileA->id]['premium_window']['status']);
        $this->assertSame(1, $rows[$profileA->id]['premium_window']['seats_used']);
        $this->assertSame(10, $rows[$profileA->id]['premium_window']['seat_limit']);
        $this->assertSame(5, $rows[$profileA->id]['premium_window']['duration_months']);
        $this->assertSame(8, $rows[$profileB->id]['member_limit']);
        $this->assertNull($rows[$profileB->id]['premium_window']);
        $this->assertSame(0, $rows[$profileB->id]['active_grant_count']);

        Sanctum::actingAs($managerB);
        $this->getJson('/api/admin/v1/school-managers/premium-overview')->assertForbidden();
        $mine = $this->getJson('/api/admin/v1/school-panel/me')->assertOk();
        $this->assertStringNotContainsString('نمای الف', $mine->getContent());
    }

    public function test_closing_or_deleting_the_manager_keeps_grant_history_and_the_members_premium(): void
    {
        $super = User::factory()->superAdmin()->create();
        [$manager, $profile] = $this->panel('09141000120', 5);
        $member = User::factory()->create();
        $this->attach($manager, $profile, $member);

        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profile), $this->windowBody(2, 2))->assertCreated();
        Sanctum::actingAs($manager);
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/premium-grant')->assertCreated();

        Sanctum::actingAs($super);
        $this->deleteJson('/api/admin/v1/school-managers/'.$profile->id)->assertOk();

        $this->assertDatabaseHas('school_premium_windows', [
            'school_manager_profile_id' => $profile->id,
            'status' => 'revoked',
            'active_profile_id' => null,
        ]);
        $this->assertNotNull(SchoolPremiumGrant::query()->where('user_id', $member->id)->first());
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($member->id));

        [$managerAgain, $profileAgain] = $this->panel('09141000121', 5);
        $kept = User::factory()->create();
        $this->attach($managerAgain, $profileAgain, $kept);
        Sanctum::actingAs($super);
        $this->putJson($this->windowUrl($profileAgain), $this->windowBody(1, 1))->assertCreated();
        Sanctum::actingAs($managerAgain);
        $this->postJson('/api/admin/v1/school-panel/members/'.$kept->id.'/premium-grant')->assertCreated();
        $grantId = SchoolPremiumGrant::query()->where('user_id', $kept->id)->value('id');

        app(UserDeletionService::class)->deleteUser($managerAgain->fresh());

        $this->assertDatabaseMissing('users', ['id' => $managerAgain->id]);
        $this->assertNotNull(User::query()->find($kept->id));
        $grant = SchoolPremiumGrant::query()->findOrFail($grantId);
        $this->assertNull($grant->school_manager_profile_id);
        $this->assertNotNull($grant->school_premium_window_id);
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($kept->id));
    }

    /**
     * @return array{0: User, 1: SchoolManagerProfile}
     */
    private function panel(string $phone, int $limit, ?string $label = null): array
    {
        $manager = User::factory()->schoolManager()->create(['phone_number' => $phone]);
        $manager->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        $profile = SchoolManagerProfile::query()->create([
            'user_id' => $manager->id,
            'member_limit' => $limit,
            'label' => $label,
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

    private function windowUrl(SchoolManagerProfile $profile): string
    {
        return '/api/admin/v1/school-managers/'.$profile->id.'/premium-window';
    }

    /**
     * @return array{duration_months: int, seat_limit: int, expires_at: string}
     */
    private function windowBody(int $months = 2, int $seats = 20, ?Carbon $expires = null): array
    {
        return [
            'duration_months' => $months,
            'seat_limit' => $seats,
            'expires_at' => ($expires ?? now()->addDays(14))->toIso8601String(),
        ];
    }
}
