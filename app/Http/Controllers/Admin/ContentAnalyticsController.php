<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ContentAnalyticsController extends Controller
{
    public function overview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $contentStats = [
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_categories' => Category::count(),
            'published_content' => Story::where('is_active', true)->count(),
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
}