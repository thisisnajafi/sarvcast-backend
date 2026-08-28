<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Payment;
use App\Models\Story;
use App\Models\StoryComment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DashboardListeningAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardController extends Controller
{
    private const ONLINE_WINDOW_MINUTES = 15;

    public function __construct(
        private DashboardListeningAnalyticsService $listeningAnalytics,
    ) {}

    public function stats(): JsonResponse
    {
        $data = array_merge([
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_stories' => Story::count(),
            'published_stories' => Story::where('status', 'published')->count(),
            'total_episodes' => Episode::count(),
            'published_episodes' => Episode::where('status', 'published')->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'pending_comments' => StoryComment::where('is_approved', false)->count(),
            'monthly_revenue' => (int) Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ], $this->listeningAnalytics->getStats());

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats loaded successfully.',
            'data' => $data,
        ]);
    }

    public function charts(): JsonResponse
    {
        $listening = $this->listeningAnalytics->getChartPayload();
        $listeningByDate = collect($listening['daily'] ?? [])->keyBy('date');

        $series = collect(range(6, 0))->map(function ($daysAgo) use ($listeningByDate) {
            $date = now()->subDays($daysAgo)->toDateString();
            $point = $listeningByDate->get($date, []);

            return [
                'date' => $date,
                'new_users' => User::whereDate('created_at', $date)->count(),
                'revenue' => (int) Payment::where('status', 'completed')
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
                'listens' => (int) ($point['listens'] ?? 0),
                'unique_listeners' => (int) ($point['unique_listeners'] ?? 0),
                'unique_stories' => (int) ($point['unique_stories'] ?? 0),
                'favorites' => (int) ($point['favorites'] ?? 0),
            ];
        })->values();

        $publishedStories = Story::where('status', 'published')->count();
        $totalStories = Story::count();
        $publishedEpisodes = Episode::where('status', 'published')->count();
        $totalEpisodes = Episode::count();
        $activeUsers = User::where('status', 'active')->count();
        $totalUsers = User::count();

        $subscriptionByStatus = Subscription::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard chart data loaded successfully.',
            'data' => [
                'daily' => $series,
                'top_stories_today' => $listening['top_stories_today'] ?? [],
                'top_stories_this_week' => $listening['top_stories_this_week'] ?? [],
                'most_favorited_stories' => $listening['most_favorited_stories'] ?? [],
                'most_favorited_episodes' => $listening['most_favorited_episodes'] ?? [],
                'breakdown' => [
                    'content' => [
                        'stories' => [
                            'published' => $publishedStories,
                            'unpublished' => max(0, $totalStories - $publishedStories),
                        ],
                        'episodes' => [
                            'published' => $publishedEpisodes,
                            'unpublished' => max(0, $totalEpisodes - $publishedEpisodes),
                        ],
                    ],
                    'users' => [
                        'active' => $activeUsers,
                        'inactive' => max(0, $totalUsers - $activeUsers),
                    ],
                    'subscriptions' => $subscriptionByStatus,
                ],
            ],
        ]);
    }

    public function onlineUsers(): JsonResponse
    {
        $count = User::where('last_activity_at', '>=', now()->subMinutes(self::ONLINE_WINDOW_MINUTES))->count();

        return response()->json([
            'success' => true,
            'message' => 'Online users count loaded successfully.',
            'count' => $count,
            'data' => [
                'count' => $count,
                'window_minutes' => self::ONLINE_WINDOW_MINUTES,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return app(\App\Http\Controllers\Admin\DashboardController::class)->export($request);
    }
}

