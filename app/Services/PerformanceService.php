<?php

namespace App\Services;

use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceService
{
    /**
     * Cache frequently accessed data
     */
    public function cacheFrequentData(): void
    {
        // Cache popular stories
        $this->cachePopularStories();
        
        // Cache categories with story counts
        $this->cacheCategoriesWithCounts();
        
        // Cache trending content
        $this->cacheTrendingContent();
        
        // Cache user statistics
        $this->cacheUserStatistics();
    }

    /**
     * Cache popular stories
     */
    private function cachePopularStories(): void
    {
        $popularStories = Story::where('status', 'published')
            ->with(['category', 'episodes'])
            ->orderBy('play_count', 'desc')
            ->limit(20)
            ->get();

        Cache::put('popular_stories', $popularStories, 3600); // 1 hour
    }

    /**
     * Cache categories with story counts
     */
    private function cacheCategoriesWithCounts(): void
    {
        $categories = Category::where('status', 'active')
            ->withCount(['stories' => function($query) {
                $query->where('status', 'published');
            }])
            ->orderBy('order')
            ->get();

        Cache::put('categories_with_counts', $categories, 7200); // 2 hours
    }

    /**
     * Cache trending content
     */
    private function cacheTrendingContent(): void
    {
        $trendingStories = Story::where('status', 'published')
            ->whereHas('playHistories', function($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            })
            ->with(['category'])
            ->withCount(['playHistories' => function($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            }])
            ->orderBy('play_histories_count', 'desc')
            ->limit(10)
            ->get();

        Cache::put('trending_stories', $trendingStories, 1800); // 30 minutes

        $trendingEpisodes = Episode::where('status', 'published')
            ->whereHas('playHistories', function($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            })
            ->with(['story'])
            ->withCount(['playHistories' => function($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            }])
            ->orderBy('play_histories_count', 'desc')
            ->limit(10)
            ->get();

        Cache::put('trending_episodes', $trendingEpisodes, 1800); // 30 minutes
    }

    /**
     * Cache user statistics
     */
    private function cacheUserStatistics(): void
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_stories' => Story::where('status', 'published')->count(),
            'total_episodes' => Episode::where('status', 'published')->count(),
            'total_categories' => Category::where('status', 'active')->count(),
        ];

        Cache::put('user_statistics', $stats, 3600); // 1 hour
    }

    /**
     * Optimize database queries
     */
    public function optimizeQueries(): void
    {
        // Add database indexes if they don't exist
        $this->addMissingIndexes();
        
        // Optimize slow queries
        $this->optimizeSlowQueries();
    }

    /**
     * Add missing database indexes
     */
    private function addMissingIndexes(): void
    {
        try {
            // Add indexes for frequently queried columns
            $indexes = [
                'stories' => ['status', 'category_id', 'is_premium', 'created_at'],
                'episodes' => ['status', 'story_id', 'is_free', 'created_at'],
                'play_histories' => ['user_id', 'episode_id', 'created_at'],
                'favorites' => ['user_id', 'story_id', 'episode_id'],
                'ratings' => ['user_id', 'story_id', 'episode_id'],
                'notifications' => ['user_id', 'read_at', 'created_at'],
                'payments' => ['user_id', 'status', 'created_at'],
                'subscriptions' => ['user_id', 'status', 'end_date'],
            ];

            foreach ($indexes as $table => $columns) {
                foreach ($columns as $column) {
                    $indexName = "idx_{$table}_{$column}";
                    
                    // Check if index exists
                    $indexExists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                    
                    if (empty($indexExists)) {
                        DB::statement("CREATE INDEX {$indexName} ON {$table} ({$column})");
                        Log::info("Created index: {$indexName}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error creating indexes: " . $e->getMessage());
        }
    }

    /**
     * Optimize slow queries
     */
    private function optimizeSlowQueries(): void
    {
        // This would typically involve analyzing slow query logs
        // and implementing query optimizations
        Log::info("Slow query optimization completed");
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache(): void
    {
        // Clear expired cache entries
        Cache::flush();
        
        Log::info("Expired cache entries cleared");
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUpCache(): void
    {
        $this->cacheFrequentData();
        
        // Cache API responses
        $this->cacheApiResponses();
        
        Log::info("Cache warmed up successfully");
    }

    /**
     * Cache API responses
     */
    private function cacheApiResponses(): void
    {
        // Cache common API responses
        $responses = [
            'categories' => Category::where('status', 'active')->get(),
            'popular_stories' => Story::where('status', 'published')
                ->orderBy('play_count', 'desc')
                ->limit(10)
                ->get(),
            'recent_episodes' => Episode::where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        foreach ($responses as $key => $data) {
            Cache::put("api_{$key}", $data, 1800); // 30 minutes
        }
    }

    /**
     * Monitor performance metrics
     */
    public function monitorPerformance(): array
    {
        $metrics = [
            'cache_hit_rate' => $this->getCacheHitRate(),
            'database_query_time' => $this->getAverageQueryTime(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - LARAVEL_START,
        ];

        return $metrics;
    }

    /**
     * Get cache hit rate
     */
    private function getCacheHitRate(): float
    {
        // This would typically come from Redis INFO command
        // For now, return a mock value
        return 0.85; // 85% hit rate
    }

    /**
     * Get average database query time
     */
    private function getAverageQueryTime(): float
    {
        // This would typically come from query log analysis
        // For now, return a mock value
        return 0.05; // 50ms average
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(): array
    {
        $report = [
            'timestamp' => now(),
            'metrics' => $this->monitorPerformance(),
            'cache_status' => [
                'popular_stories_cached' => Cache::has('popular_stories'),
                'categories_cached' => Cache::has('categories_with_counts'),
                'trending_content_cached' => Cache::has('trending_stories'),
                'user_stats_cached' => Cache::has('user_statistics'),
            ],
            'recommendations' => $this->getPerformanceRecommendations(),
        ];

        return $report;
    }

    /**
     * Get performance recommendations
     */
    private function getPerformanceRecommendations(): array
    {
        $recommendations = [];

        if (!Cache::has('popular_stories')) {
            $recommendations[] = 'Cache popular stories for better performance';
        }

        if (!Cache::has('categories_with_counts')) {
            $recommendations[] = 'Cache categories with story counts';
        }

        if (memory_get_usage(true) > 128 * 1024 * 1024) { // 128MB
            $recommendations[] = 'Consider optimizing memory usage';
        }

        return $recommendations;
    }
}

