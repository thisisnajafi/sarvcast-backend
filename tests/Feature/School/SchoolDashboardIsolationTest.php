<?php

namespace Tests\Feature\School;

use App\Models\Episode;
use App\Models\Favorite;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\PlayHistory;
use App\Models\Role;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\Story;
use App\Models\StoryComment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DashboardListeningAnalyticsService;
use App\Services\SchoolDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolDashboardIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $managerA;

    private User $managerB;

    private SchoolManagerProfile $profileA;

    private SchoolManagerProfile $profileB;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-13 15:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_each_manager_sees_only_their_members_and_cache_keys_stay_apart(): void
    {
        $this->seedTwoPanels();

        Sanctum::actingAs($this->managerA);
        $statsA = $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 1)
            ->assertJsonPath('data.active_users', 1)
            ->assertJsonPath('data.active_subscriptions', 1)
            ->assertJsonPath('data.pending_comments', 1)
            ->assertJsonPath('data.monthly_revenue', 1111)
            ->assertJsonPath('data.total_listens', 2)
            ->assertJsonPath('data.listens_today', 2)
            ->assertJsonPath('data.favorites_today', 1)
            ->assertJsonPath('data.total_favorites', 1);

        $statsA->assertJsonMissingPath('data.total_stories');
        $statsA->assertJsonMissingPath('data.published_stories');
        $statsA->assertJsonMissingPath('data.total_episodes');
        $statsA->assertJsonMissingPath('data.published_episodes');
        $this->assertStringNotContainsString('7777', $statsA->getContent());
        $this->assertStringNotContainsString('8888', $statsA->getContent());
        $this->assertStringNotContainsString('99999', $statsA->getContent());
        $this->assertStringNotContainsString('داستان پنل ب', $statsA->getContent());
        $this->assertStringNotContainsString('داستان غریبه', $statsA->getContent());

        $chartsA = $this->getJson('/api/admin/v1/school-panel/dashboard/charts')->assertOk();
        $todayA = collect($chartsA->json('data.daily'))->firstWhere('date', '2026-10-13');
        $this->assertSame(2, $todayA['new_users']);
        $this->assertSame(1111, $todayA['revenue']);
        $this->assertSame(2, $todayA['listens']);
        $this->assertSame('داستان پنل الف', $chartsA->json('data.top_stories_today.0.title'));
        $this->assertSame(2, $chartsA->json('data.top_stories_today.0.listen_count'));
        $this->assertArrayNotHasKey('content', $chartsA->json('data.breakdown'));
        $this->assertSame(1, $chartsA->json('data.breakdown.users.active'));
        $this->assertSame(1, $chartsA->json('data.breakdown.subscriptions.active'));
        $this->assertStringNotContainsString('داستان پنل ب', $chartsA->getContent());

        $this->getJson('/api/admin/v1/school-panel/dashboard/online-users')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.window_minutes', 15);

        Sanctum::actingAs($this->managerB);
        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 2)
            ->assertJsonPath('data.active_users', 1)
            ->assertJsonPath('data.monthly_revenue', 7777)
            ->assertJsonPath('data.total_listens', 5)
            ->assertJsonPath('data.pending_comments', 0)
            ->assertJsonPath('data.active_subscriptions', 1);

        $chartsB = $this->getJson('/api/admin/v1/school-panel/dashboard/charts')->assertOk();
        $todayB = collect($chartsB->json('data.daily'))->firstWhere('date', '2026-10-13');
        $yesterdayB = collect($chartsB->json('data.daily'))->firstWhere('date', '2026-10-12');
        $this->assertSame(1, $todayB['new_users']);
        $this->assertSame(1, $yesterdayB['new_users']);
        $this->assertSame(7777, $todayB['revenue']);
        $this->assertSame(5, $todayB['listens']);
        $this->assertSame('داستان پنل ب', $chartsB->json('data.top_stories_today.0.title'));
        $this->assertStringNotContainsString('داستان پنل الف', $chartsB->getContent());
        $this->assertStringNotContainsString('1111', $chartsB->getContent());

        $this->getJson('/api/admin/v1/school-panel/dashboard/online-users')
            ->assertOk()
            ->assertJsonPath('data.count', 2);

        $cachedA = Cache::get('school_dashboard_'.$this->profileA->id.'_stats_v1');
        $cachedB = Cache::get('school_dashboard_'.$this->profileB->id.'_stats_v1');
        $this->assertIsArray($cachedA);
        $this->assertIsArray($cachedB);
        $this->assertSame(2, $cachedA['total_listens']);
        $this->assertSame(1111, $cachedA['monthly_revenue']);
        $this->assertSame(5, $cachedB['total_listens']);
        $this->assertSame(7777, $cachedB['monthly_revenue']);
        $this->assertSame(
            'school_dashboard_'.$this->profileA->id.'_stats_v1',
            SchoolDashboardService::statsCacheKey($this->profileA->id),
        );
        $this->assertNotSame(
            SchoolDashboardService::statsCacheKey($this->profileA->id),
            SchoolDashboardService::statsCacheKey($this->profileB->id),
        );
        $this->assertSame(2, Cache::get(SchoolDashboardService::chartsCacheKey($this->profileA->id))['daily'][6]['listens']);
        $this->assertSame(5, Cache::get(SchoolDashboardService::chartsCacheKey($this->profileB->id))['daily'][6]['listens']);
    }

    public function test_super_admin_home_stays_global_and_the_school_manager_cannot_open_it(): void
    {
        $this->seedTwoPanels();

        $listening = app(DashboardListeningAnalyticsService::class)->getStats();
        $this->assertSame(PlayHistory::query()->count(), $listening['total_listens']);
        $this->assertTrue(Cache::has('admin_dashboard_listening_stats_v1'));
        $this->assertFalse(Cache::has('school_dashboard_'.$this->profileA->id.'_stats_v1'));

        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $globalUsers = User::query()->count();
        $globalRevenue = (int) Payment::query()
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $this->getJson('/api/admin/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', $globalUsers)
            ->assertJsonPath('data.total_stories', Story::query()->count())
            ->assertJsonPath('data.published_stories', Story::query()->where('status', 'published')->count())
            ->assertJsonPath('data.monthly_revenue', $globalRevenue)
            ->assertJsonPath('data.total_listens', PlayHistory::query()->count());

        $this->assertGreaterThan(1111, $globalRevenue);
        $this->assertGreaterThan(2, PlayHistory::query()->count());
        $this->assertSame(
            PlayHistory::query()->count(),
            Cache::get('admin_dashboard_listening_stats_v1')['total_listens'],
        );

        Sanctum::actingAs($this->managerA);
        $this->getJson('/api/admin/v1/dashboard/stats')->assertForbidden();
        $this->getJson('/api/admin/v1/dashboard/charts')->assertForbidden();
        $this->getJson('/api/admin/v1/dashboard/online-users')->assertForbidden();
        $this->assertSame(
            PlayHistory::query()->count(),
            Cache::get('admin_dashboard_listening_stats_v1')['total_listens'],
        );

        Sanctum::actingAs($super);
        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')->assertForbidden();
    }

    public function test_empty_roster_is_zero_and_a_removed_member_leaves_the_numbers(): void
    {
        [$manager] = $this->panel('09123000021', 5);
        $member = User::factory()->create([
            'phone_number' => '09123000022',
            'last_activity_at' => now(),
        ]);
        $stranger = User::factory()->create([
            'phone_number' => '09123000023',
            'last_activity_at' => now(),
        ]);
        $this->attach($manager, $manager->schoolManagerProfile, $member);
        $story = $this->makeStory('داستان موقت');
        $hidden = $this->makeStory('داستان بیرون');
        $this->play($member, $this->makeEpisode($story, 1, 'قسمت موقت'), $story, true);
        $this->play($stranger, $this->makeEpisode($hidden, 1, 'قسمت بیرون'), $hidden, true);
        $this->pay($member, 3333);
        $this->pay($stranger, 4444);

        Sanctum::actingAs($manager);
        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 1)
            ->assertJsonPath('data.total_listens', 1)
            ->assertJsonPath('data.monthly_revenue', 3333);
        $this->getJson('/api/admin/v1/school-panel/dashboard/online-users')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        $membership = SchoolMembership::query()->where('user_id', $member->id)->firstOrFail();
        $membership->update([
            'active_user_id' => null,
            'detached_at' => now(),
        ]);
        Cache::forget(SchoolDashboardService::statsCacheKey($manager->schoolManagerProfile->id));
        Cache::forget(SchoolDashboardService::chartsCacheKey($manager->schoolManagerProfile->id));

        $after = $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 0)
            ->assertJsonPath('data.active_users', 0)
            ->assertJsonPath('data.total_listens', 0)
            ->assertJsonPath('data.monthly_revenue', 0)
            ->assertJsonPath('data.pending_comments', 0);
        $this->assertStringNotContainsString('4444', $after->getContent());
        $this->assertStringNotContainsString('داستان بیرون', $after->getContent());

        $charts = $this->getJson('/api/admin/v1/school-panel/dashboard/charts')->assertOk();
        $today = collect($charts->json('data.daily'))->firstWhere('date', '2026-10-13');
        $this->assertSame(1, $today['new_users']);
        $this->assertSame(0, $today['revenue']);
        $this->assertSame(0, $today['listens']);
        $this->assertSame([], $charts->json('data.top_stories_today'));

        $this->getJson('/api/admin/v1/school-panel/dashboard/online-users')
            ->assertOk()
            ->assertJsonPath('data.count', 0);

        [$emptyManager] = $this->panel('09123000024', 5);
        Sanctum::actingAs($emptyManager);
        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_users', 0)
            ->assertJsonPath('data.total_listens', 0)
            ->assertJsonPath('data.monthly_revenue', 0);
        $this->getJson('/api/admin/v1/school-panel/dashboard/online-users')
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_dashboard_requires_the_panel_permission_and_an_active_profile(): void
    {
        [$manager, $profile] = $this->panel('09123000031', 5);
        $role = Role::query()->where('name', 'school_manager')->firstOrFail();
        $permission = Permission::query()->where('name', 'school_panel.dashboard.view')->firstOrFail();
        $role->permissions()->detach($permission->id);

        Sanctum::actingAs($manager);
        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')->assertForbidden();
        $this->getJson('/api/admin/v1/school-panel/dashboard/charts')->assertForbidden();
        $this->getJson('/api/admin/v1/school-panel/dashboard/online-users')->assertForbidden();

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $profile->update(['status' => SchoolManagerProfile::STATUS_SUSPENDED]);

        $this->getJson('/api/admin/v1/school-panel/dashboard/stats')
            ->assertForbidden()
            ->assertJsonPath('error', 'PANEL_SUSPENDED');
    }

    private function seedTwoPanels(): void
    {
        [$this->managerA, $this->profileA] = $this->panel('09123000001', 10);
        [$this->managerB, $this->profileB] = $this->panel('09123000002', 10);

        $memberA = User::factory()->create([
            'phone_number' => '09123000003',
            'status' => User::STATUS_ACTIVE,
            'last_activity_at' => now(),
        ]);
        $removed = User::factory()->create([
            'phone_number' => '09123000004',
            'last_activity_at' => now(),
        ]);
        $memberB = User::factory()->create([
            'phone_number' => '09123000005',
            'status' => User::STATUS_ACTIVE,
            'last_activity_at' => now(),
        ]);
        $memberBQuiet = User::factory()->create([
            'phone_number' => '09123000006',
            'status' => User::STATUS_INACTIVE,
            'last_activity_at' => now(),
        ]);
        $stranger = User::factory()->create([
            'phone_number' => '09123000007',
            'last_activity_at' => now(),
        ]);

        $this->attach($this->managerA, $this->profileA, $memberA);
        $this->attach($this->managerB, $this->profileB, $memberB);
        $quiet = $this->attach($this->managerB, $this->profileB, $memberBQuiet);
        SchoolMembership::query()->whereKey($quiet->id)->update(['created_at' => now()->subDay()]);
        SchoolMembership::query()->create([
            'school_manager_profile_id' => $this->profileA->id,
            'user_id' => $removed->id,
            'active_user_id' => null,
            'attached_by' => $this->managerA->id,
            'detached_at' => now(),
            'detached_by' => $this->managerA->id,
        ]);

        $storyA = $this->makeStory('داستان پنل الف');
        $storyB = $this->makeStory('داستان پنل ب');
        $storyGone = $this->makeStory('داستان جداشده');
        $storyOut = $this->makeStory('داستان غریبه');

        $this->play($memberA, $this->makeEpisode($storyA, 1, 'قسمت الف ۱'), $storyA, true);
        $this->play($memberA, $this->makeEpisode($storyA, 2, 'قسمت الف ۲'), $storyA, false);
        Favorite::query()->create([
            'user_id' => $memberA->id,
            'story_id' => $storyA->id,
        ]);
        foreach (range(1, 5) as $number) {
            $this->play($memberB, $this->makeEpisode($storyB, $number, 'قسمت ب '.$number), $storyB, true);
        }
        foreach (range(1, 4) as $number) {
            $this->play($removed, $this->makeEpisode($storyGone, $number, 'قسمت جدا '.$number), $storyGone, true);
        }
        foreach (range(1, 9) as $number) {
            $this->play($stranger, $this->makeEpisode($storyOut, $number, 'قسمت غریبه '.$number), $storyOut, true);
        }
        Favorite::query()->create([
            'user_id' => $stranger->id,
            'story_id' => $storyOut->id,
        ]);

        $this->pay($memberA, 1111);
        Payment::query()->create([
            'user_id' => $memberA->id,
            'amount' => 50,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'status' => 'pending',
            'transaction_id' => 'PENDING-A',
        ]);
        $this->pay($removed, 8888);
        $this->pay($memberB, 7777);
        $this->pay($stranger, 99999);

        $this->subscribe($memberA, 'active');
        $this->subscribe($memberB, 'active');
        $this->subscribe($removed, 'active');
        $this->subscribe($stranger, 'active');

        StoryComment::query()->create([
            'story_id' => $storyA->id,
            'user_id' => $memberA->id,
            'content' => 'نظر عضو الف',
            'is_approved' => false,
        ]);
        StoryComment::query()->create([
            'story_id' => $storyOut->id,
            'user_id' => $stranger->id,
            'content' => 'نظر غریبه',
            'is_approved' => false,
        ]);
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

    private function attach(User $manager, SchoolManagerProfile $profile, User $member): SchoolMembership
    {
        return SchoolMembership::query()->create([
            'school_manager_profile_id' => $profile->id,
            'user_id' => $member->id,
            'active_user_id' => $member->id,
            'attached_by' => $manager->id,
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

    private function pay(User $user, int $amount): void
    {
        Payment::query()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'status' => 'completed',
            'transaction_id' => 'PAY-'.$user->id.'-'.$amount,
        ]);
    }

    private function subscribe(User $user, string $status): void
    {
        Subscription::query()->create([
            'user_id' => $user->id,
            'type' => '1month',
            'status' => $status,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'price' => 1000,
            'currency' => 'IRR',
        ]);
    }
}
