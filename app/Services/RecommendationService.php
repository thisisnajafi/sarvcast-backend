<?php

namespace App\Services;

use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    /**
     * Get personalized recommendations for a user
     */
    public function getPersonalizedRecommendations(int $userId, int $limit = 10): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        // Get user preferences and behavior
        $userPreferences = $this->getUserPreferences($userId);
        $userBehavior = $this->getUserBehavior($userId);

        // Combine different recommendation strategies
        $recommendations = [];

        // 1. Collaborative Filtering
        $collaborativeRecs = $this->getCollaborativeFilteringRecommendations($userId, $limit);
        $recommendations = array_merge($recommendations, $collaborativeRecs);

        // 2. Content-Based Filtering
        $contentBasedRecs = $this->getContentBasedRecommendations($userId, $limit);
        $recommendations = array_merge($recommendations, $contentBasedRecs);

        // 3. Trending Content
        $trendingRecs = $this->getTrendingRecommendations($userId, $limit);
        $recommendations = array_merge($recommendations, $trendingRecs);

        // 4. Similar Users
        $similarUsersRecs = $this->getSimilarUsersRecommendations($userId, $limit);
        $recommendations = array_merge($recommendations, $similarUsersRecs);

        // 5. Category-based recommendations
        $categoryRecs = $this->getCategoryBasedRecommendations($userId, $limit);
        $recommendations = array_merge($recommendations, $categoryRecs);

        // Remove duplicates and already consumed content
        $recommendations = $this->deduplicateRecommendations($recommendations);
        $recommendations = $this->filterConsumedContent($recommendations, $userId);

        // Score and rank recommendations
        $recommendations = $this->scoreRecommendations($recommendations, $userPreferences, $userBehavior);

        // Sort by score and limit results
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Get collaborative filtering recommendations
     */
    public function getCollaborativeFilteringRecommendations(int $userId, int $limit = 10): array
    {
        return Cache::remember("collaborative_recs_{$userId}_{$limit}", 3600, function() use ($userId, $limit) {
            // Find users with similar preferences
            $similarUsers = $this->findSimilarUsers($userId, 20);

            if (empty($similarUsers)) {
                return [];
            }

            $similarUserIds = array_column($similarUsers, 'user_id');

            // Get content liked by similar users but not by current user
            $recommendations = DB::table('favorites')
                ->join('stories', 'favorites.story_id', '=', 'stories.id')
                ->whereIn('favorites.user_id', $similarUserIds)
                ->whereNotIn('favorites.story_id', function($query) use ($userId) {
                    $query->select('story_id')
                        ->from('favorites')
                        ->where('user_id', $userId);
                })
                ->whereNotIn('favorites.story_id', function($query) use ($userId) {
                    $query->select('story_id')
                        ->from('play_histories')
                        ->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                        ->where('play_histories.user_id', $userId);
                })
                ->where('stories.status', 'published')
                ->select('stories.*', DB::raw('COUNT(*) as recommendation_score'))
                ->groupBy('stories.id')
                ->orderBy('recommendation_score', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();

            return array_map(function($item) {
                return [
                    'type' => 'collaborative',
                    'story_id' => $item->id,
                    'story' => $item,
                    'score' => $item->recommendation_score,
                    'reason' => 'مشابه کاربران دیگر'
                ];
            }, $recommendations);
        });
    }

    /**
     * Get content-based recommendations
     */
    public function getContentBasedRecommendations(int $userId, int $limit = 10): array
    {
        return Cache::remember("content_based_recs_{$userId}_{$limit}", 3600, function() use ($userId, $limit) {
            // Get user's favorite stories
            $userFavorites = Favorite::where('user_id', $userId)
                ->with('story')
                ->get();

            if ($userFavorites->isEmpty()) {
                return [];
            }

            $recommendations = [];

            foreach ($userFavorites as $favorite) {
                $story = $favorite->story;
                
                // Find similar stories based on content features
                $similarStories = $this->findSimilarStories($story->id, $limit);
                
                foreach ($similarStories as $similarStory) {
                    $recommendations[] = [
                        'type' => 'content_based',
                        'story_id' => $similarStory['id'],
                        'story' => $similarStory,
                        'score' => $similarStory['similarity_score'],
                        'reason' => 'مشابه داستان‌های مورد علاقه شما'
                    ];
                }
            }

            return $recommendations;
        });
    }

    /**
     * Get trending recommendations
     */
    public function getTrendingRecommendations(int $userId, int $limit = 10): array
    {
        return Cache::remember("trending_recs_{$userId}_{$limit}", 1800, function() use ($userId, $limit) {
            // Get trending stories based on recent activity
            $trendingStories = Story::where('status', 'published')
                ->whereNotIn('id', function($query) use ($userId) {
                    $query->select('story_id')
                        ->from('favorites')
                        ->where('user_id', $userId);
                })
                ->whereNotIn('id', function($query) use ($userId) {
                    $query->select('story_id')
                        ->from('play_histories')
                        ->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                        ->where('play_histories.user_id', $userId);
                })
                ->withCount(['playHistories as recent_plays' => function($query) {
                    $query->where('created_at', '>=', now()->subDays(7));
                }])
                ->withCount(['favorites as recent_favorites' => function($query) {
                    $query->where('created_at', '>=', now()->subDays(7));
                }])
                ->orderBy('recent_plays', 'desc')
                ->orderBy('recent_favorites', 'desc')
                ->limit($limit)
                ->get();

            return $trendingStories->map(function($story) {
                return [
                    'type' => 'trending',
                    'story_id' => $story->id,
                    'story' => $story,
                    'score' => $story->recent_plays + $story->recent_favorites,
                    'reason' => 'محتوای محبوب این روزها'
                ];
            })->toArray();
        });
    }

    /**
     * Get recommendations based on similar users
     */
    public function getSimilarUsersRecommendations(int $userId, int $limit = 10): array
    {
        return Cache::remember("similar_users_recs_{$userId}_{$limit}", 3600, function() use ($userId, $limit) {
            $similarUsers = $this->findSimilarUsers($userId, 10);

            if (empty($similarUsers)) {
                return [];
            }

            $recommendations = [];

            foreach ($similarUsers as $similarUser) {
                // Get highly rated content by similar users
                $highlyRatedContent = Rating::where('user_id', $similarUser['user_id'])
                    ->where('rating', '>=', 4)
                    ->whereNotIn('story_id', function($query) use ($userId) {
                        $query->select('story_id')
                            ->from('ratings')
                            ->where('user_id', $userId);
                    })
                    ->whereHas('story', function($query) {
                        $query->where('status', 'published');
                    })
                    ->with('story')
                    ->limit(5)
                    ->get();

                foreach ($highlyRatedContent as $rating) {
                    $recommendations[] = [
                        'type' => 'similar_users',
                        'story_id' => $rating->story_id,
                        'story' => $rating->story,
                        'score' => $rating->rating * $similarUser['similarity_score'],
                        'reason' => 'مورد علاقه کاربران مشابه'
                    ];
                }
            }

            return $recommendations;
        });
    }

    /**
     * Get category-based recommendations
     */
    public function getCategoryBasedRecommendations(int $userId, int $limit = 10): array
    {
        return Cache::remember("category_based_recs_{$userId}_{$limit}", 3600, function() use ($userId, $limit) {
            // Get user's preferred categories
            $userCategories = $this->getUserPreferredCategories($userId);

            if (empty($userCategories)) {
                return [];
            }

            $categoryIds = array_column($userCategories, 'category_id');

            // Get popular stories from user's preferred categories
            $recommendations = Story::whereIn('category_id', $categoryIds)
                ->where('status', 'published')
                ->whereNotIn('id', function($query) use ($userId) {
                    $query->select('story_id')
                        ->from('favorites')
                        ->where('user_id', $userId);
                })
                ->whereNotIn('id', function($query) use ($userId) {
                    $query->select('story_id')
                        ->from('play_histories')
                        ->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                        ->where('play_histories.user_id', $userId);
                })
                ->withCount(['favorites', 'ratings'])
                ->withAvg('ratings', 'rating')
                ->orderBy('favorites_count', 'desc')
                ->orderBy('ratings_avg_rating', 'desc')
                ->limit($limit)
                ->get();

            return $recommendations->map(function($story) {
                return [
                    'type' => 'category_based',
                    'story_id' => $story->id,
                    'story' => $story,
                    'score' => $story->favorites_count + ($story->ratings_avg_rating * 10),
                    'reason' => 'از دسته‌بندی مورد علاقه شما'
                ];
            })->toArray();
        });
    }

    /**
     * Find similar users based on behavior
     */
    public function findSimilarUsers(int $userId, int $limit = 10): array
    {
        return Cache::remember("similar_users_{$userId}_{$limit}", 3600, function() use ($userId, $limit) {
            // Get user's favorite stories
            $userFavorites = Favorite::where('user_id', $userId)
                ->pluck('story_id')
                ->toArray();

            if (empty($userFavorites)) {
                return [];
            }

            // Find users who have similar favorites
            $similarUsers = DB::table('favorites')
                ->where('user_id', '!=', $userId)
                ->whereIn('story_id', $userFavorites)
                ->select('user_id', DB::raw('COUNT(*) as common_favorites'))
                ->groupBy('user_id')
                ->having('common_favorites', '>', 0)
                ->orderBy('common_favorites', 'desc')
                ->limit($limit)
                ->get();

            // Calculate similarity scores
            $totalUserFavorites = count($userFavorites);
            
            return $similarUsers->map(function($user) use ($totalUserFavorites) {
                $similarityScore = $user->common_favorites / $totalUserFavorites;
                return [
                    'user_id' => $user->user_id,
                    'similarity_score' => $similarityScore,
                    'common_favorites' => $user->common_favorites
                ];
            })->toArray();
        });
    }

    /**
     * Find similar stories based on content features
     */
    public function findSimilarStories(int $storyId, int $limit = 10): array
    {
        $story = Story::find($storyId);
        if (!$story) {
            return [];
        }

        // Find stories with similar categories, tags, and other features
        $similarStories = Story::where('id', '!=', $storyId)
            ->where('status', 'published')
            ->where(function($query) use ($story) {
                $query->where('category_id', $story->category_id)
                    ->orWhere('director_id', $story->director_id)
                    ->orWhere('narrator_id', $story->narrator_id);
            })
            ->withCount(['favorites', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->orderBy('favorites_count', 'desc')
            ->orderBy('ratings_avg_rating', 'desc')
            ->limit($limit)
            ->get();

        return $similarStories->map(function($similarStory) use ($story) {
            $similarityScore = 0;
            
            // Category similarity
            if ($similarStory->category_id === $story->category_id) {
                $similarityScore += 0.4;
            }
            
            // Director similarity
            if ($similarStory->director_id === $story->director_id) {
                $similarityScore += 0.3;
            }
            
            // Narrator similarity
            if ($similarStory->narrator_id === $story->narrator_id) {
                $similarityScore += 0.3;
            }

            return [
                'id' => $similarStory->id,
                'title' => $similarStory->title,
                'category_id' => $similarStory->category_id,
                'director_id' => $similarStory->director_id,
                'narrator_id' => $similarStory->narrator_id,
                'similarity_score' => $similarityScore,
                'favorites_count' => $similarStory->favorites_count,
                'ratings_avg_rating' => $similarStory->ratings_avg_rating
            ];
        })->toArray();
    }

    /**
     * Get user preferences
     */
    public function getUserPreferences(int $userId): array
    {
        return Cache::remember("user_preferences_{$userId}", 3600, function() use ($userId) {
            $preferences = [];

            // Preferred categories
            $preferredCategories = DB::table('favorites')
                ->join('stories', 'favorites.story_id', '=', 'stories.id')
                ->where('favorites.user_id', $userId)
                ->select('stories.category_id', DB::raw('COUNT(*) as count'))
                ->groupBy('stories.category_id')
                ->orderBy('count', 'desc')
                ->get();

            $preferences['categories'] = $preferredCategories->pluck('category_id')->toArray();

            // Preferred directors
            $preferredDirectors = DB::table('favorites')
                ->join('stories', 'favorites.story_id', '=', 'stories.id')
                ->where('favorites.user_id', $userId)
                ->select('stories.director_id', DB::raw('COUNT(*) as count'))
                ->groupBy('stories.director_id')
                ->orderBy('count', 'desc')
                ->get();

            $preferences['directors'] = $preferredDirectors->pluck('director_id')->toArray();

            // Preferred narrators
            $preferredNarrators = DB::table('favorites')
                ->join('stories', 'favorites.story_id', '=', 'stories.id')
                ->where('favorites.user_id', $userId)
                ->select('stories.narrator_id', DB::raw('COUNT(*) as count'))
                ->groupBy('stories.narrator_id')
                ->orderBy('count', 'desc')
                ->get();

            $preferences['narrators'] = $preferredNarrators->pluck('narrator_id')->toArray();

            return $preferences;
        });
    }

    /**
     * Get user behavior patterns
     */
    public function getUserBehavior(int $userId): array
    {
        return Cache::remember("user_behavior_{$userId}", 3600, function() use ($userId) {
            $behavior = [];

            // Play time patterns
            $playTimePatterns = PlayHistory::where('user_id', $userId)
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('count', 'desc')
                ->get();

            $behavior['preferred_hours'] = $playTimePatterns->pluck('hour')->toArray();

            // Content completion rate
            $totalPlays = PlayHistory::where('user_id', $userId)->count();
            $completedPlays = PlayHistory::where('user_id', $userId)
                ->where('progress', '>=', 90)
                ->count();

            $behavior['completion_rate'] = $totalPlays > 0 ? $completedPlays / $totalPlays : 0;

            // Average session duration
            $behavior['avg_session_duration'] = PlayHistory::where('user_id', $userId)
                ->avg('duration') ?? 0;

            return $behavior;
        });
    }

    /**
     * Get user's preferred categories
     */
    public function getUserPreferredCategories(int $userId): array
    {
        return DB::table('favorites')
            ->join('stories', 'favorites.story_id', '=', 'stories.id')
            ->where('favorites.user_id', $userId)
            ->select('stories.category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('stories.category_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Remove duplicate recommendations
     */
    private function deduplicateRecommendations(array $recommendations): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($recommendations as $recommendation) {
            $key = $recommendation['story_id'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $deduplicated[] = $recommendation;
            }
        }

        return $deduplicated;
    }

    /**
     * Filter out content already consumed by user
     */
    private function filterConsumedContent(array $recommendations, int $userId): array
    {
        $consumedStoryIds = DB::table('favorites')
            ->where('user_id', $userId)
            ->pluck('story_id')
            ->toArray();

        $consumedStoryIds = array_merge($consumedStoryIds, 
            DB::table('play_histories')
                ->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                ->where('play_histories.user_id', $userId)
                ->pluck('episodes.story_id')
                ->toArray()
        );

        return array_filter($recommendations, function($recommendation) use ($consumedStoryIds) {
            return !in_array($recommendation['story_id'], $consumedStoryIds);
        });
    }

    /**
     * Score recommendations based on user preferences and behavior
     */
    private function scoreRecommendations(array $recommendations, array $userPreferences, array $userBehavior): array
    {
        foreach ($recommendations as &$recommendation) {
            $score = $recommendation['score'];
            
            // Boost score for preferred categories
            if (in_array($recommendation['story']->category_id, $userPreferences['categories'] ?? [])) {
                $score *= 1.2;
            }
            
            // Boost score for preferred directors
            if (in_array($recommendation['story']->director_id, $userPreferences['directors'] ?? [])) {
                $score *= 1.1;
            }
            
            // Boost score for preferred narrators
            if (in_array($recommendation['story']->narrator_id, $userPreferences['narrators'] ?? [])) {
                $score *= 1.1;
            }
            
            // Boost score for high-rated content
            if ($recommendation['story']->avg_rating > 4) {
                $score *= 1.15;
            }
            
            // Boost score for popular content
            if ($recommendation['story']->total_favorites > 100) {
                $score *= 1.05;
            }

            $recommendation['score'] = $score;
        }

        return $recommendations;
    }

    /**
     * Get recommendations for new users (cold start problem)
     */
    public function getNewUserRecommendations(int $limit = 10): array
    {
        return Cache::remember("new_user_recommendations_{$limit}", 1800, function() use ($limit) {
            // Get popular and highly-rated content
            $recommendations = Story::where('status', 'published')
                ->withCount(['favorites', 'ratings'])
                ->withAvg('ratings', 'rating')
                ->having('ratings_count', '>=', 10) // Minimum ratings threshold
                ->orderBy('ratings_avg_rating', 'desc')
                ->orderBy('favorites_count', 'desc')
                ->limit($limit)
                ->get();

            return $recommendations->map(function($story) {
                return [
                    'type' => 'new_user',
                    'story_id' => $story->id,
                    'story' => $story,
                    'score' => $story->ratings_avg_rating * $story->favorites_count,
                    'reason' => 'محتوای محبوب و باکیفیت'
                ];
            })->toArray();
        });
    }

    /**
     * Get trending content recommendations
     */
    public function getTrendingContent(int $limit = 10): array
    {
        return Cache::remember("trending_content_{$limit}", 1800, function() use ($limit) {
            $trendingStories = Story::where('status', 'published')
                ->withCount(['playHistories as recent_plays' => function($query) {
                    $query->where('created_at', '>=', now()->subDays(7));
                }])
                ->withCount(['favorites as recent_favorites' => function($query) {
                    $query->where('created_at', '>=', now()->subDays(7));
                }])
                ->orderBy('recent_plays', 'desc')
                ->orderBy('recent_favorites', 'desc')
                ->limit($limit)
                ->get();

            return $trendingStories->map(function($story) {
                return [
                    'type' => 'trending',
                    'story_id' => $story->id,
                    'story' => $story,
                    'score' => $story->recent_plays + $story->recent_favorites,
                    'reason' => 'محتوای محبوب این روزها'
                ];
            })->toArray();
        });
    }

    /**
     * Get similar content recommendations
     */
    public function getSimilarContent(int $storyId, int $limit = 10): array
    {
        return Cache::remember("similar_content_{$storyId}_{$limit}", 3600, function() use ($storyId, $limit) {
            $similarStories = $this->findSimilarStories($storyId, $limit);
            
            return array_map(function($story) {
                return [
                    'type' => 'similar',
                    'story_id' => $story['id'],
                    'story' => $story,
                    'score' => $story['similarity_score'],
                    'reason' => 'مشابه این محتوا'
                ];
            }, $similarStories);
        });
    }

    /**
     * Clear recommendation cache for a user
     */
    public function clearUserRecommendationCache(int $userId): void
    {
        $patterns = [
            "collaborative_recs_{$userId}_*",
            "content_based_recs_{$userId}_*",
            "trending_recs_{$userId}_*",
            "similar_users_recs_{$userId}_*",
            "category_based_recs_{$userId}_*",
            "similar_users_{$userId}_*",
            "user_preferences_{$userId}",
            "user_behavior_{$userId}"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Update recommendation model (for future ML integration)
     */
    public function updateRecommendationModel(): void
    {
        // This would typically trigger a background job to retrain the recommendation model
        // For now, we'll just clear all recommendation caches
        Cache::flush();
    }
}
