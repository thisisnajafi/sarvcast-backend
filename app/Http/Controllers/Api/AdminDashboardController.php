<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Payment;
use App\Models\Story;
use App\Models\StoryComment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $data = [
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
        ];

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats loaded successfully.',
            'data' => $data,
        ]);
    }

    public function charts(): JsonResponse
    {
        $series = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();
            return [
                'date' => $date,
                'new_users' => User::whereDate('created_at', $date)->count(),
                'revenue' => (int) Payment::where('status', 'completed')
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard chart data loaded successfully.',
            'data' => [
                'daily' => $series,
            ],
        ]);
    }
}

