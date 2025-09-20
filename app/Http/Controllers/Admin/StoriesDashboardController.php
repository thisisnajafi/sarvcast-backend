<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\PlayHistory;
use App\Models\Rating;
use App\Models\Favorite;
use App\Models\StoryComment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoriesDashboardController extends Controller
{
    /**
     * Display the stories dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Basic statistics
        $stats = [
            'total_stories' => Story::count(),
            'published_stories' => Story::where('status', 'published')->count(),
            'draft_stories' => Story::where('status', 'draft')->count(),
            'total_episodes' => Episode::count(),
            'premium_episodes' => Episode::where('is_premium', true)->count(),
            'free_episodes' => Episode::where('is_premium', false)->count(),
            'published_episodes' => Episode::where('status', 'published')->count(),
            'draft_episodes' => Episode::where('status', 'draft')->count(),
            'total_plays' => PlayHistory::count(),
            'total_ratings' => Rating::count(),
            'avg_rating' => Rating::avg('rating'),
            'total_favorites' => Favorite::count(),
            'total_comments' => StoryComment::count(),
            'approved_comments' => StoryComment::where('is_approved', true)->count(),
            'pending_comments' => StoryComment::where('is_approved', false)->where('is_visible', true)->count(),
            'pinned_comments' => StoryComment::where('is_pinned', true)->count(),
        ];

        // Recent activity
        $recentStories = Story::with(['category', 'episodes'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top performing stories
        $topStories = Story::with(['category'])
            ->withCount(['playHistories as plays_count'])
            ->withAvg('ratings', 'rating')
            ->orderBy('plays_count', 'desc')
            ->limit(10)
            ->get();

        // Category distribution
        $categoryStats = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->get();

        // Daily plays for the selected period
        $dailyPlays = PlayHistory::selectRaw('DATE(played_at) as date, COUNT(*) as plays')
            ->where('played_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Daily comments for the selected period
        $dailyComments = StoryComment::selectRaw('DATE(created_at) as date, COUNT(*) as comments')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Stories by status
        $storiesByStatus = Story::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Episodes per story
        $episodesPerStory = Story::withCount('episodes')
            ->get()
            ->avg('episodes_count');

        // Recent ratings
        $recentRatings = Rating::with(['user', 'story'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Performance metrics
        $performanceMetrics = [
            'avg_plays_per_story' => Story::withCount('playHistories')->get()->avg('play_histories_count'),
            'avg_rating_per_story' => Story::withAvg('ratings', 'rating')->get()->avg('ratings_avg_rating'),
            'completion_rate' => $this->calculateCompletionRate(),
            'engagement_rate' => $this->calculateEngagementRate(),
        ];

        return view('admin.dashboards.stories', compact(
            'stats',
            'recentStories',
            'topStories',
            'categoryStats',
            'dailyPlays',
            'dailyComments',
            'storiesByStatus',
            'episodesPerStory',
            'recentRatings',
            'performanceMetrics',
            'dateRange'
        ));
    }

    /**
     * Get stories analytics data for charts
     */
    public function analytics(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Stories created over time
        $storiesCreated = Story::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Plays over time
        $playsOverTime = PlayHistory::selectRaw('DATE(played_at) as date, COUNT(*) as plays')
            ->where('played_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ratings over time
        $ratingsOverTime = Rating::selectRaw('DATE(created_at) as date, COUNT(*) as ratings, AVG(rating) as avg_rating')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top categories by plays
        $topCategories = Category::withCount(['stories as stories_count'])
            ->withCount(['stories as plays_count' => function ($query) {
                $query->join('play_histories', 'stories.id', '=', 'play_histories.story_id');
            }])
            ->orderBy('plays_count', 'desc')
            ->limit(10)
            ->get();

        // Episodes by premium status
        $episodesByPremium = Episode::selectRaw('is_premium, COUNT(*) as count')
            ->groupBy('is_premium')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->is_premium ? 'premium' : 'free' => $item->count];
            });

        // Episodes by status
        $episodesByStatus = Episode::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Comments over time
        $commentsOverTime = StoryComment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Comments by status
        $commentsByStatus = StoryComment::selectRaw('
            CASE 
                WHEN is_approved = 1 THEN "approved"
                WHEN is_approved = 0 AND is_visible = 1 THEN "pending"
                ELSE "rejected"
            END as status,
            COUNT(*) as count
        ')->groupBy('status')->get();

        // Top stories by comments
        $topStoriesByComments = Story::withCount('comments')
            ->orderBy('comments_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stories_created' => $storiesCreated,
                'plays_over_time' => $playsOverTime,
                'ratings_over_time' => $ratingsOverTime,
                'top_categories' => $topCategories,
                'episodes_by_premium' => $episodesByPremium,
                'episodes_by_status' => $episodesByStatus,
                'comments_over_time' => $commentsOverTime,
                'comments_by_status' => $commentsByStatus,
                'top_stories_by_comments' => $topStoriesByComments,
                'date_range' => $dateRange
            ]
        ]);
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate(): float
    {
        $totalPlays = PlayHistory::count();
        if ($totalPlays === 0) return 0;

        // Assuming completion means plays that lasted more than 80% of episode duration
        $completedPlays = PlayHistory::whereRaw('play_duration >= (episode_duration * 0.8)')->count();
        
        return round(($completedPlays / $totalPlays) * 100, 2);
    }

    /**
     * Calculate engagement rate
     */
    private function calculateEngagementRate(): float
    {
        $totalStories = Story::count();
        if ($totalStories === 0) return 0;

        $engagedStories = Story::whereHas('ratings')
            ->orWhereHas('favorites')
            ->count();

        return round(($engagedStories / $totalStories) * 100, 2);
    }

    /**
     * Export stories data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $type = $request->get('type', 'all');

        // This would implement actual export functionality
        return redirect()->back()
            ->with('success', "گزارش داستان‌ها با فرمت {$format} آماده دانلود است.");
    }
}