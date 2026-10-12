<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Favorite;
use App\Models\PlayHistory;
use App\Models\Rating;
use App\Models\Story;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardListeningAnalyticsService
{
    private const CACHE_TTL_SECONDS = 60;

    public function getStats(): array
    {
        return Cache::remember('admin_dashboard_listening_stats_v1', self::CACHE_TTL_SECONDS, function () {
            return $this->computeStats();
        });
    }

    public function getChartPayload(): array
    {
        return Cache::remember('admin_dashboard_listening_charts_v1', self::CACHE_TTL_SECONDS, function () {
            return $this->computeChartPayload();
        });
    }

    /**
     * Listening totals for a fixed set of users. An empty list is nobody, not the whole app.
     *
     * @param  list<int>  $userIds
     */
    public function computeStatsForUsers(array $userIds): array
    {
        return $this->computeStats($userIds);
    }

    /**
     * Chart series for a fixed set of users. An empty list is nobody, not the whole app.
     *
     * @param  list<int>  $userIds
     */
    public function computeChartPayloadForUsers(array $userIds, int $days = 7): array
    {
        return $this->computeChartPayload($days, $userIds);
    }

    public function computeStats(?array $userIds = null): array
    {
        $today = now()->toDateString();
        $weekStart = now()->copy()->startOfWeek();
        $weekEnd = now()->copy()->endOfWeek();
        $monthStart = now()->copy()->startOfMonth();

        $listensToday = $this->countPlays(fn ($q) => $q->whereDate('played_at', $today), $userIds);
        $listensThisWeek = $this->countPlays(fn ($q) => $q->whereBetween('played_at', [$weekStart, $weekEnd]), $userIds);
        $listensThisMonth = $this->countPlays(fn ($q) => $q->where('played_at', '>=', $monthStart), $userIds);

        $totalListens = PlayHistory::query();
        $this->limitToUsers($totalListens, $userIds);
        $totalFavorites = Favorite::query();
        $this->limitToUsers($totalFavorites, $userIds);
        $favoritesToday = Favorite::query()->whereDate('created_at', $today);
        $this->limitToUsers($favoritesToday, $userIds);
        $favoritesThisWeek = Favorite::query()->whereBetween('created_at', [$weekStart, $weekEnd]);
        $this->limitToUsers($favoritesThisWeek, $userIds);

        return [
            'listens_today' => $listensToday,
            'listens_this_week' => $listensThisWeek,
            'listens_this_month' => $listensThisMonth,
            'total_listens' => (int) $totalListens->count(),
            'unique_listeners_today' => $this->countDistinctColumn('user_id', fn ($q) => $q->whereDate('played_at', $today), $userIds),
            'unique_listeners_this_week' => $this->countDistinctColumn('user_id', fn ($q) => $q->whereBetween('played_at', [$weekStart, $weekEnd]), $userIds),
            'unique_stories_today' => $this->countDistinctColumn('story_id', fn ($q) => $q->whereDate('played_at', $today), $userIds),
            'unique_stories_this_week' => $this->countDistinctColumn('story_id', fn ($q) => $q->whereBetween('played_at', [$weekStart, $weekEnd]), $userIds),
            'completed_listens_today' => $this->countPlays(fn ($q) => $q->whereDate('played_at', $today)->completed(), $userIds),
            'completed_listens_this_week' => $this->countPlays(fn ($q) => $q->whereBetween('played_at', [$weekStart, $weekEnd])->completed(), $userIds),
            'total_favorites' => (int) $totalFavorites->count(),
            'favorites_today' => (int) $favoritesToday->count(),
            'favorites_this_week' => (int) $favoritesThisWeek->count(),
        ];
    }

    public function computeChartPayload(int $days = 7, ?array $userIds = null): array
    {
        $weekStart = now()->copy()->startOfWeek();
        $weekEnd = now()->copy()->endOfWeek();

        return [
            'daily' => $this->dailySeries($days, $userIds),
            'top_stories_today' => $this->topStoriesForPeriod(now()->copy()->startOfDay(), now()->copy()->endOfDay(), 8, $userIds),
            'top_stories_this_week' => $this->topStoriesForPeriod($weekStart, $weekEnd, 8, $userIds),
            'most_favorited_stories' => $this->mostFavoritedStories(5, $userIds),
            'most_favorited_episodes' => $this->mostFavoritedEpisodes(5, $userIds),
        ];
    }

    /**
     * @return list<array{date: string, listens: int, unique_listeners: int, unique_stories: int, favorites: int}>
     */
    public function dailySeries(int $days = 7, ?array $userIds = null): array
    {
        $days = max(1, min(90, $days));
        $from = now()->copy()->subDays($days - 1)->startOfDay();
        $to = now()->copy()->endOfDay();

        $plays = PlayHistory::query()
            ->whereBetween('played_at', [$from, $to]);
        $this->limitToUsers($plays, $userIds);
        $plays = $plays
            ->selectRaw('DATE(played_at) as play_date, COUNT(*) as listens, COUNT(DISTINCT user_id) as unique_listeners, COUNT(DISTINCT story_id) as unique_stories')
            ->groupByRaw('DATE(played_at)')
            ->get()
            ->keyBy('play_date');

        $favorites = Favorite::query()
            ->whereBetween('created_at', [$from, $to]);
        $this->limitToUsers($favorites, $userIds);
        $favorites = $favorites
            ->selectRaw('DATE(created_at) as fav_date, COUNT(*) as favorites')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('fav_date');

        return collect(range($days - 1, 0))->map(function (int $daysAgo) use ($plays, $favorites) {
            $date = now()->copy()->subDays($daysAgo)->toDateString();
            $playRow = $plays->get($date);
            $favRow = $favorites->get($date);

            return [
                'date' => $date,
                'listens' => (int) ($playRow->listens ?? 0),
                'unique_listeners' => (int) ($playRow->unique_listeners ?? 0),
                'unique_stories' => (int) ($playRow->unique_stories ?? 0),
                'favorites' => (int) ($favRow->favorites ?? 0),
            ];
        })->values()->all();
    }

    public function topStoriesForPeriod(Carbon $from, Carbon $to, int $limit = 8, ?array $userIds = null): array
    {
        $rows = PlayHistory::query()
            ->whereBetween('played_at', [$from, $to]);
        $this->limitToUsers($rows, $userIds);
        $rows = $rows
            ->selectRaw('story_id, COUNT(*) as listen_count, COUNT(DISTINCT user_id) as unique_listeners, SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_listens')
            ->groupBy('story_id')
            ->orderByDesc('listen_count')
            ->limit($limit)
            ->get();

        return $this->mapRankedStories($rows, $userIds);
    }

    public function mostFavoritedStories(int $limit = 5, ?array $userIds = null): array
    {
        $rows = Favorite::query();
        $this->limitToUsers($rows, $userIds);
        $rows = $rows
            ->selectRaw('story_id, COUNT(*) as favorite_count')
            ->groupBy('story_id')
            ->orderByDesc('favorite_count')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $listenRows = PlayHistory::query()
            ->whereIn('story_id', $rows->pluck('story_id')->all());
        $this->limitToUsers($listenRows, $userIds);
        $listenRows = $listenRows
            ->selectRaw('story_id, COUNT(*) as listen_count, COUNT(DISTINCT user_id) as unique_listeners, SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_listens')
            ->groupBy('story_id')
            ->get()
            ->keyBy('story_id');

        return $this->mapRankedStories(
            $rows->map(function ($row) use ($listenRows) {
                $listens = $listenRows->get($row->story_id);
                $row->listen_count = (int) ($listens->listen_count ?? 0);
                $row->unique_listeners = (int) ($listens->unique_listeners ?? 0);
                $row->completed_listens = (int) ($listens->completed_listens ?? 0);

                return $row;
            }),
            $userIds,
        );
    }

    /**
     * Favorites are stored per story, not per episode. Rank episodes by plays
     * from users who favorited the parent story; fall back to overall plays.
     */
    public function mostFavoritedEpisodes(int $limit = 5, ?array $userIds = null): array
    {
        $favoritedRows = DB::table('play_histories as ph')
            ->join('favorites as f', function ($join) {
                $join->on('f.user_id', '=', 'ph.user_id')
                    ->on('f.story_id', '=', 'ph.story_id');
            });
        $this->limitToUsers($favoritedRows, $userIds, 'ph.user_id');
        $favoritedRows = $favoritedRows
            ->selectRaw('ph.episode_id as episode_id, COUNT(*) as listen_count, COUNT(DISTINCT ph.user_id) as unique_listeners')
            ->groupBy('ph.episode_id')
            ->orderByDesc('listen_count')
            ->limit($limit)
            ->get();

        $source = $favoritedRows->isNotEmpty() ? 'favorited_listeners' : 'all_plays';

        if ($favoritedRows->isNotEmpty()) {
            $rows = $favoritedRows;
        } else {
            $rows = PlayHistory::query();
            $this->limitToUsers($rows, $userIds);
            $rows = $rows
                ->selectRaw('episode_id, COUNT(*) as listen_count, COUNT(DISTINCT user_id) as unique_listeners')
                ->groupBy('episode_id')
                ->orderByDesc('listen_count')
                ->limit($limit)
                ->get();
        }

        return $this->mapRankedEpisodes($rows, $source, $userIds);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapRankedStories(Collection $rows, ?array $userIds = null): array
    {
        $ids = $rows->pluck('story_id')->filter()->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        $storiesQuery = Story::query()
            ->with(['category:id,name'])
            ->whereIn('id', $ids);
        if ($userIds === null) {
            $storiesQuery->withCount('favorites');
        }
        $stories = $storiesQuery->get()->keyBy('id');

        $scopedFavorites = null;
        if ($userIds !== null) {
            $favoriteQuery = Favorite::query()->whereIn('story_id', $ids);
            $this->limitToUsers($favoriteQuery, $userIds);
            $scopedFavorites = $favoriteQuery
                ->selectRaw('story_id, COUNT(*) as favorite_count')
                ->groupBy('story_id')
                ->pluck('favorite_count', 'story_id');
        }

        return $rows->map(function ($row) use ($stories, $scopedFavorites) {
            $story = $stories->get($row->story_id);
            if (! $story) {
                return null;
            }

            $favoritesCount = isset($row->favorite_count)
                ? (int) $row->favorite_count
                : ($scopedFavorites !== null
                    ? (int) ($scopedFavorites[$story->id] ?? 0)
                    : (int) ($story->favorites_count ?? 0));

            return [
                'id' => $story->id,
                'title' => $story->title,
                'image_url' => $story->image_url,
                'status' => $story->status,
                'category' => $story->category?->name,
                'category_id' => $story->category_id,
                'listen_count' => (int) ($row->listen_count ?? 0),
                'unique_listeners' => (int) ($row->unique_listeners ?? 0),
                'completed_listens' => (int) ($row->completed_listens ?? 0),
                'favorites_count' => $favoritesCount,
            ];
        })->filter()->values()->all();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapRankedEpisodes(Collection $rows, string $source, ?array $userIds = null): array
    {
        $ids = $rows->pluck('episode_id')->filter()->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        $episodes = Episode::query()
            ->with(['story:id,title,image_url,category_id', 'story.category:id,name'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ratings = Rating::query()
            ->whereNotNull('episode_id')
            ->whereIn('episode_id', $ids);
        $this->limitToUsers($ratings, $userIds);
        $ratings = $ratings
            ->selectRaw('episode_id, COUNT(*) as rating_count, AVG(rating) as avg_rating')
            ->groupBy('episode_id')
            ->get()
            ->keyBy('episode_id');

        $totalPlays = PlayHistory::query()
            ->whereIn('episode_id', $ids);
        $this->limitToUsers($totalPlays, $userIds);
        $totalPlays = $totalPlays
            ->selectRaw('episode_id, COUNT(*) as listen_count, COUNT(DISTINCT user_id) as unique_listeners, SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_listens')
            ->groupBy('episode_id')
            ->get()
            ->keyBy('episode_id');

        return $rows->map(function ($row) use ($episodes, $ratings, $totalPlays, $source) {
            $episode = $episodes->get($row->episode_id);
            if (! $episode) {
                return null;
            }

            $story = $episode->story;
            $rating = $ratings->get($episode->id);
            $plays = $totalPlays->get($episode->id);
            $imageUrls = is_array($episode->image_urls) ? $episode->image_urls : [];

            return [
                'id' => $episode->id,
                'title' => $episode->title,
                'episode_number' => (int) $episode->episode_number,
                'duration' => (int) $episode->duration,
                'status' => $episode->status,
                'image_url' => $imageUrls[0] ?? $story?->image_url,
                'story_id' => $episode->story_id,
                'story_title' => $story?->title,
                'category' => $story?->category?->name,
                'listen_count' => (int) ($plays->listen_count ?? $row->listen_count ?? 0),
                'unique_listeners' => (int) ($plays->unique_listeners ?? $row->unique_listeners ?? 0),
                'completed_listens' => (int) ($plays->completed_listens ?? 0),
                'favorited_listener_plays' => $source === 'favorited_listeners' ? (int) $row->listen_count : 0,
                'rating_count' => (int) ($rating->rating_count ?? 0),
                'avg_rating' => round((float) ($rating->avg_rating ?? 0), 2),
                'source' => $source,
            ];
        })->filter()->values()->all();
    }

    private function countPlays(callable $scope, ?array $userIds = null): int
    {
        $query = PlayHistory::query();
        $this->limitToUsers($query, $userIds);
        $scope($query);

        return (int) $query->count();
    }

    private function countDistinctColumn(string $column, callable $scope, ?array $userIds = null): int
    {
        $allowed = ['user_id', 'story_id', 'episode_id'];
        if (! in_array($column, $allowed, true)) {
            return 0;
        }

        $query = PlayHistory::query();
        $this->limitToUsers($query, $userIds);
        $scope($query);

        return (int) $query->selectRaw("COUNT(DISTINCT {$column}) as aggregate")->value('aggregate');
    }

    /**
     * null keeps the super-admin queries unscoped. An array, including an empty one, limits the rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  list<int>|null  $userIds
     */
    private function limitToUsers($query, ?array $userIds, string $column = 'user_id'): void
    {
        if ($userIds === null) {
            return;
        }

        $query->whereIn($column, array_values(array_unique(array_map(static fn ($id): int => (int) $id, $userIds))));
    }
}
