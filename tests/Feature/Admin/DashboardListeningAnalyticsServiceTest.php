<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Favorite;
use App\Models\PlayHistory;
use App\Models\Story;
use App\Models\User;
use App\Services\DashboardListeningAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DashboardListeningAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardListeningAnalyticsService $service;

    private Story $popularStory;

    private Story $favoritedStory;

    private Episode $popularEpisode;

    private Episode $favoritedEpisode;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Carbon::setTestNow(Carbon::parse('2026-08-28 12:00:00'));

        $this->service = app(DashboardListeningAnalyticsService::class);

        $category = Category::create([
            'name' => 'قصه شب',
            'slug' => 'bedtime-analytics',
            'description' => 'Test',
            'color' => '#00BCD4',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->favoritedStory = Story::create([
            'title' => 'داستان محبوب',
            'description' => 'Most favorited story',
            'image_url' => 'images/stories/fav.jpg',
            'category_id' => $category->id,
            'age_group' => 'all',
            'duration' => 20,
            'status' => 'published',
        ]);

        $this->popularStory = Story::create([
            'title' => 'داستان پربازدید',
            'description' => 'Most listened story today',
            'image_url' => 'images/stories/hot.jpg',
            'category_id' => $category->id,
            'age_group' => 'all',
            'duration' => 15,
            'status' => 'published',
        ]);

        $this->favoritedEpisode = Episode::create([
            'story_id' => $this->favoritedStory->id,
            'title' => 'قسمت محبوب',
            'audio_url' => 'audio/fav.mp3',
            'duration' => 12,
            'episode_number' => 1,
            'status' => 'published',
        ]);

        $this->popularEpisode = Episode::create([
            'story_id' => $this->popularStory->id,
            'title' => 'قسمت پربازدید',
            'audio_url' => 'audio/hot.mp3',
            'duration' => 8,
            'episode_number' => 1,
            'status' => 'published',
        ]);

        $fans = User::factory()->count(3)->create();
        $listeners = User::factory()->count(5)->create();

        foreach ($fans as $fan) {
            Favorite::create([
                'user_id' => $fan->id,
                'story_id' => $this->favoritedStory->id,
                'created_at' => now(),
            ]);

            PlayHistory::create([
                'user_id' => $fan->id,
                'episode_id' => $this->favoritedEpisode->id,
                'story_id' => $this->favoritedStory->id,
                'played_at' => now(),
                'duration_played' => 500,
                'total_duration' => 720,
                'completed' => true,
            ]);
        }

        Favorite::create([
            'user_id' => $listeners[0]->id,
            'story_id' => $this->popularStory->id,
            'created_at' => now()->subDays(3),
        ]);

        foreach ($listeners as $listener) {
            PlayHistory::create([
                'user_id' => $listener->id,
                'episode_id' => $this->popularEpisode->id,
                'story_id' => $this->popularStory->id,
                'played_at' => now(),
                'duration_played' => 200,
                'total_duration' => 480,
                'completed' => false,
            ]);
        }

        PlayHistory::create([
            'user_id' => $listeners[0]->id,
            'episode_id' => $this->popularEpisode->id,
            'story_id' => $this->popularStory->id,
            'played_at' => now()->subDays(2),
            'duration_played' => 200,
            'total_duration' => 480,
            'completed' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stats_count_daily_and_weekly_listens(): void
    {
        $stats = $this->service->computeStats();

        $this->assertSame(8, $stats['listens_today']);
        $this->assertSame(9, $stats['listens_this_week']);
        $this->assertSame(8, $stats['unique_listeners_today']);
        $this->assertSame(2, $stats['unique_stories_today']);
        $this->assertSame(3, $stats['completed_listens_today']);
        $this->assertSame(4, $stats['total_favorites']);
        $this->assertSame(3, $stats['favorites_today']);
    }

    public function test_top_stories_today_rank_by_listen_count(): void
    {
        $top = $this->service->topStoriesForPeriod(now()->startOfDay(), now()->endOfDay());

        $this->assertSame($this->popularStory->id, $top[0]['id']);
        $this->assertSame(5, $top[0]['listen_count']);
        $this->assertSame(5, $top[0]['unique_listeners']);
        $this->assertSame('قصه شب', $top[0]['category']);
        $this->assertSame($this->favoritedStory->id, $top[1]['id']);
        $this->assertSame(3, $top[1]['listen_count']);
    }

    public function test_most_favorited_story_and_episode_include_details(): void
    {
        $stories = $this->service->mostFavoritedStories();
        $episodes = $this->service->mostFavoritedEpisodes();

        $this->assertSame($this->favoritedStory->id, $stories[0]['id']);
        $this->assertSame(3, $stories[0]['favorites_count']);
        $this->assertSame(3, $stories[0]['listen_count']);
        $this->assertSame('داستان محبوب', $stories[0]['title']);

        $this->assertSame($this->favoritedEpisode->id, $episodes[0]['id']);
        $this->assertSame('favorited_listeners', $episodes[0]['source']);
        $this->assertSame(3, $episodes[0]['favorited_listener_plays']);
        $this->assertSame($this->favoritedStory->id, $episodes[0]['story_id']);
        $this->assertSame(1, $episodes[0]['episode_number']);
    }

    public function test_daily_series_fills_empty_days(): void
    {
        $daily = $this->service->dailySeries(7);

        $this->assertCount(7, $daily);
        $this->assertSame('2026-08-28', $daily[6]['date']);
        $this->assertSame(8, $daily[6]['listens']);
        $this->assertSame(3, $daily[6]['favorites']);
        $this->assertSame('2026-08-26', $daily[4]['date']);
        $this->assertSame(1, $daily[4]['listens']);
    }
}
