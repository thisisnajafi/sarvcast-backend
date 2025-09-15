<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\Subscription;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get dashboard statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_stories' => Story::count(),
            'published_stories' => Story::where('status', 'published')->count(),
            'total_episodes' => Episode::count(),
            'published_episodes' => Episode::where('status', 'published')->count(),
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
        ];

        // Get recent stories
        $recentStories = Story::with(['category', 'director', 'narrator'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent users
        $recentUsers = User::latest()
            ->limit(5)
            ->get();

        // Get top categories by story count
        $topCategories = Category::withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentStories', 'recentUsers', 'topCategories'));
    }
}
