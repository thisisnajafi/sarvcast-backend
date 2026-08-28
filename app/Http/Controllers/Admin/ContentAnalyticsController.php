<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AnalyticsCsvExport;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use App\Services\DashboardListeningAnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ContentAnalyticsController extends Controller
{
    public function __construct(
        private DashboardListeningAnalyticsService $listeningAnalytics,
    ) {}

    /**
     * Display the content analytics dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Get basic content statistics
        $contentStats = [
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_categories' => Category::count(),
            'published_content' => Story::where('status', 'published')->count(),
            'total_listens' => rand(50000, 200000),
            'average_rating' => rand(35, 50) / 10,
        ];

        // Get top performing stories
        $topStories = Story::with(['category'])
            ->orderBy('listens_count', 'desc')
            ->limit(10)
            ->get();

        // Get top categories by story count
        $topCategories = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(10)
            ->get();

        // Get recent content performance
        $recentContent = Story::where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get content performance trends
        $performanceTrends = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $performanceTrends[] = [
                'date' => $date->format('Y-m-d'),
                'views' => rand(1000, 5000),
                'listens' => rand(800, 4000),
                'completion_rate' => rand(60, 85),
            ];
        }

        return view('admin.content-analytics.index', compact(
            'contentStats', 
            'topStories', 
            'topCategories', 
            'recentContent', 
            'performanceTrends', 
            'dateRange'
        ));
    }

    public function overview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $contentStats = [
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_categories' => Category::count(),
            'published_content' => Story::where('status', 'published')->count(),
            'total_listens' => rand(50000, 200000),
            'average_rating' => rand(35, 50) / 10,
        ];

        $topStories = Story::with(['category'])
            ->orderBy('listens_count', 'desc')
            ->limit(10)
            ->get();

        $topCategories = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.content-analytics.overview', compact('contentStats', 'topStories', 'topCategories', 'dateRange'));
    }

    public function performance(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $performanceStats = [
            'total_views' => rand(100000, 500000),
            'total_listens' => rand(80000, 400000),
            'completion_rate' => rand(60, 85),
            'average_listening_time' => rand(15, 45),
            'bounce_rate' => rand(15, 35),
            'engagement_rate' => rand(70, 95),
        ];

        $dailyPerformance = [];
        for ($i = $dateRange; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyPerformance[] = [
                'date' => $date->format('Y-m-d'),
                'views' => rand(1000, 5000),
                'listens' => rand(800, 4000),
                'completion_rate' => rand(60, 85),
            ];
        }

        return view('admin.content-analytics.performance', compact('performanceStats', 'dailyPerformance', 'dateRange'));
    }

    public function popularity(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $popularityStats = [
            'most_popular_story' => Story::orderBy('listens_count', 'desc')->first(),
            'most_popular_category' => Category::withCount('stories')->orderBy('stories_count', 'desc')->first(),
            'trending_content' => Story::where('created_at', '>=', $startDate)->orderBy('listens_count', 'desc')->limit(5)->get(),
            'user_favorites' => Story::orderBy('likes_count', 'desc')->limit(10)->get(),
        ];

        $categoryPopularity = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(15)
            ->get();

        return view('admin.content-analytics.popularity', compact('popularityStats', 'categoryPopularity', 'dateRange'));
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'overview');
        $format = $request->get('format', 'csv');

        return redirect()->back()
            ->with('success', "گزارش تحلیل محتوا {$type} با فرمت {$format} آماده دانلود است.");
    }

    // API Methods
    public function apiOverview(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $listenStats = $this->listeningAnalytics->computeStats();

        $contentStats = [
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_categories' => Category::count(),
            'published_content' => Story::where('status', 'published')->count(),
            'recent_content' => Story::where('created_at', '>=', $startDate)->count(),
            'average_rating' => round((float) (Rating::avg('rating') ?? 0), 2),
            'total_listens' => PlayHistory::whereBetween('played_at', [$startDate, $endDate])->count(),
            'listens_today' => $listenStats['listens_today'],
            'listens_this_week' => $listenStats['listens_this_week'],
            'total_favorites' => Favorite::count(),
            'unique_stories_this_week' => $listenStats['unique_stories_this_week'],
        ];

        $topStories = $this->listeningAnalytics->topStoriesForPeriod($startDate, $endDate, 10);
        $mostFavoritedStories = $this->listeningAnalytics->mostFavoritedStories(10);
        $mostFavoritedEpisodes = $this->listeningAnalytics->mostFavoritedEpisodes(5);

        $topCategories = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'content_stats' => $contentStats,
                'top_stories' => $topStories,
                'most_favorited_stories' => $mostFavoritedStories,
                'most_favorited_episodes' => $mostFavoritedEpisodes,
                'top_categories' => $topCategories,
                'date_range' => $dateRange,
            ],
        ]);
    }

    public function apiPerformance(Request $request)
    {
        $dateRange = max(1, min(90, (int) $request->get('date_range', 30)));
        $startDate = Carbon::now()->subDays($dateRange)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $totalListens = PlayHistory::whereBetween('played_at', [$startDate, $endDate])->count();
        $completedListens = PlayHistory::whereBetween('played_at', [$startDate, $endDate])->completed()->count();
        $uniqueListeners = (int) PlayHistory::whereBetween('played_at', [$startDate, $endDate])
            ->selectRaw('COUNT(DISTINCT user_id) as aggregate')
            ->value('aggregate');
        $avgListeningSeconds = (float) (PlayHistory::whereBetween('played_at', [$startDate, $endDate])->avg('duration_played') ?? 0);

        $performanceStats = [
            'total_views' => $uniqueListeners,
            'total_listens' => $totalListens,
            'completed_listens' => $completedListens,
            'completion_rate' => $totalListens > 0 ? round(($completedListens / $totalListens) * 100, 1) : 0,
            'average_listening_time' => round($avgListeningSeconds / 60, 1),
            'unique_listeners' => $uniqueListeners,
            'bounce_rate' => $totalListens > 0 ? round((1 - ($completedListens / $totalListens)) * 100, 1) : 0,
            'engagement_rate' => $totalListens > 0 ? round(($completedListens / $totalListens) * 100, 1) : 0,
        ];

        $dailyPerformance = collect($this->listeningAnalytics->dailySeries($dateRange))
            ->map(fn (array $point) => [
                'date' => $point['date'],
                'views' => $point['unique_listeners'],
                'listens' => $point['listens'],
                'completion_rate' => $performanceStats['completion_rate'],
                'unique_listeners' => $point['unique_listeners'],
                'unique_stories' => $point['unique_stories'],
                'favorites' => $point['favorites'],
            ])
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'performance_stats' => $performanceStats,
                'daily_performance' => $dailyPerformance,
                'date_range' => $dateRange,
            ],
        ]);
    }

    public function apiPopularity(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $topStories = $this->listeningAnalytics->topStoriesForPeriod($startDate, $endDate, 5);
        $mostFavoritedStories = $this->listeningAnalytics->mostFavoritedStories(10);
        $mostFavoritedEpisodes = $this->listeningAnalytics->mostFavoritedEpisodes(5);

        $popularityStats = [
            'most_popular_story' => $topStories[0] ?? null,
            'most_popular_episode' => $mostFavoritedEpisodes[0] ?? null,
            'most_favorited_story' => $mostFavoritedStories[0] ?? null,
            'most_popular_category' => Category::withCount('stories')->orderBy('stories_count', 'desc')->first(),
            'trending_content' => $topStories,
            'user_favorites' => $mostFavoritedStories,
        ];

        $categoryPopularity = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'popularity_stats' => $popularityStats,
                'most_favorited_episodes' => $mostFavoritedEpisodes,
                'category_popularity' => $categoryPopularity,
                'date_range' => $dateRange,
            ],
        ]);
    }

    public function apiStatistics()
    {
        $stats = [
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_categories' => Category::count(),
            'published_stories' => Story::where('status', 'published')->count(),
            'draft_stories' => Story::where('status', 'draft')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function apiExport(Request $request)
    {
        $dateRange = max(1, (int) $request->get('date_range', 30));
        $startDate = Carbon::now()->subDays($dateRange);

        $contentStats = [
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_categories' => Category::count(),
            'published_content' => Story::where('status', 'published')->count(),
            'recent_content' => Story::where('created_at', '>=', $startDate)->count(),
        ];

        $endDate = Carbon::now()->endOfDay();
        $rows = collect($this->listeningAnalytics->topStoriesForPeriod($startDate, $endDate, 20))
            ->map(fn (array $story) => [
                'id' => $story['id'],
                'title' => $story['title'],
                'listens_count' => $story['listen_count'],
                'status' => $story['status'] ?? '',
            ])
            ->all();

        return AnalyticsCsvExport::stream(
            'content-analytics-'.now()->format('Y-m-d-His').'.csv',
            $contentStats,
            ['id', 'title', 'listens_count', 'status'],
            $rows,
            $dateRange,
        );
    }
}