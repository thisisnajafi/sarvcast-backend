<?php

namespace Tests\Feature\School;

use App\Models\Episode;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PlayHistory;
use App\Models\SchoolMembership;
use App\Models\SchoolPremiumGrant;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\SchoolDashboardService;
use Carbon\Carbon;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolManagerWalkthroughTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $key = str_repeat('b', 32);
        config([
            'services.zarinpal.sandbox' => true,
            'app.admin_dashboard_url' => 'http://localhost:3000',
            'app.key' => 'base64:'.base64_encode($key),
        ]);
        $this->app->singleton('encrypter', fn () => new Encrypter($key, (string) config('app.cipher')));
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_school_manager_spec_from_create_through_isolated_kpis(): void
    {
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $created = $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '09126000001',
            'first_name' => 'لیلا',
            'last_name' => 'مدیری',
            'member_limit' => 2,
            'label' => 'دبستان سرو',
        ])->assertCreated()
            ->assertJsonPath('data.member_limit', 2)
            ->assertJsonPath('data.label', 'دبستان سرو')
            ->assertJsonPath('data.manager.first_name', 'لیلا');

        $manager = User::query()->findOrFail($created->json('data.manager.id'));
        $profileId = (int) $created->json('data.id');
        $this->assertSame(User::ROLE_SCHOOL_MANAGER, $manager->role);

        $otherCreated = $this->postJson('/api/admin/v1/school-managers', [
            'phone_number' => '09126000002',
            'first_name' => 'رضا',
            'last_name' => 'دیگر',
            'member_limit' => 5,
            'label' => 'مدرسه-دیگر-پنهان',
        ])->assertCreated();
        $otherManager = User::query()->findOrFail($otherCreated->json('data.manager.id'));
        $otherProfileId = (int) $otherCreated->json('data.id');

        $userA = User::factory()->create([
            'phone_number' => '09126000011',
            'first_name' => 'آرمین',
            'last_name' => 'الف',
            'last_activity_at' => now(),
        ]);
        $userB = User::factory()->create([
            'phone_number' => '09126000012',
            'first_name' => 'بهار',
            'last_name' => 'ب',
        ]);
        $userC = User::factory()->create([
            'phone_number' => '09126000013',
            'first_name' => 'سوم',
            'last_name' => 'اضافه',
        ]);
        $outsider = User::factory()->create([
            'phone_number' => '09126000014',
            'first_name' => 'غریبه',
            'last_activity_at' => now(),
        ]);
        $otherMember = User::factory()->create([
            'phone_number' => '09126000015',
            'first_name' => 'عضو دیگر',
            'last_activity_at' => now(),
        ]);

        Sanctum::actingAs($manager);

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => '09126000011',
        ])->assertOk()
            ->assertJsonPath('data.id', $userA->id)
            ->assertJsonPath('data.first_name', 'آرمین')
            ->assertJsonPath('data.last_name', 'الف')
            ->assertJsonPath('data.phone_number', '09126000011')
            ->assertJsonPath('data.can_add', true);
        $this->assertSame(0, SchoolMembership::query()->where('school_manager_profile_id', $profileId)->count());

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $userA->id,
        ])->assertCreated()->assertJsonPath('data.user_id', $userA->id);

        $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => '09126000012',
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'بهار')
            ->assertJsonPath('data.can_add', true);

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $userB->id,
        ])->assertCreated();

        $lookupC = $this->postJson('/api/admin/v1/school-panel/members/lookup', [
            'phone_number' => '09126000013',
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'سوم')
            ->assertJsonPath('data.can_add', false)
            ->assertJsonPath('data.block_reason', 'limit_reached');
        $this->assertStringNotContainsString('مدرسه-دیگر-پنهان', $lookupC->getContent());

        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $userC->id,
        ])->assertStatus(422)->assertJsonPath('error', 'limit_reached');

        $roster = collect($this->getJson('/api/admin/v1/school-panel/members')->json('data'))->pluck('user_id');
        $this->assertTrue($roster->contains($userA->id));
        $this->assertTrue($roster->contains($userB->id));
        $this->assertFalse($roster->contains($userC->id));
        $this->assertFalse($roster->contains($outsider->id));
        $this->assertFalse($roster->contains($otherMember->id));

        Sanctum::actingAs($otherManager);
        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $otherMember->id,
        ])->assertCreated();
        $this->postJson('/api/admin/v1/school-panel/members', [
            'user_id' => $userA->id,
        ])->assertStatus(409)->assertJsonPath('error', 'managed_elsewhere');

        $storyA = $this->makeStory('داستان پیاده‌روی الف');
        $storyB = $this->makeStory('داستان پیاده‌روی ب');
        $storyOut = $this->makeStory('داستان غریبه پیاده‌روی');
        $storyOther = $this->makeStory('داستان مدیر دیگر');
        $episodeA1 = $this->makeEpisode($storyA, 1, 'قسمت اول الف');
        $episodeA2 = $this->makeEpisode($storyA, 2, 'قسمت دوم الف');
        $episodeB = $this->makeEpisode($storyB, 1, 'قسمت بهار');
        $episodeOut = $this->makeEpisode($storyOut, 1, 'قسمت غریبه');
        $episodeOther = $this->makeEpisode($storyOther, 1, 'قسمت مدیر دیگر');

        $this->play($userA, $episodeA1, $storyA, true);
        $this->play($userA, $episodeA2, $storyA, false);
        $this->play($userB, $episodeB, $storyB, true);
        $this->play($outsider, $episodeOut, $storyOut, true);
        $this->play($otherMember, $episodeOther, $storyOther, true);
        $this->play($otherMember, $episodeOther, $storyOther, true);

        Subscription::query()->create([
            'user_id' => $userA->id,
            'type' => '3months',
            'status' => 'expired',
            'start_date' => now()->subMonths(4),
            'end_date' => now()->subMonth(),
            'price' => 1500,
            'currency' => 'IRR',
        ]);
        Subscription::query()->create([
            'user_id' => $outsider->id,
            'type' => '1year',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'price' => 424242,
            'currency' => 'IRR',
        ]);
        Payment::query()->create([
            'user_id' => $outsider->id,
            'amount' => 99999,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'status' => 'completed',
            'transaction_id' => 'OUTSIDER-99999',
        ]);
        Payment::query()->create([
            'user_id' => $otherMember->id,
            'amount' => 88888,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'status' => 'completed',
            'transaction_id' => 'OTHER-88888',
        ]);

        Sanctum::actingAs($manager);

        $activity = $this->getJson('/api/admin/v1/school-panel/members/'.$userA->id.'/activity')
            ->assertOk()
            ->assertJsonPath('data.user.id', $userA->id)
            ->assertJsonPath('data.user.first_name', 'آرمین')
            ->assertJsonPath('data.totals.play_count', 2)
            ->assertJsonPath('data.totals.distinct_stories', 1)
            ->assertJsonPath('data.totals.completed_count', 1);
        $titles = collect($activity->json('data.plays'))->pluck('story_title');
        $this->assertTrue($titles->contains('داستان پیاده‌روی الف'));
        $this->assertFalse($titles->contains('داستان پیاده‌روی ب'));
        $this->assertFalse($titles->contains('داستان غریبه پیاده‌روی'));
        $this->assertFalse($titles->contains('داستان مدیر دیگر'));

        $plans = $this->getJson('/api/admin/v1/school-panel/members/'.$userA->id.'/subscriptions')
            ->assertOk()
            ->assertJsonPath('data.subscriptions.0.type', '3months')
            ->assertJsonPath('data.subscriptions.0.grants_access', false);
        $this->assertStringNotContainsString('424242', $plans->getContent());
        $this->assertStringNotContainsString('99999', $plans->getContent());

        $overview = $this->getJson('/api/admin/v1/school-panel/activity')->assertOk();
        $this->assertSame(3, $overview->json('data.totals.play_count'));
        $this->assertCount(2, $overview->json('data.members'));
        $this->assertStringNotContainsString('داستان غریبه پیاده‌روی', $overview->getContent());
        $this->assertStringNotContainsString('داستان مدیر دیگر', $overview->getContent());

        $this->getJson('/api/admin/v1/school-panel/members/'.$outsider->id.'/activity')
            ->assertNotFound();
        $this->getJson('/api/admin/v1/school-panel/members/'.$otherMember->id.'/subscriptions')
            ->assertNotFound();
        $this->getJson('/api/admin/v1/school-panel/members/'.$userC->id.'/activity')
            ->assertNotFound();

        $plan = SubscriptionPlan::query()->create([
            'name' => 'یک ماهه',
            'slug' => '1month',
            'duration_days' => 30,
            'price' => 1000,
            'currency' => 'IRT',
            'is_active' => true,
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), 'verify')) {
                return Http::response([
                    'data' => ['code' => 100, 'ref_id' => 77, 'card_pan' => '6219', 'fee' => 0],
                ], 200);
            }

            return Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTH-WALKTHROUGH-1'],
            ], 200);
        });

        $this->postJson('/api/admin/v1/school-panel/members/'.$outsider->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertNotFound();

        $checkout = $this->postJson('/api/admin/v1/school-panel/members/'.$userA->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertCreated()
            ->assertJsonPath('data.payment_url', 'https://sandbox.zarinpal.com/pg/StartPay/AUTH-WALKTHROUGH-1')
            ->assertJsonPath('data.subscription.user_id', $userA->id)
            ->assertJsonPath('data.payment.user_id', $userA->id)
            ->assertJsonPath('data.payment.payer_user_id', $manager->id)
            ->assertJsonPath('data.payment.beneficiary_user_id', $userA->id);

        $paidAmount = (int) $checkout->json('data.payment.amount');
        $payment = Payment::query()->findOrFail($checkout->json('data.payment.id'));
        $this->assertSame('school_panel', $payment->payment_metadata['source']);
        $this->assertSame($userA->id, $payment->user_id);
        $this->assertNotSame($manager->id, $payment->user_id);

        $callback = $this->get('/payment/zarinpal/callback?Authority=AUTH-WALKTHROUGH-1&Status=OK');
        $callback->assertRedirect();
        $location = (string) $callback->headers->get('Location');
        $this->assertStringContainsString('http://localhost:3000/school-panel/members/'.$userA->id, $location);
        $this->assertStringContainsString('payment=ok', $location);
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($userA->id));
        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($manager->id));
        $this->assertSame('active', $payment->subscription->fresh()->status);

        Sanctum::actingAs($super);
        $this->putJson('/api/admin/v1/school-managers/'.$profileId.'/premium-window', [
            'duration_months' => 1,
            'seat_limit' => 2,
            'expires_at' => now()->addDays(14)->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('data.duration_months', 1)
            ->assertJsonPath('data.seat_limit', 2);

        $this->patchJson('/api/admin/v1/school-managers/'.$profileId.'/premium-window', [
            'seat_limit' => 1,
        ])->assertOk()->assertJsonPath('data.seat_limit', 1)->assertJsonPath('data.duration_months', 1);

        $overviewRows = collect($this->getJson('/api/admin/v1/school-managers/premium-overview')->json('data'))->keyBy('id');
        $this->assertSame(2, $overviewRows[$profileId]['member_limit']);
        $this->assertSame(1, $overviewRows[$profileId]['premium_window']['duration_months']);
        $this->assertSame(1, $overviewRows[$profileId]['premium_window']['seat_limit']);

        Sanctum::actingAs($manager);
        $this->getJson('/api/admin/v1/school-panel/me')
            ->assertOk()
            ->assertJsonPath('data.premium_window.duration_months', 1)
            ->assertJsonPath('data.premium_window.seat_limit', 1)
            ->assertJsonPath('data.premium_window.seats_remaining', 1)
            ->assertJsonPath('data.premium_window.accepting_grants', true);

        $granted = $this->postJson('/api/admin/v1/school-panel/members/'.$userB->id.'/premium-grant')
            ->assertCreated()
            ->assertJsonPath('data.subscription.type', '1month')
            ->assertJsonPath('data.grant.user_id', $userB->id)
            ->assertJsonPath('data.grant.duration_months', 1)
            ->assertJsonPath('data.premium_window.seats_used', 1);

        $notification = Notification::query()->findOrFail($granted->json('data.grant.notification_id'));
        $this->assertSame($userB->id, $notification->user_id);
        $this->assertSame('subscription', $notification->type);
        $this->assertSame('اشتراک رایگان برای شما فعال شد', $notification->title);
        $this->assertNotSame($userA->id, $notification->user_id);
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($userB->id));

        $this->postJson('/api/admin/v1/school-panel/members/'.$userA->id.'/premium-grant')
            ->assertStatus(422)
            ->assertJsonPath('error', 'seats_full');
        $this->postJson('/api/admin/v1/school-panel/members/'.$otherMember->id.'/premium-grant')
            ->assertNotFound();
        $this->postJson('/api/admin/v1/school-panel/members/'.$outsider->id.'/premium-grant')
            ->assertNotFound();

        $stats = $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 2)
            ->assertJsonPath('data.total_listens', 3)
            ->assertJsonPath('data.monthly_revenue', $paidAmount);
        $this->assertStringNotContainsString('99999', $stats->getContent());
        $this->assertStringNotContainsString('88888', $stats->getContent());
        $this->assertStringNotContainsString('داستان غریبه پیاده‌روی', $stats->getContent());
        $this->assertStringNotContainsString('داستان مدیر دیگر', $stats->getContent());
        $stats->assertJsonMissingPath('data.total_stories');

        $this->getJson('/api/admin/v1/school-panel/dashboard/online-users')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        $this->getJson('/api/admin/v1/dashboard/stats')->assertForbidden();

        Sanctum::actingAs($otherManager);
        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 1)
            ->assertJsonPath('data.total_listens', 2)
            ->assertJsonPath('data.monthly_revenue', 88888);
        $this->getJson('/api/admin/v1/school-panel/members/'.$userA->id.'/activity')->assertNotFound();
        $this->assertNotSame(
            SchoolDashboardService::statsCacheKey($profileId),
            SchoolDashboardService::statsCacheKey($otherProfileId),
        );
        $this->assertSame(3, Cache::get(SchoolDashboardService::statsCacheKey($profileId))['total_listens']);
        $this->assertSame(2, Cache::get(SchoolDashboardService::statsCacheKey($otherProfileId))['total_listens']);

        Sanctum::actingAs($super);
        $global = $this->getJson('/api/admin/v1/dashboard/stats')->assertOk();
        $this->assertGreaterThan(2, (int) $global->json('data.total_users'));

        Sanctum::actingAs($manager);
        $premiumEnds = Carbon::parse($granted->json('data.subscription.end_date'));
        $this->travelTo(now()->addDays(14)->addMinute());

        $this->postJson('/api/admin/v1/school-panel/members/'.$userA->id.'/premium-grant')
            ->assertStatus(422)
            ->assertJsonPath('error', 'window_closed');
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($userB->id));

        $this->artisan('school:close-premium-windows')
            ->expectsOutput('Windows closed: 1. Grants expired: 0.')
            ->assertSuccessful();
        $this->assertDatabaseHas('school_premium_windows', [
            'school_manager_profile_id' => $profileId,
            'status' => 'expired',
        ]);
        $this->assertSame('active', SchoolPremiumGrant::query()->where('user_id', $userB->id)->value('status'));
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($userB->id));

        $this->artisan('school:close-premium-windows')
            ->expectsOutput('Windows closed: 0. Grants expired: 0.')
            ->assertSuccessful();

        $this->travelTo($premiumEnds->copy()->addMinute());
        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($userB->id));

        $this->artisan('school:close-premium-windows')
            ->expectsOutput('Windows closed: 0. Grants expired: 1.')
            ->assertSuccessful();
        $this->assertSame('expired', SchoolPremiumGrant::query()->where('user_id', $userB->id)->value('status'));

        Cache::flush();
        $after = $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 2)
            ->assertJsonPath('data.total_listens', 3);
        $this->assertStringNotContainsString('99999', $after->getContent());
        $this->assertStringNotContainsString('88888', $after->getContent());
        $this->assertStringNotContainsString('داستان غریبه پیاده‌روی', $after->getContent());
        $this->assertStringNotContainsString('داستان مدیر دیگر', $after->getContent());
        $this->assertNotSame(99999, (int) $after->json('data.monthly_revenue'));
        $this->assertNotSame(88888, (int) $after->json('data.monthly_revenue'));

        Sanctum::actingAs($otherManager);
        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 1)
            ->assertJsonPath('data.total_listens', 2);
    }

    private function makeStory(string $title): Story
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'دسته '.$title,
            'slug' => 'cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Story::query()->create([
            'title' => $title,
            'description' => 'توضیح',
            'image_url' => 'https://example.com/cover.webp',
            'category_id' => $categoryId,
            'age_group' => '7+',
            'language' => 'fa',
            'duration' => 10,
            'status' => 'published',
        ]);
    }

    private function makeEpisode(Story $story, int $number, string $title): Episode
    {
        return Episode::query()->create([
            'story_id' => $story->id,
            'title' => $title,
            'description' => 'توضیح',
            'episode_number' => $number,
            'duration' => 5,
            'status' => 'published',
            'is_premium' => false,
            'audio_url' => 'https://example.com/audio.mp3',
        ]);
    }

    private function play(User $user, Episode $episode, Story $story, bool $completed): void
    {
        PlayHistory::query()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'story_id' => $story->id,
            'played_at' => now()->subHour(),
            'duration_played' => 80,
            'total_duration' => 180,
            'completed' => $completed,
        ]);
    }
}
