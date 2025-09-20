<?php

namespace App\Services;

use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\Person;
use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContentAnalyticsService
{
    /**
     * Get comprehensive content analytics
     */
    public function getContentAnalytics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();
        $contentType = $filters['content_type'] ?? null; // story, episode
        $categoryId = $filters['category_id'] ?? null;

        return [
            'overview' => $this->getOverviewMetrics($dateFrom, $dateTo, $contentType, $categoryId),
            'performance' => $this->getPerformanceMetrics($dateFrom, $dateTo, $contentType, $categoryId),
            'popularity' => $this->getPopularityMetrics($dateFrom, $dateTo, $contentType, $categoryId),
            'engagement' => $this->getEngagementMetrics($dateFrom, $dateTo, $contentType, $categoryId),
            'trends' => $this->getTrendMetrics($dateFrom, $dateTo, $contentType, $categoryId),
            'categories' => $this->getCategoryMetrics($dateFrom, $dateTo),
            'creators' => $this->getCreatorMetrics($dateFrom, $dateTo),
            'quality' => $this->getQualityMetrics($dateFrom, $dateTo, $contentType, $categoryId),
            'discovery' => $this->getDiscoveryMetrics($dateFrom, $dateTo, $contentType, $categoryId),
            'monetization' => $this->getMonetizationMetrics($dateFrom, $dateTo, $contentType, $categoryId)
        ];
    }

    /**
     * Get overview metrics
     */
    public function getOverviewMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        $storyQuery = Story::query();
        $episodeQuery = Episode::query();

        if ($categoryId) {
            $storyQuery->where('category_id', $categoryId);
            $episodeQuery->whereHas('story', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $totalStories = $storyQuery->count();
        $totalEpisodes = $episodeQuery->count();
        $publishedStories = $storyQuery->where('status', 'published')->count();
        $publishedEpisodes = $episodeQuery->where('status', 'published')->count();

        // New content in period
        $newStories = $storyQuery->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $newEpisodes = $episodeQuery->whereBetween('created_at', [$dateFrom, $dateTo])->count();

        // Total play time
        $totalPlayTime = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->whereHas('episode.story');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->sum('duration');

        // Total plays
        $totalPlays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->whereHas('episode.story');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->count();

        // Total favorites
        $totalFavorites = Favorite::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->count();

        // Total ratings
        $totalRatings = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->whereNotNull('story_id');
            })
            ->when($contentType === 'episode', function($q) {
                $q->whereNotNull('episode_id');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where(function($ratingQ) use ($categoryId) {
                    $ratingQ->whereHas('story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    })->orWhereHas('episode.story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    });
                });
            })
            ->count();

        // Average rating
        $avgRating = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->whereNotNull('story_id');
            })
            ->when($contentType === 'episode', function($q) {
                $q->whereNotNull('episode_id');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where(function($ratingQ) use ($categoryId) {
                    $ratingQ->whereHas('story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    })->orWhereHas('episode.story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    });
                });
            })
            ->avg('rating');

        return [
            'total_stories' => $totalStories,
            'total_episodes' => $totalEpisodes,
            'published_stories' => $publishedStories,
            'published_episodes' => $publishedEpisodes,
            'new_stories' => $newStories,
            'new_episodes' => $newEpisodes,
            'total_play_time' => $totalPlayTime,
            'total_plays' => $totalPlays,
            'total_favorites' => $totalFavorites,
            'total_ratings' => $totalRatings,
            'avg_rating' => round($avgRating ?? 0, 2),
            'publish_rate' => $totalStories > 0 ? round(($publishedStories / $totalStories) * 100, 2) : 0
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Most played content
        $mostPlayed = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->join('stories', 'episodes.story_id', '=', 'stories.id')
                  ->selectRaw('stories.id, stories.title, COUNT(*) as play_count, SUM(play_histories.duration) as total_duration')
                  ->groupBy('stories.id', 'stories.title');
            })
            ->when($contentType === 'episode', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->selectRaw('episodes.id, episodes.title, COUNT(*) as play_count, SUM(play_histories.duration) as total_duration')
                  ->groupBy('episodes.id', 'episodes.title');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->orderBy('play_count', 'desc')
            ->limit(10)
            ->get();

        // Most favorited content
        $mostFavorited = Favorite::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->selectRaw('story_id, COUNT(*) as favorite_count')
            ->groupBy('story_id')
            ->orderBy('favorite_count', 'desc')
            ->limit(10)
            ->with('story')
            ->get();

        // Highest rated content
        $highestRated = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->whereNotNull('story_id')
                  ->selectRaw('story_id, AVG(rating) as avg_rating, COUNT(*) as rating_count')
                  ->groupBy('story_id');
            })
            ->when($contentType === 'episode', function($q) {
                $q->whereNotNull('episode_id')
                  ->selectRaw('episode_id, AVG(rating) as avg_rating, COUNT(*) as rating_count')
                  ->groupBy('episode_id');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where(function($ratingQ) use ($categoryId) {
                    $ratingQ->whereHas('story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    })->orWhereHas('episode.story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    });
                });
            })
            ->having('rating_count', '>=', 5) // Minimum 5 ratings
            ->orderBy('avg_rating', 'desc')
            ->limit(10)
            ->get();

        // Content completion rates
        $completionRates = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->join('stories', 'episodes.story_id', '=', 'stories.id')
                  ->selectRaw('stories.id, stories.title, 
                              COUNT(*) as total_plays,
                              SUM(CASE WHEN play_histories.progress >= 90 THEN 1 ELSE 0 END) as completed_plays')
                  ->groupBy('stories.id', 'stories.title');
            })
            ->when($contentType === 'episode', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->selectRaw('episodes.id, episodes.title, 
                              COUNT(*) as total_plays,
                              SUM(CASE WHEN play_histories.progress >= 90 THEN 1 ELSE 0 END) as completed_plays')
                  ->groupBy('episodes.id', 'episodes.title');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->having('total_plays', '>=', 10) // Minimum 10 plays
            ->get()
            ->map(function($item) {
                $item->completion_rate = $item->total_plays > 0 ? 
                    round(($item->completed_plays / $item->total_plays) * 100, 2) : 0;
                return $item;
            })
            ->sortByDesc('completion_rate')
            ->take(10);

        return [
            'most_played' => $mostPlayed,
            'most_favorited' => $mostFavorited,
            'highest_rated' => $highestRated,
            'completion_rates' => $completionRates,
            'avg_completion_rate' => $completionRates->avg('completion_rate') ?? 0
        ];
    }

    /**
     * Get popularity metrics
     */
    public function getPopularityMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Trending content (most played in recent days)
        $trendingContent = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->join('stories', 'episodes.story_id', '=', 'stories.id')
                  ->selectRaw('stories.id, stories.title, COUNT(*) as recent_plays')
                  ->groupBy('stories.id', 'stories.title');
            })
            ->when($contentType === 'episode', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->selectRaw('episodes.id, episodes.title, COUNT(*) as recent_plays')
                  ->groupBy('episodes.id', 'episodes.title');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->orderBy('recent_plays', 'desc')
            ->limit(20)
            ->get();

        // Viral content (rapidly growing popularity)
        $viralContent = $this->getViralContent($dateFrom, $dateTo, $contentType, $categoryId);

        // Popular by age group
        $popularByAgeGroup = $this->getPopularByAgeGroup($dateFrom, $dateTo, $contentType, $categoryId);

        // Popular by time of day
        $popularByTime = PlayHistory::whereBetween('played_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->join('stories', 'episodes.story_id', '=', 'stories.id')
                  ->selectRaw('stories.id, stories.title, HOUR(play_histories.played_at) as hour, COUNT(*) as plays')
                  ->groupBy('stories.id', 'stories.title', 'hour');
            })
            ->when($contentType === 'episode', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->selectRaw('episodes.id, episodes.title, HOUR(play_histories.played_at) as hour, COUNT(*) as plays')
                  ->groupBy('episodes.id', 'episodes.title', 'hour');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->get()
            ->groupBy('hour')
            ->map(function($hourData) {
                return $hourData->sortByDesc('plays')->take(5);
            });

        return [
            'trending_content' => $trendingContent,
            'viral_content' => $viralContent,
            'popular_by_age_group' => $popularByAgeGroup,
            'popular_by_time' => $popularByTime
        ];
    }

    /**
     * Get engagement metrics
     */
    public function getEngagementMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Average session duration by content
        $avgSessionDuration = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->join('stories', 'episodes.story_id', '=', 'stories.id')
                  ->selectRaw('stories.id, stories.title, AVG(play_histories.duration) as avg_duration')
                  ->groupBy('stories.id', 'stories.title');
            })
            ->when($contentType === 'episode', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->selectRaw('episodes.id, episodes.title, AVG(play_histories.duration) as avg_duration')
                  ->groupBy('episodes.id', 'episodes.title');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->orderBy('avg_duration', 'desc')
            ->limit(10)
            ->get();

        // User retention by content
        $retentionByContent = $this->getRetentionByContent($dateFrom, $dateTo, $contentType, $categoryId);

        // Repeat listening rate
        $repeatListeningRate = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->join('stories', 'episodes.story_id', '=', 'stories.id')
                  ->selectRaw('stories.id, stories.title, 
                              COUNT(DISTINCT user_id) as unique_users,
                              COUNT(*) as total_plays')
                  ->groupBy('stories.id', 'stories.title');
            })
            ->when($contentType === 'episode', function($q) {
                $q->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                  ->selectRaw('episodes.id, episodes.title, 
                              COUNT(DISTINCT user_id) as unique_users,
                              COUNT(*) as total_plays')
                  ->groupBy('episodes.id', 'episodes.title');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->get()
            ->map(function($item) {
                $item->repeat_rate = $item->unique_users > 0 ? 
                    round(($item->total_plays / $item->unique_users), 2) : 0;
                return $item;
            })
            ->sortByDesc('repeat_rate')
            ->take(10);

        return [
            'avg_session_duration' => $avgSessionDuration,
            'retention_by_content' => $retentionByContent,
            'repeat_listening_rate' => $repeatListeningRate,
            'avg_repeat_rate' => $repeatListeningRate->avg('repeat_rate') ?? 0
        ];
    }

    /**
     * Get trend metrics
     */
    public function getTrendMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Daily play trends
        $dailyPlayTrends = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->selectRaw('DATE(created_at) as date, COUNT(*) as plays, SUM(duration) as total_duration')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Weekly trends
        $weeklyTrends = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->selectRaw('YEARWEEK(created_at) as week, COUNT(*) as plays')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Monthly trends
        $monthlyTrends = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('episode.story', function($storyQ) use ($categoryId) {
                    $storyQ->where('category_id', $categoryId);
                });
            })
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as plays')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Growth rates
        $playGrowthRate = $this->calculateGrowthRate($dailyPlayTrends);
        $durationGrowthRate = $this->calculateGrowthRate($dailyPlayTrends->map(function($item) {
            return (object)['count' => $item->total_duration];
        }));

        return [
            'daily_trends' => $dailyPlayTrends,
            'weekly_trends' => $weeklyTrends,
            'monthly_trends' => $monthlyTrends,
            'play_growth_rate' => $playGrowthRate,
            'duration_growth_rate' => $durationGrowthRate
        ];
    }

    /**
     * Get category metrics
     */
    public function getCategoryMetrics($dateFrom, $dateTo): array
    {
        // Category performance
        $categoryPerformance = Category::withCount(['stories', 'episodes'])
            ->withSum('stories', 'play_count')
            ->get()
            ->map(function($category) use ($dateFrom, $dateTo) {
                $plays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereHas('episode.story', function($q) use ($category) {
                        $q->where('category_id', $category->id);
                    })
                    ->count();

                $favorites = Favorite::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereHas('story', function($q) use ($category) {
                        $q->where('category_id', $category->id);
                    })
                    ->count();

                $ratings = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where(function($q) use ($category) {
                        $q->whereHas('story', function($storyQ) use ($category) {
                            $storyQ->where('category_id', $category->id);
                        })->orWhereHas('episode.story', function($storyQ) use ($category) {
                            $storyQ->where('category_id', $category->id);
                        });
                    })
                    ->count();

                $avgRating = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where(function($q) use ($category) {
                        $q->whereHas('story', function($storyQ) use ($category) {
                            $storyQ->where('category_id', $category->id);
                        })->orWhereHas('episode.story', function($storyQ) use ($category) {
                            $storyQ->where('category_id', $category->id);
                        });
                    })
                    ->avg('rating');

                $category->plays = $plays;
                $category->favorites = $favorites;
                $category->ratings = $ratings;
                $category->avg_rating = round($avgRating ?? 0, 2);

                return $category;
            })
            ->sortByDesc('plays');

        return [
            'category_performance' => $categoryPerformance,
            'top_category' => $categoryPerformance->first(),
            'total_categories' => $categoryPerformance->count()
        ];
    }

    /**
     * Get creator metrics
     */
    public function getCreatorMetrics($dateFrom, $dateTo): array
    {
        // Director performance
        $directorPerformance = Person::whereJsonContains('roles', 'director')
            ->withCount(['stories', 'episodes'])
            ->get()
            ->map(function($director) use ($dateFrom, $dateTo) {
                $plays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereHas('episode.story', function($q) use ($director) {
                        $q->where('director_id', $director->id);
                    })
                    ->count();

                $avgRating = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where(function($q) use ($director) {
                        $q->whereHas('story', function($storyQ) use ($director) {
                            $storyQ->where('director_id', $director->id);
                        })->orWhereHas('episode.story', function($storyQ) use ($director) {
                            $storyQ->where('director_id', $director->id);
                        });
                    })
                    ->avg('rating');

                $director->plays = $plays;
                $director->avg_rating = round($avgRating ?? 0, 2);

                return $director;
            })
            ->sortByDesc('plays');

        // Narrator performance
        $narratorPerformance = Person::whereJsonContains('roles', 'narrator')
            ->withCount(['stories', 'episodes'])
            ->get()
            ->map(function($narrator) use ($dateFrom, $dateTo) {
                $plays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereHas('episode', function($q) use ($narrator) {
                        $q->where('narrator_id', $narrator->id);
                    })
                    ->count();

                $avgRating = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where(function($q) use ($narrator) {
                        $q->whereHas('story', function($storyQ) use ($narrator) {
                            $storyQ->where('narrator_id', $narrator->id);
                        })->orWhereHas('episode', function($episodeQ) use ($narrator) {
                            $episodeQ->where('narrator_id', $narrator->id);
                        });
                    })
                    ->avg('rating');

                $narrator->plays = $plays;
                $narrator->avg_rating = round($avgRating ?? 0, 2);

                return $narrator;
            })
            ->sortByDesc('plays');

        return [
            'director_performance' => $directorPerformance,
            'narrator_performance' => $narratorPerformance,
            'top_director' => $directorPerformance->first(),
            'top_narrator' => $narratorPerformance->first()
        ];
    }

    /**
     * Get quality metrics
     */
    public function getQualityMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Content with highest ratings
        $highestRatedContent = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->whereNotNull('story_id')
                  ->selectRaw('story_id, AVG(rating) as avg_rating, COUNT(*) as rating_count')
                  ->groupBy('story_id');
            })
            ->when($contentType === 'episode', function($q) {
                $q->whereNotNull('episode_id')
                  ->selectRaw('episode_id, AVG(rating) as avg_rating, COUNT(*) as rating_count')
                  ->groupBy('episode_id');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where(function($ratingQ) use ($categoryId) {
                    $ratingQ->whereHas('story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    })->orWhereHas('episode.story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    });
                });
            })
            ->having('rating_count', '>=', 10) // Minimum 10 ratings
            ->orderBy('avg_rating', 'desc')
            ->limit(20)
            ->get();

        // Content quality distribution
        $qualityDistribution = Rating::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($contentType === 'story', function($q) {
                $q->whereNotNull('story_id');
            })
            ->when($contentType === 'episode', function($q) {
                $q->whereNotNull('episode_id');
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where(function($ratingQ) use ($categoryId) {
                    $ratingQ->whereHas('story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    })->orWhereHas('episode.story', function($storyQ) use ($categoryId) {
                        $storyQ->where('category_id', $categoryId);
                    });
                });
            })
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->get();

        return [
            'highest_rated_content' => $highestRatedContent,
            'quality_distribution' => $qualityDistribution,
            'avg_quality_score' => $highestRatedContent->avg('avg_rating') ?? 0
        ];
    }

    /**
     * Get discovery metrics
     */
    public function getDiscoveryMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Most searched content
        $mostSearched = $this->getMostSearchedContent($dateFrom, $dateTo, $contentType, $categoryId);

        // Content discovery rate
        $discoveryRate = $this->getContentDiscoveryRate($dateFrom, $dateTo, $contentType, $categoryId);

        // New content performance
        $newContentPerformance = $this->getNewContentPerformance($dateFrom, $dateTo, $contentType, $categoryId);

        return [
            'most_searched' => $mostSearched,
            'discovery_rate' => $discoveryRate,
            'new_content_performance' => $newContentPerformance
        ];
    }

    /**
     * Get monetization metrics
     */
    public function getMonetizationMetrics($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Premium content performance
        $premiumContentPerformance = Story::where('is_premium', true)
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->withCount(['episodes'])
            ->get()
            ->map(function($story) use ($dateFrom, $dateTo) {
                $plays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereHas('episode', function($q) use ($story) {
                        $q->where('story_id', $story->id);
                    })
                    ->count();

                $favorites = Favorite::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('story_id', $story->id)
                    ->count();

                $story->plays = $plays;
                $story->favorites = $favorites;

                return $story;
            })
            ->sortByDesc('plays');

        // Free content performance
        $freeContentPerformance = Story::where('is_completely_free', true)
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->withCount(['episodes'])
            ->get()
            ->map(function($story) use ($dateFrom, $dateTo) {
                $plays = PlayHistory::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereHas('episode', function($q) use ($story) {
                        $q->where('story_id', $story->id);
                    })
                    ->count();

                $favorites = Favorite::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('story_id', $story->id)
                    ->count();

                $story->plays = $plays;
                $story->favorites = $favorites;

                return $story;
            })
            ->sortByDesc('plays');

        return [
            'premium_content_performance' => $premiumContentPerformance,
            'free_content_performance' => $freeContentPerformance,
            'premium_vs_free_ratio' => $premiumContentPerformance->count() > 0 ? 
                round($freeContentPerformance->count() / $premiumContentPerformance->count(), 2) : 0
        ];
    }

    /**
     * Helper methods
     */
    private function getViralContent($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Implementation for viral content detection
        // This would analyze rapid growth in popularity
        return [];
    }

    private function getPopularByAgeGroup($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Implementation for popularity by age group
        return [];
    }

    private function getRetentionByContent($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Implementation for retention analysis by content
        return [];
    }

    private function getMostSearchedContent($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Implementation for search analytics
        return [];
    }

    private function getContentDiscoveryRate($dateFrom, $dateTo, $contentType = null, $categoryId = null): float
    {
        // Implementation for discovery rate calculation
        return 0;
    }

    private function getNewContentPerformance($dateFrom, $dateTo, $contentType = null, $categoryId = null): array
    {
        // Implementation for new content performance
        return [];
    }

    private function calculateGrowthRate($data): float
    {
        if ($data->count() < 2) {
            return 0;
        }

        $firstValue = $data->first()->count ?? $data->first()->plays ?? 0;
        $lastValue = $data->last()->count ?? $data->last()->plays ?? 0;

        if ($firstValue == 0) {
            return $lastValue > 0 ? 100 : 0;
        }

        return round((($lastValue - $firstValue) / $firstValue) * 100, 2);
    }
}
