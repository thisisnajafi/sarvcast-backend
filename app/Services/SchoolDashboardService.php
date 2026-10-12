<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\StoryComment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SchoolDashboardService
{
    public const ONLINE_WINDOW_MINUTES = 15;

    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly DashboardListeningAnalyticsService $listening,
    ) {}

    public static function statsCacheKey(int $profileId): string
    {
        return "school_dashboard_{$profileId}_stats_v1";
    }

    public static function chartsCacheKey(int $profileId): string
    {
        return "school_dashboard_{$profileId}_charts_v1";
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(SchoolManagerProfile $profile): array
    {
        return Cache::remember(self::statsCacheKey($profile->id), self::CACHE_TTL_SECONDS, function () use ($profile) {
            return $this->buildStats($profile);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function charts(SchoolManagerProfile $profile): array
    {
        return Cache::remember(self::chartsCacheKey($profile->id), self::CACHE_TTL_SECONDS, function () use ($profile) {
            return $this->buildCharts($profile);
        });
    }

    public function onlineCount(SchoolManagerProfile $profile): int
    {
        $userIds = $profile->activeMemberIds();
        if ($userIds === []) {
            return 0;
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('last_activity_at', '>=', now()->subMinutes(self::ONLINE_WINDOW_MINUTES))
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStats(SchoolManagerProfile $profile): array
    {
        $userIds = $profile->activeMemberIds();

        return array_merge([
            'total_users' => count($userIds),
            'active_users' => $this->countActiveUsers($userIds),
            'active_subscriptions' => $this->countActiveSubscriptions($userIds),
            'pending_comments' => $this->countPendingComments($userIds),
            'monthly_revenue' => $this->monthlyRevenue($userIds),
        ], $this->listening->computeStatsForUsers($userIds));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCharts(SchoolManagerProfile $profile): array
    {
        $userIds = $profile->activeMemberIds();
        $listening = $this->listening->computeChartPayloadForUsers($userIds);
        $listeningByDate = collect($listening['daily'] ?? [])->keyBy('date');

        $series = collect(range(6, 0))->map(function (int $daysAgo) use ($listeningByDate, $profile, $userIds) {
            $date = now()->subDays($daysAgo)->toDateString();
            $point = $listeningByDate->get($date, []);

            return [
                'date' => $date,
                'new_users' => $this->membershipsAttachedOn($profile, $date),
                'revenue' => $this->revenueOn($userIds, $date),
                'listens' => (int) ($point['listens'] ?? 0),
                'unique_listeners' => (int) ($point['unique_listeners'] ?? 0),
                'unique_stories' => (int) ($point['unique_stories'] ?? 0),
                'favorites' => (int) ($point['favorites'] ?? 0),
            ];
        })->values();

        $activeUsers = $this->countActiveUsers($userIds);
        $totalUsers = count($userIds);

        return [
            'daily' => $series,
            'top_stories_today' => $listening['top_stories_today'] ?? [],
            'top_stories_this_week' => $listening['top_stories_this_week'] ?? [],
            'most_favorited_stories' => $listening['most_favorited_stories'] ?? [],
            'most_favorited_episodes' => $listening['most_favorited_episodes'] ?? [],
            'breakdown' => [
                'users' => [
                    'active' => $activeUsers,
                    'inactive' => max(0, $totalUsers - $activeUsers),
                ],
                'subscriptions' => $this->subscriptionsByStatus($userIds),
            ],
        ];
    }

    /**
     * @param  list<int>  $userIds
     */
    private function countActiveUsers(array $userIds): int
    {
        if ($userIds === []) {
            return 0;
        }

        return User::query()->whereIn('id', $userIds)->where('status', 'active')->count();
    }

    /**
     * @param  list<int>  $userIds
     */
    private function countActiveSubscriptions(array $userIds): int
    {
        if ($userIds === []) {
            return 0;
        }

        return Subscription::query()->whereIn('user_id', $userIds)->where('status', 'active')->count();
    }

    /**
     * @param  list<int>  $userIds
     */
    private function countPendingComments(array $userIds): int
    {
        if ($userIds === []) {
            return 0;
        }

        return StoryComment::query()->whereIn('user_id', $userIds)->where('is_approved', false)->count();
    }

    /**
     * @param  list<int>  $userIds
     */
    private function monthlyRevenue(array $userIds): int
    {
        if ($userIds === []) {
            return 0;
        }

        return (int) Payment::query()
            ->where('status', 'completed')
            ->whereIn('user_id', $userIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * @param  list<int>  $userIds
     */
    private function revenueOn(array $userIds, string $date): int
    {
        if ($userIds === []) {
            return 0;
        }

        return (int) Payment::query()
            ->where('status', 'completed')
            ->whereIn('user_id', $userIds)
            ->whereDate('created_at', $date)
            ->sum('amount');
    }

    private function membershipsAttachedOn(SchoolManagerProfile $profile, string $date): int
    {
        return SchoolMembership::query()
            ->where('school_manager_profile_id', $profile->id)
            ->whereDate('created_at', $date)
            ->count();
    }

    /**
     * @param  list<int>  $userIds
     * @return array<string, int>
     */
    private function subscriptionsByStatus(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return Subscription::query()
            ->whereIn('user_id', $userIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
