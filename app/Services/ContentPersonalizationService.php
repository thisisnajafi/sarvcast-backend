<?php

namespace App\Services;

use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\UserRecommendationPreference;
use App\Models\RecommendationInteraction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContentPersonalizationService
{
    /**
     * Learn user preferences from interactions
     */
    public function learnUserPreferences(int $userId): void
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return;
            }

            // Get user interactions
            $interactions = $this->getUserInteractions($userId);
            
            // Analyze interactions and update preferences
            $this->updateCategoryPreferences($userId, $interactions);
            $this->updateDirectorPreferences($userId, $interactions);
            $this->updateNarratorPreferences($userId, $interactions);
            $this->updateContentPreferences($userId, $interactions);
            $this->updateTimePreferences($userId, $interactions);
            $this->updateDurationPreferences($userId, $interactions);

            // Clear user preference cache
            $this->clearUserPreferenceCache($userId);

            Log::info("User preferences learned for user {$userId}");

        } catch (\Exception $e) {
            Log::error("Error learning user preferences for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Get personalized content feed for user
     */
    public function getPersonalizedFeed(int $userId, int $limit = 20): array
    {
        return Cache::remember("personalized_feed_{$userId}_{$limit}", 1800, function() use ($userId, $limit) {
            $userPreferences = $this->getUserPreferences($userId);
            $userBehavior = $this->getUserBehavior($userId);

            // Get personalized content based on preferences
            $feed = [];

            // 1. Highly preferred content
            $preferredContent = $this->getPreferredContent($userId, $userPreferences, $limit / 4);
            $feed = array_merge($feed, $preferredContent);

            // 2. Trending content in preferred categories
            $trendingPreferred = $this->getTrendingInPreferredCategories($userId, $userPreferences, $limit / 4);
            $feed = array_merge($feed, $trendingPreferred);

            // 3. New content from preferred creators
            $newFromPreferred = $this->getNewFromPreferredCreators($userId, $userPreferences, $limit / 4);
            $feed = array_merge($feed, $newFromPreferred);

            // 4. Similar to recently enjoyed content
            $similarToRecent = $this->getSimilarToRecentContent($userId, $limit / 4);
            $feed = array_merge($feed, $similarToRecent);

            // Remove duplicates and filter consumed content
            $feed = $this->deduplicateFeed($feed);
            $feed = $this->filterConsumedContent($feed, $userId);

            // Personalize based on user behavior
            $feed = $this->personalizeBasedOnBehavior($feed, $userBehavior);

            // Sort by personalization score
            usort($feed, function($a, $b) {
                return $b['personalization_score'] <=> $a['personalization_score'];
            });

            return array_slice($feed, 0, $limit);
        });
    }

    /**
     * Get personalized search results
     */
    public function getPersonalizedSearchResults(int $userId, string $query, int $limit = 20): array
    {
        return Cache::remember("personalized_search_{$userId}_" . md5($query) . "_{$limit}", 900, function() use ($userId, $query, $limit) {
            $userPreferences = $this->getUserPreferences($userId);

            // Get search results
            $searchResults = Story::where('status', 'published')
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('tags', 'like', "%{$query}%");
                })
                ->with(['category', 'director', 'narrator'])
                ->withCount(['favorites', 'ratings'])
                ->withAvg('ratings', 'rating')
                ->limit($limit * 2) // Get more results for personalization
                ->get();

            // Personalize search results
            $personalizedResults = [];
            foreach ($searchResults as $story) {
                $personalizationScore = $this->calculatePersonalizationScore($story, $userPreferences);
                
                $personalizedResults[] = [
                    'story' => $story,
                    'personalization_score' => $personalizationScore,
                    'relevance_score' => $this->calculateRelevanceScore($story, $query),
                    'combined_score' => $personalizationScore + $this->calculateRelevanceScore($story, $query)
                ];
            }

            // Sort by combined score
            usort($personalizedResults, function($a, $b) {
                return $b['combined_score'] <=> $a['combined_score'];
            });

            return array_slice($personalizedResults, 0, $limit);
        });
    }

    /**
     * Get personalized category recommendations
     */
    public function getPersonalizedCategoryRecommendations(int $userId, int $categoryId, int $limit = 10): array
    {
        return Cache::remember("personalized_category_{$userId}_{$categoryId}_{$limit}", 1800, function() use ($userId, $categoryId, $limit) {
            $userPreferences = $this->getUserPreferences($userId);

            $stories = Story::where('status', 'published')
                ->where('category_id', $categoryId)
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
                ->with(['category', 'director', 'narrator'])
                ->withCount(['favorites', 'ratings'])
                ->withAvg('ratings', 'rating')
                ->limit($limit * 2)
                ->get();

            $personalizedStories = [];
            foreach ($stories as $story) {
                $personalizationScore = $this->calculatePersonalizationScore($story, $userPreferences);
                
                $personalizedStories[] = [
                    'story' => $story,
                    'personalization_score' => $personalizationScore
                ];
            }

            // Sort by personalization score
            usort($personalizedStories, function($a, $b) {
                return $b['personalization_score'] <=> $a['personalization_score'];
            });

            return array_slice($personalizedStories, 0, $limit);
        });
    }

    /**
     * Update user preferences based on interaction
     */
    public function updatePreferencesFromInteraction(int $userId, int $storyId, string $interactionType): void
    {
        try {
            $story = Story::find($storyId);
            if (!$story) {
                return;
            }

            // Update category preference
            $this->updatePreference($userId, 'category', $story->category_id, $interactionType);
            
            // Update director preference
            if ($story->director_id) {
                $this->updatePreference($userId, 'director', $story->director_id, $interactionType);
            }
            
            // Update narrator preference
            if ($story->narrator_id) {
                $this->updatePreference($userId, 'narrator', $story->narrator_id, $interactionType);
            }

            // Update content length preference
            $episodeCount = $story->episodes()->count();
            $this->updatePreference($userId, 'episode_count', $episodeCount, $interactionType);

            // Update content age preference
            $contentAge = now()->diffInDays($story->created_at);
            $ageGroup = $this->getAgeGroup($contentAge);
            $this->updatePreference($userId, 'content_age', $ageGroup, $interactionType);

            Log::info("Preferences updated for user {$userId} based on interaction {$interactionType} with story {$storyId}");

        } catch (\Exception $e) {
            Log::error("Error updating preferences for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Get user's personalized dashboard data
     */
    public function getPersonalizedDashboard(int $userId): array
    {
        return Cache::remember("personalized_dashboard_{$userId}", 1800, function() use ($userId) {
            $userPreferences = $this->getUserPreferences($userId);
            $userBehavior = $this->getUserBehavior($userId);

            return [
                'recommended_stories' => $this->getPersonalizedFeed($userId, 10),
                'trending_in_preferences' => $this->getTrendingInPreferredCategories($userId, $userPreferences, 5),
                'new_from_preferred_creators' => $this->getNewFromPreferredCreators($userId, $userPreferences, 5),
                'continue_listening' => $this->getContinueListening($userId, 5),
                'recently_favorited' => $this->getRecentlyFavorited($userId, 5),
                'preferred_categories' => $this->getPreferredCategories($userId),
                'preferred_creators' => $this->getPreferredCreators($userId),
                'listening_patterns' => $this->getListeningPatterns($userId),
                'content_preferences' => $this->getContentPreferences($userId)
            ];
        });
    }

    /**
     * Get user preferences
     */
    public function getUserPreferences(int $userId): array
    {
        return Cache::remember("user_preferences_{$userId}", 3600, function() use ($userId) {
            $preferences = UserRecommendationPreference::where('user_id', $userId)
                ->get()
                ->groupBy('preference_type')
                ->map(function($group) {
                    return $group->mapWithKeys(function($pref) {
                        return [$pref->preference_value => $pref->weight];
                    });
                })
                ->toArray();

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

            // Listening time patterns
            $timePatterns = PlayHistory::where('user_id', $userId)
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('count', 'desc')
                ->get();

            $behavior['preferred_hours'] = $timePatterns->pluck('hour')->toArray();

            // Session duration patterns
            $sessionDurations = PlayHistory::where('user_id', $userId)
                ->selectRaw('AVG(duration) as avg_duration, COUNT(*) as session_count')
                ->first();

            $behavior['avg_session_duration'] = $sessionDurations->avg_duration ?? 0;
            $behavior['total_sessions'] = $sessionDurations->session_count ?? 0;

            // Content completion rate
            $totalPlays = PlayHistory::where('user_id', $userId)->count();
            $completedPlays = PlayHistory::where('user_id', $userId)
                ->where('progress', '>=', 90)
                ->count();

            $behavior['completion_rate'] = $totalPlays > 0 ? $completedPlays / $totalPlays : 0;

            // Preferred content length
            $episodeLengths = DB::table('play_histories')
                ->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                ->where('play_histories.user_id', $userId)
                ->selectRaw('AVG(episodes.duration) as avg_length')
                ->first();

            $behavior['preferred_episode_length'] = $episodeLengths->avg_length ?? 0;

            return $behavior;
        });
    }

    /**
     * Get user interactions for learning
     */
    private function getUserInteractions(int $userId): array
    {
        $interactions = [];

        // Favorites
        $favorites = Favorite::where('user_id', $userId)
            ->with('story')
            ->get();

        foreach ($favorites as $favorite) {
            $interactions[] = [
                'type' => 'favorite',
                'story_id' => $favorite->story_id,
                'story' => $favorite->story,
                'weight' => 1.0
            ];
        }

        // Play history
        $playHistory = PlayHistory::where('user_id', $userId)
            ->with(['episode.story'])
            ->get();

        foreach ($playHistory as $play) {
            $weight = $play->progress / 100; // Weight based on completion
            $interactions[] = [
                'type' => 'play',
                'story_id' => $play->episode->story_id,
                'story' => $play->episode->story,
                'weight' => $weight
            ];
        }

        // Ratings
        $ratings = Rating::where('user_id', $userId)
            ->with('story')
            ->get();

        foreach ($ratings as $rating) {
            $weight = $rating->rating / 5; // Normalize rating to 0-1
            $interactions[] = [
                'type' => 'rating',
                'story_id' => $rating->story_id,
                'story' => $rating->story,
                'weight' => $weight
            ];
        }

        return $interactions;
    }

    /**
     * Update category preferences
     */
    private function updateCategoryPreferences(int $userId, array $interactions): void
    {
        $categoryWeights = [];
        
        foreach ($interactions as $interaction) {
            $categoryId = $interaction['story']->category_id;
            $weight = $interaction['weight'];
            
            if (!isset($categoryWeights[$categoryId])) {
                $categoryWeights[$categoryId] = 0;
            }
            
            $categoryWeights[$categoryId] += $weight;
        }

        foreach ($categoryWeights as $categoryId => $weight) {
            $this->updatePreference($userId, 'category', $categoryId, 'interaction', $weight);
        }
    }

    /**
     * Update director preferences
     */
    private function updateDirectorPreferences(int $userId, array $interactions): void
    {
        $directorWeights = [];
        
        foreach ($interactions as $interaction) {
            $directorId = $interaction['story']->director_id;
            if (!$directorId) continue;
            
            $weight = $interaction['weight'];
            
            if (!isset($directorWeights[$directorId])) {
                $directorWeights[$directorId] = 0;
            }
            
            $directorWeights[$directorId] += $weight;
        }

        foreach ($directorWeights as $directorId => $weight) {
            $this->updatePreference($userId, 'director', $directorId, 'interaction', $weight);
        }
    }

    /**
     * Update narrator preferences
     */
    private function updateNarratorPreferences(int $userId, array $interactions): void
    {
        $narratorWeights = [];
        
        foreach ($interactions as $interaction) {
            $narratorId = $interaction['story']->narrator_id;
            if (!$narratorId) continue;
            
            $weight = $interaction['weight'];
            
            if (!isset($narratorWeights[$narratorId])) {
                $narratorWeights[$narratorId] = 0;
            }
            
            $narratorWeights[$narratorId] += $weight;
        }

        foreach ($narratorWeights as $narratorId => $weight) {
            $this->updatePreference($userId, 'narrator', $narratorId, 'interaction', $weight);
        }
    }

    /**
     * Update content preferences
     */
    private function updateContentPreferences(int $userId, array $interactions): void
    {
        $contentWeights = [];
        
        foreach ($interactions as $interaction) {
            $story = $interaction['story'];
            $weight = $interaction['weight'];
            
            // Episode count preference
            $episodeCount = $story->episodes()->count();
            $episodeCountGroup = $this->getEpisodeCountGroup($episodeCount);
            
            if (!isset($contentWeights['episode_count'][$episodeCountGroup])) {
                $contentWeights['episode_count'][$episodeCountGroup] = 0;
            }
            $contentWeights['episode_count'][$episodeCountGroup] += $weight;
            
            // Content age preference
            $contentAge = now()->diffInDays($story->created_at);
            $ageGroup = $this->getAgeGroup($contentAge);
            
            if (!isset($contentWeights['content_age'][$ageGroup])) {
                $contentWeights['content_age'][$ageGroup] = 0;
            }
            $contentWeights['content_age'][$ageGroup] += $weight;
        }

        foreach ($contentWeights as $type => $weights) {
            foreach ($weights as $value => $weight) {
                $this->updatePreference($userId, $type, $value, 'interaction', $weight);
            }
        }
    }

    /**
     * Update time preferences
     */
    private function updateTimePreferences(int $userId, array $interactions): void
    {
        $timeWeights = [];
        
        foreach ($interactions as $interaction) {
            // This would need to be implemented with actual interaction timestamps
            // For now, we'll use a simplified approach
        }
    }

    /**
     * Update duration preferences
     */
    private function updateDurationPreferences(int $userId, array $interactions): void
    {
        $durationWeights = [];
        
        foreach ($interactions as $interaction) {
            // This would need episode duration data
            // For now, we'll use a simplified approach
        }
    }

    /**
     * Update a specific preference
     */
    private function updatePreference(int $userId, string $type, string $value, string $interactionType, float $weight = null): void
    {
        $preference = UserRecommendationPreference::firstOrCreate([
            'user_id' => $userId,
            'preference_type' => $type,
            'preference_value' => $value
        ]);

        if ($weight === null) {
            $weight = $this->getInteractionWeight($interactionType);
        }

        $preference->weight = max(0, min(1, $preference->weight + $weight));
        $preference->interaction_count++;
        $preference->last_updated = now();
        $preference->save();
    }

    /**
     * Get interaction weight
     */
    private function getInteractionWeight(string $interactionType): float
    {
        return match($interactionType) {
            'favorite' => 0.1,
            'play' => 0.05,
            'rating_high' => 0.15,
            'rating_low' => -0.1,
            'dismiss' => -0.05,
            'interaction' => 0.01,
            default => 0.01
        };
    }

    /**
     * Get preferred content
     */
    private function getPreferredContent(int $userId, array $userPreferences, int $limit): array
    {
        $preferredCategories = $userPreferences['category'] ?? [];
        $preferredDirectors = $userPreferences['director'] ?? [];
        $preferredNarrators = $userPreferences['narrator'] ?? [];

        if (empty($preferredCategories) && empty($preferredDirectors) && empty($preferredNarrators)) {
            return [];
        }

        $stories = Story::where('status', 'published')
            ->where(function($query) use ($preferredCategories, $preferredDirectors, $preferredNarrators) {
                if (!empty($preferredCategories)) {
                    $query->orWhereIn('category_id', array_keys($preferredCategories));
                }
                if (!empty($preferredDirectors)) {
                    $query->orWhereIn('director_id', array_keys($preferredDirectors));
                }
                if (!empty($preferredNarrators)) {
                    $query->orWhereIn('narrator_id', array_keys($preferredNarrators));
                }
            })
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
            ->with(['category', 'director', 'narrator'])
            ->withCount(['favorites', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->limit($limit)
            ->get();

        $personalizedStories = [];
        foreach ($stories as $story) {
            $personalizationScore = $this->calculatePersonalizationScore($story, $userPreferences);
            
            $personalizedStories[] = [
                'story' => $story,
                'personalization_score' => $personalizationScore,
                'type' => 'preferred_content'
            ];
        }

        return $personalizedStories;
    }

    /**
     * Get trending content in preferred categories
     */
    private function getTrendingInPreferredCategories(int $userId, array $userPreferences, int $limit): array
    {
        $preferredCategories = $userPreferences['category'] ?? [];
        
        if (empty($preferredCategories)) {
            return [];
        }

        $stories = Story::where('status', 'published')
            ->whereIn('category_id', array_keys($preferredCategories))
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

        $personalizedStories = [];
        foreach ($stories as $story) {
            $personalizationScore = $this->calculatePersonalizationScore($story, $userPreferences);
            
            $personalizedStories[] = [
                'story' => $story,
                'personalization_score' => $personalizationScore,
                'type' => 'trending_preferred'
            ];
        }

        return $personalizedStories;
    }

    /**
     * Get new content from preferred creators
     */
    private function getNewFromPreferredCreators(int $userId, array $userPreferences, int $limit): array
    {
        $preferredDirectors = $userPreferences['director'] ?? [];
        $preferredNarrators = $userPreferences['narrator'] ?? [];
        
        if (empty($preferredDirectors) && empty($preferredNarrators)) {
            return [];
        }

        $stories = Story::where('status', 'published')
            ->where(function($query) use ($preferredDirectors, $preferredNarrators) {
                if (!empty($preferredDirectors)) {
                    $query->orWhereIn('director_id', array_keys($preferredDirectors));
                }
                if (!empty($preferredNarrators)) {
                    $query->orWhereIn('narrator_id', array_keys($preferredNarrators));
                }
            })
            ->where('created_at', '>=', now()->subDays(30)) // New content
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
            ->with(['category', 'director', 'narrator'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $personalizedStories = [];
        foreach ($stories as $story) {
            $personalizationScore = $this->calculatePersonalizationScore($story, $userPreferences);
            
            $personalizedStories[] = [
                'story' => $story,
                'personalization_score' => $personalizationScore,
                'type' => 'new_from_preferred'
            ];
        }

        return $personalizedStories;
    }

    /**
     * Get content similar to recently enjoyed content
     */
    private function getSimilarToRecentContent(int $userId, int $limit): array
    {
        // Get recently favorited or highly rated content
        $recentContent = DB::table('favorites')
            ->join('stories', 'favorites.story_id', '=', 'stories.id')
            ->where('favorites.user_id', $userId)
            ->where('favorites.created_at', '>=', now()->subDays(30))
            ->select('stories.id', 'stories.category_id', 'stories.director_id', 'stories.narrator_id')
            ->limit(5)
            ->get();

        if ($recentContent->isEmpty()) {
            return [];
        }

        $recentCategoryIds = $recentContent->pluck('category_id')->unique()->toArray();
        $recentDirectorIds = $recentContent->pluck('director_id')->filter()->unique()->toArray();
        $recentNarratorIds = $recentContent->pluck('narrator_id')->filter()->unique()->toArray();

        $stories = Story::where('status', 'published')
            ->where(function($query) use ($recentCategoryIds, $recentDirectorIds, $recentNarratorIds) {
                if (!empty($recentCategoryIds)) {
                    $query->orWhereIn('category_id', $recentCategoryIds);
                }
                if (!empty($recentDirectorIds)) {
                    $query->orWhereIn('director_id', $recentDirectorIds);
                }
                if (!empty($recentNarratorIds)) {
                    $query->orWhereIn('narrator_id', $recentNarratorIds);
                }
            })
            ->whereNotIn('id', $recentContent->pluck('id')->toArray())
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
            ->with(['category', 'director', 'narrator'])
            ->withCount(['favorites', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->limit($limit)
            ->get();

        $personalizedStories = [];
        foreach ($stories as $story) {
            $personalizationScore = $this->calculateSimilarityScore($story, $recentContent);
            
            $personalizedStories[] = [
                'story' => $story,
                'personalization_score' => $personalizationScore,
                'type' => 'similar_to_recent'
            ];
        }

        return $personalizedStories;
    }

    /**
     * Calculate personalization score for a story
     */
    private function calculatePersonalizationScore($story, array $userPreferences): float
    {
        $score = 0;

        // Category preference
        if (isset($userPreferences['category'][$story->category_id])) {
            $score += $userPreferences['category'][$story->category_id] * 0.4;
        }

        // Director preference
        if ($story->director_id && isset($userPreferences['director'][$story->director_id])) {
            $score += $userPreferences['director'][$story->director_id] * 0.3;
        }

        // Narrator preference
        if ($story->narrator_id && isset($userPreferences['narrator'][$story->narrator_id])) {
            $score += $userPreferences['narrator'][$story->narrator_id] * 0.3;
        }

        return $score;
    }

    /**
     * Calculate relevance score for search
     */
    private function calculateRelevanceScore($story, string $query): float
    {
        $score = 0;
        $query = strtolower($query);

        // Title match
        if (str_contains(strtolower($story->title), $query)) {
            $score += 0.5;
        }

        // Description match
        if (str_contains(strtolower($story->description), $query)) {
            $score += 0.3;
        }

        // Tags match
        if (str_contains(strtolower($story->tags), $query)) {
            $score += 0.2;
        }

        return $score;
    }

    /**
     * Calculate similarity score
     */
    private function calculateSimilarityScore($story, $recentContent): float
    {
        $score = 0;
        $totalSimilarity = 0;

        foreach ($recentContent as $recent) {
            $similarity = 0;

            // Category similarity
            if ($story->category_id === $recent->category_id) {
                $similarity += 0.4;
            }

            // Director similarity
            if ($story->director_id === $recent->director_id) {
                $similarity += 0.3;
            }

            // Narrator similarity
            if ($story->narrator_id === $recent->narrator_id) {
                $similarity += 0.3;
            }

            $totalSimilarity += $similarity;
        }

        return $totalSimilarity / count($recentContent);
    }

    /**
     * Deduplicate feed
     */
    private function deduplicateFeed(array $feed): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($feed as $item) {
            $key = $item['story']->id;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $deduplicated[] = $item;
            }
        }

        return $deduplicated;
    }

    /**
     * Filter consumed content
     */
    private function filterConsumedContent(array $feed, int $userId): array
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

        return array_filter($feed, function($item) use ($consumedStoryIds) {
            return !in_array($item['story']->id, $consumedStoryIds);
        });
    }

    /**
     * Personalize based on behavior
     */
    private function personalizeBasedOnBehavior(array $feed, array $userBehavior): array
    {
        foreach ($feed as &$item) {
            $score = $item['personalization_score'];

            // Boost score for content that matches user's preferred listening time
            $currentHour = now()->hour;
            if (in_array($currentHour, $userBehavior['preferred_hours'] ?? [])) {
                $score *= 1.1;
            }

            // Boost score for content that matches user's preferred episode length
            $episodeCount = $item['story']->episodes()->count();
            $preferredLength = $userBehavior['preferred_episode_length'] ?? 0;
            if ($preferredLength > 0 && abs($episodeCount - $preferredLength) < 5) {
                $score *= 1.05;
            }

            $item['personalization_score'] = $score;
        }

        return $feed;
    }

    /**
     * Get continue listening content
     */
    private function getContinueListening(int $userId, int $limit): array
    {
        $continueListening = PlayHistory::where('user_id', $userId)
            ->where('progress', '>', 0)
            ->where('progress', '<', 90)
            ->with(['episode.story'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        return $continueListening->map(function($play) {
            return [
                'story' => $play->episode->story,
                'episode' => $play->episode,
                'progress' => $play->progress,
                'last_played' => $play->updated_at,
                'type' => 'continue_listening'
            ];
        })->toArray();
    }

    /**
     * Get recently favorited content
     */
    private function getRecentlyFavorited(int $userId, int $limit): array
    {
        $recentFavorites = Favorite::where('user_id', $userId)
            ->with('story')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $recentFavorites->map(function($favorite) {
            return [
                'story' => $favorite->story,
                'favorited_at' => $favorite->created_at,
                'type' => 'recently_favorited'
            ];
        })->toArray();
    }

    /**
     * Get preferred categories
     */
    private function getPreferredCategories(int $userId): array
    {
        $preferences = UserRecommendationPreference::where('user_id', $userId)
            ->where('preference_type', 'category')
            ->orderBy('weight', 'desc')
            ->limit(5)
            ->get();

        return $preferences->map(function($pref) {
            $category = Category::find($pref->preference_value);
            return [
                'category' => $category,
                'weight' => $pref->weight,
                'interaction_count' => $pref->interaction_count
            ];
        })->toArray();
    }

    /**
     * Get preferred creators
     */
    private function getPreferredCreators(int $userId): array
    {
        $preferences = UserRecommendationPreference::where('user_id', $userId)
            ->whereIn('preference_type', ['director', 'narrator'])
            ->orderBy('weight', 'desc')
            ->limit(10)
            ->get();

        return $preferences->map(function($pref) {
            $person = \App\Models\Person::find($pref->preference_value);
            return [
                'person' => $person,
                'type' => $pref->preference_type,
                'weight' => $pref->weight,
                'interaction_count' => $pref->interaction_count
            ];
        })->toArray();
    }

    /**
     * Get listening patterns
     */
    private function getListeningPatterns(int $userId): array
    {
        $patterns = [];

        // Daily patterns
        $dailyPatterns = PlayHistory::where('user_id', $userId)
            ->selectRaw('DAYOFWEEK(created_at) as day_of_week, COUNT(*) as count')
            ->groupBy('day_of_week')
            ->get();

        $patterns['daily'] = $dailyPatterns->pluck('count', 'day_of_week')->toArray();

        // Hourly patterns
        $hourlyPatterns = PlayHistory::where('user_id', $userId)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->get();

        $patterns['hourly'] = $hourlyPatterns->pluck('count', 'hour')->toArray();

        return $patterns;
    }

    /**
     * Get content preferences
     */
    private function getContentPreferences(int $userId): array
    {
        $preferences = [];

        // Episode count preferences
        $episodeCountPrefs = UserRecommendationPreference::where('user_id', $userId)
            ->where('preference_type', 'episode_count')
            ->orderBy('weight', 'desc')
            ->get();

        $preferences['episode_count'] = $episodeCountPrefs->map(function($pref) {
            return [
                'value' => $pref->preference_value,
                'weight' => $pref->weight
            ];
        })->toArray();

        // Content age preferences
        $agePrefs = UserRecommendationPreference::where('user_id', $userId)
            ->where('preference_type', 'content_age')
            ->orderBy('weight', 'desc')
            ->get();

        $preferences['content_age'] = $agePrefs->map(function($pref) {
            return [
                'value' => $pref->preference_value,
                'weight' => $pref->weight
            ];
        })->toArray();

        return $preferences;
    }

    /**
     * Get episode count group
     */
    private function getEpisodeCountGroup(int $episodeCount): string
    {
        if ($episodeCount <= 5) return 'short';
        if ($episodeCount <= 15) return 'medium';
        return 'long';
    }

    /**
     * Get age group
     */
    private function getAgeGroup(int $ageInDays): string
    {
        if ($ageInDays <= 7) return 'very_recent';
        if ($ageInDays <= 30) return 'recent';
        if ($ageInDays <= 90) return 'moderate';
        return 'old';
    }

    /**
     * Clear user preference cache
     */
    private function clearUserPreferenceCache(int $userId): void
    {
        $patterns = [
            "user_preferences_{$userId}",
            "user_behavior_{$userId}",
            "personalized_feed_{$userId}_*",
            "personalized_dashboard_{$userId}",
            "personalized_search_{$userId}_*",
            "personalized_category_{$userId}_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
