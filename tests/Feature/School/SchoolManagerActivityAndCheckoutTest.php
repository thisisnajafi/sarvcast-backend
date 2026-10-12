<?php

namespace Tests\Feature\School;

use App\Models\Episode;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\PlayHistory;
use App\Models\Role;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolManagerActivityAndCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $key = str_repeat('a', 32);
        config([
            'services.zarinpal.sandbox' => true,
            'app.admin_dashboard_url' => 'http://localhost:3000',
            'app.key' => 'base64:'.base64_encode($key),
        ]);
        $this->app->singleton('encrypter', fn () => new Encrypter($key, (string) config('app.cipher')));
    }

    public function test_activity_and_subscriptions_stay_inside_the_managers_roster(): void
    {
        [$managerA] = $this->panel('09121000001', 10);
        [$managerB] = $this->panel('09121000002', 10);
        $member = User::factory()->create([
            'phone_number' => '09121000003',
            'first_name' => 'نیکی',
            'last_name' => 'عضو',
        ]);
        $other = User::factory()->create([
            'phone_number' => '09121000004',
            'first_name' => 'دیگری',
        ]);
        $stranger = User::factory()->create(['phone_number' => '09121000005']);
        $this->attach($managerA, $managerA->schoolManagerProfile, $member);
        $this->attach($managerB, $managerB->schoolManagerProfile, $other);

        $ownStory = $this->makeStory('داستان روباه');
        $secondStory = $this->makeStory('داستان رودخانه');
        $hiddenStory = $this->makeStory('داستان پنهان دیگران');
        $episodeOne = $this->makeEpisode($ownStory, 1, 'قسمت روباه');
        $episodeTwo = $this->makeEpisode($ownStory, 2, 'قسمت دوم روباه');
        $episodeThree = $this->makeEpisode($secondStory, 1, 'قسمت رودخانه');
        $hiddenEpisode = $this->makeEpisode($hiddenStory, 1, 'قسمت پنهان');

        $this->play($member, $episodeOne, $ownStory, 100, true);
        $this->play($member, $episodeTwo, $ownStory, 40, false);
        $this->play($member, $episodeThree, $secondStory, 90, true);
        $this->play($other, $hiddenEpisode, $hiddenStory, 300, true);

        Subscription::query()->create([
            'user_id' => $member->id,
            'type' => '3months',
            'status' => 'expired',
            'start_date' => now()->subMonths(4),
            'end_date' => now()->subMonth(),
            'price' => 1500,
            'currency' => 'IRR',
        ]);
        Payment::query()->create([
            'user_id' => $member->id,
            'amount' => 1500,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'status' => 'completed',
            'transaction_id' => 'MEMBER-PAY-1500',
            'payment_metadata' => ['source' => 'web'],
        ]);
        Subscription::query()->create([
            'user_id' => $other->id,
            'type' => '1year',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'price' => 424242,
            'currency' => 'IRR',
        ]);

        Sanctum::actingAs($managerA);

        $overview = $this->getJson('/api/admin/v1/school-panel/activity')
            ->assertOk()
            ->assertJsonPath('data.totals.play_count', 3)
            ->assertJsonPath('data.totals.distinct_stories', 2)
            ->assertJsonPath('data.totals.completed_count', 2)
            ->assertJsonPath('data.members.0.user_id', $member->id)
            ->assertJsonPath('data.members.0.play_count', 3);

        $this->assertStringNotContainsString('داستان پنهان دیگران', $overview->getContent());
        $this->assertCount(1, $overview->json('data.members'));

        $this->getJson('/api/admin/v1/school-panel/members')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $member->id)
            ->assertJsonPath('data.0.listen_count', 3)
            ->assertJsonPath('data.0.distinct_stories', 2)
            ->assertJsonPath('data.0.completed_count', 2)
            ->assertJsonPath('data.0.subscription', null);

        $activity = $this->getJson('/api/admin/v1/school-panel/members/'.$member->id.'/activity')
            ->assertOk()
            ->assertJsonPath('data.user.id', $member->id)
            ->assertJsonPath('data.user.first_name', 'نیکی')
            ->assertJsonPath('data.totals.play_count', 3)
            ->assertJsonPath('data.totals.distinct_stories', 2)
            ->assertJsonPath('data.totals.completed_count', 2)
            ->assertJsonPath('data.meta.total', 3);

        $titles = collect($activity->json('data.plays'))->pluck('story_title');
        $this->assertTrue($titles->contains('داستان روباه'));
        $this->assertTrue($titles->contains('داستان رودخانه'));
        $this->assertFalse($titles->contains('داستان پنهان دیگران'));
        $fox = collect($activity->json('data.plays'))->firstWhere('episode_title', 'قسمت روباه');
        $this->assertSame(100, (int) $fox['duration_played']);
        $this->assertTrue($fox['completed']);

        $plans = $this->getJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions')
            ->assertOk()
            ->assertJsonPath('data.subscriptions.0.type', '3months')
            ->assertJsonPath('data.subscriptions.0.grants_access', false)
            ->assertJsonPath('data.payments.0.amount', 1500);

        $this->assertStringNotContainsString('424242', $plans->getContent());

        $this->getJson('/api/admin/v1/school-panel/members/'.$other->id.'/activity')
            ->assertNotFound()
            ->assertJsonPath('error', 'not_found');
        $this->getJson('/api/admin/v1/school-panel/members/'.$other->id.'/subscriptions')
            ->assertNotFound();
        $this->getJson('/api/admin/v1/school-panel/members/'.$stranger->id.'/activity')
            ->assertNotFound()
            ->assertJsonPath('message', 'این کاربر در پنل شما نیست.');

        $this->deleteJson('/api/admin/v1/school-panel/members/'.$member->id)->assertOk();
        $this->getJson('/api/admin/v1/school-panel/members/'.$member->id.'/activity')->assertNotFound();
    }

    public function test_purchase_uses_zarinpal_for_the_member_and_the_callback_grants_access(): void
    {
        [$manager] = $this->panel('09121000011', 5);
        $member = User::factory()->create(['phone_number' => '09121000012', 'first_name' => 'کیان']);
        $outsider = User::factory()->create(['phone_number' => '09121000013']);
        $this->attach($manager, $manager->schoolManagerProfile, $member);
        $plan = $this->plan();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), 'verify')) {
                return Http::response([
                    'data' => ['code' => 100, 'ref_id' => 55, 'card_pan' => '6219', 'fee' => 0],
                ], 200);
            }

            return Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTH-SCHOOL-1'],
            ], 200);
        });

        Sanctum::actingAs($manager);

        $this->postJson('/api/admin/v1/school-panel/members/'.$outsider->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertNotFound()->assertJsonPath('error', 'not_found');
        $this->assertSame(0, Subscription::query()->count());

        $created = $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertCreated()
            ->assertJsonPath('data.reused', false)
            ->assertJsonPath('data.authority', 'AUTH-SCHOOL-1')
            ->assertJsonPath('data.payment_url', 'https://sandbox.zarinpal.com/pg/StartPay/AUTH-SCHOOL-1')
            ->assertJsonPath('data.subscription.user_id', $member->id)
            ->assertJsonPath('data.subscription.auto_renew', false)
            ->assertJsonPath('data.payment.user_id', $member->id)
            ->assertJsonPath('data.payment.payer_user_id', $manager->id)
            ->assertJsonPath('data.payment.beneficiary_user_id', $member->id)
            ->assertJsonPath('data.payment.amount', 10000);

        $payment = Payment::query()->findOrFail($created->json('data.payment.id'));
        $this->assertSame($member->id, $payment->user_id);
        $this->assertSame($member->id, $payment->subscription->user_id);
        $this->assertNotSame($manager->id, $payment->user_id);
        $this->assertSame('school_panel', $payment->payment_metadata['source']);
        $this->assertSame($manager->id, (int) $payment->payment_metadata['payer_user_id']);
        $this->assertSame($member->id, (int) $payment->payment_metadata['beneficiary_user_id']);
        $this->assertSame($manager->schoolManagerProfile->id, (int) $payment->payment_metadata['school_manager_profile_id']);
        $this->assertStringStartsWith('http://localhost:3000/school-panel/members/'.$member->id, $payment->payment_metadata['return_url']);

        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertOk()->assertJsonPath('data.reused', true)->assertJsonPath('data.authority', 'AUTH-SCHOOL-1');
        $this->assertSame(1, Subscription::query()->count());

        $callback = $this->get('/payment/zarinpal/callback?Authority=AUTH-SCHOOL-1&Status=OK');
        $callback->assertRedirect();
        $location = (string) $callback->headers->get('Location');
        $this->assertStringContainsString('http://localhost:3000/school-panel/members/'.$member->id, $location);
        $this->assertStringContainsString('payment=ok', $location);
        $this->assertStringContainsString('subscription=activated', $location);
        $this->assertStringNotContainsString('manji://', $location);

        $subscription = $payment->subscription->fresh();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->end_date->greaterThan(now()->addDays(29)));
        $this->assertTrue(app(AccessControlService::class)->hasPremiumAccess($member->id));
        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($manager->id));

        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertStatus(409)->assertJsonPath('error', 'already_premium');
    }

    public function test_failed_gateway_start_removes_the_pending_rows(): void
    {
        [$manager] = $this->panel('09121000021', 5);
        $member = User::factory()->create(['phone_number' => '09121000022']);
        $this->attach($manager, $manager->schoolManagerProfile, $member);
        $plan = $this->plan();

        Http::fake([
            'https://sandbox.zarinpal.com/*' => Http::response('upstream down', 500),
            'https://api.zarinpal.com/*' => Http::response('upstream down', 500),
        ]);

        Sanctum::actingAs($manager);

        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertStatus(500)->assertJsonPath('error', 'payment_failed');

        $this->assertSame(0, Subscription::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertFalse(app(AccessControlService::class)->hasPremiumAccess($member->id));
    }

    public function test_unknown_coupon_is_rejected_and_a_closed_return_url_cannot_leave_the_admin_host(): void
    {
        [$manager] = $this->panel('09121000031', 5);
        $member = User::factory()->create(['phone_number' => '09121000032']);
        $this->attach($manager, $manager->schoolManagerProfile, $member);
        $plan = $this->plan();

        Sanctum::actingAs($manager);

        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => $plan->id,
            'coupon_code' => 'NOT-A-REAL-CODE',
        ])->assertStatus(400)->assertJsonPath('error', 'coupon_invalid');
        $this->assertSame(0, Subscription::query()->count());

        $subscription = Subscription::query()->create([
            'user_id' => $member->id,
            'type' => '1month',
            'status' => 'pending',
            'price' => 10000,
            'currency' => 'IRR',
            'auto_renew' => false,
        ]);
        $payment = Payment::query()->create([
            'user_id' => $member->id,
            'subscription_id' => $subscription->id,
            'amount' => 10000,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'status' => 'pending',
            'transaction_id' => 'AUTH-EVIL',
            'payment_metadata' => [
                'source' => 'school_panel',
                'payer_user_id' => $manager->id,
                'beneficiary_user_id' => $member->id,
                'return_url' => 'https://evil.example/phish',
                'failure_url' => 'https://evil.example/fail',
            ],
        ]);

        $callback = $this->get('/payment/zarinpal/callback?Authority=AUTH-EVIL&Status=NOK');
        $callback->assertRedirect();
        $location = (string) $callback->headers->get('Location');
        $this->assertStringStartsWith('http://localhost:3000/school-panel/members/'.$member->id, $location);
        $this->assertStringContainsString('payment=failed', $location);
        $this->assertStringNotContainsString('evil.example', $location);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_an_old_pending_checkout_does_not_block_a_new_one_and_plans_are_listed(): void
    {
        [$manager, $profile] = $this->panel('09121000041', 5);
        $member = User::factory()->create(['phone_number' => '09121000042']);
        $this->attach($manager, $profile, $member);
        $plan = $this->plan();
        $hidden = SubscriptionPlan::query()->create([
            'name' => 'فروشگاه',
            'slug' => 'store-only',
            'duration_days' => 30,
            'price' => 9,
            'currency' => 'IRT',
            'is_active' => true,
        ]);

        Http::fake([
            'https://sandbox.zarinpal.com/*' => Http::sequence()
                ->push(['data' => ['code' => 100, 'authority' => 'AUTH-OLD']], 200)
                ->push(['data' => ['code' => 100, 'authority' => 'AUTH-NEW']], 200),
            'https://api.zarinpal.com/*' => Http::response(['data' => ['code' => -1]], 200),
        ]);

        Sanctum::actingAs($manager);

        $plans = $this->getJson('/api/admin/v1/school-panel/subscription-plans')->assertOk();
        $slugs = collect($plans->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('1month'));
        $this->assertFalse($slugs->contains($hidden->slug));

        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertCreated()->assertJsonPath('data.authority', 'AUTH-OLD');

        $this->travel(31)->minutes();

        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertCreated()->assertJsonPath('data.authority', 'AUTH-NEW');

        $this->assertSame(2, Subscription::query()->where('user_id', $member->id)->count());
    }

    public function test_activity_permission_and_suspended_panel_are_enforced(): void
    {
        [$manager] = $this->panel('09121000051', 5);
        $member = User::factory()->create(['phone_number' => '09121000052']);
        $this->attach($manager, $manager->schoolManagerProfile, $member);

        $role = Role::query()->where('name', 'school_manager')->firstOrFail();
        $permission = Permission::query()->where('name', 'school_panel.activity.view')->firstOrFail();
        $role->permissions()->detach($permission->id);

        Sanctum::actingAs($manager);
        $this->getJson('/api/admin/v1/school-panel/activity')->assertForbidden();
        $this->getJson('/api/admin/v1/school-panel/members/'.$member->id.'/activity')->assertForbidden();

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $manager->schoolManagerProfile->update(['status' => SchoolManagerProfile::STATUS_SUSPENDED]);

        $this->getJson('/api/admin/v1/school-panel/activity')->assertForbidden()->assertJsonPath('error', 'PANEL_SUSPENDED');
        $this->postJson('/api/admin/v1/school-panel/members/'.$member->id.'/subscriptions', [
            'plan_id' => 1,
        ])->assertForbidden();
    }

    /**
     * @return array{0: User, 1: SchoolManagerProfile}
     */
    private function panel(string $phone, int $limit): array
    {
        $manager = User::factory()->schoolManager()->create(['phone_number' => $phone]);
        $manager->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        $profile = SchoolManagerProfile::query()->create([
            'user_id' => $manager->id,
            'member_limit' => $limit,
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

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::query()->create([
            'name' => 'یک ماهه',
            'slug' => '1month',
            'duration_days' => 30,
            'price' => 1000,
            'currency' => 'IRT',
            'is_active' => true,
            'features' => ['داستان‌های ویژه'],
        ]);
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
            'status' => 'draft',
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
            'status' => 'draft',
            'is_premium' => false,
            'audio_url' => 'https://example.com/audio.mp3',
        ]);
    }

    private function play(User $user, Episode $episode, Story $story, int $seconds, bool $completed): void
    {
        PlayHistory::query()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'story_id' => $story->id,
            'played_at' => now()->subHour(),
            'duration_played' => $seconds,
            'total_duration' => 180,
            'completed' => $completed,
        ]);
    }
}
