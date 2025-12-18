<?php

namespace App\Services;

use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\Person;
use App\Models\Rating;
use App\Models\Favorite;
use App\Models\PlayHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * Search stories with advanced filtering
     */
    public function searchStories(array $params = []): array
    {
        try {
            $query = Story::with(['category', 'episodes', 'director', 'writer', 'author', 'narrator'])
                         ->where('status', 'published');

            // Text search
            if (!empty($params['q'])) {
                $searchTerm = $params['q'];
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('subtitle', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('tags', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Category filter
            if (!empty($params['category_id'])) {
                $query->where('category_id', $params['category_id']);
            }

            // Age group filter
            if (!empty($params['age_group'])) {
                $query->where('age_group', $params['age_group']);
            }

            // Duration filter
            if (!empty($params['min_duration'])) {
                $query->where('duration', '>=', $params['min_duration']);
            }
            if (!empty($params['max_duration'])) {
                $query->where('duration', '<=', $params['max_duration']);
            }

            // Premium filter
            if (isset($params['is_premium'])) {
                $query->where('is_premium', $params['is_premium']);
            }

            // Rating filter
            if (!empty($params['min_rating'])) {
                $query->whereHas('ratings', function ($q) use ($params) {
                    $q->selectRaw('AVG(rating) as avg_rating')
                      ->groupBy('story_id')
                      ->having('avg_rating', '>=', $params['min_rating']);
                });
            }

            // Person filter (director, writer, narrator, etc.)
            if (!empty($params['person_id'])) {
                $query->where(function ($q) use ($params) {
                    $q->where('director_id', $params['person_id'])
                      ->orWhere('writer_id', $params['person_id'])
                      ->orWhere('author_id', $params['person_id'])
                      ->orWhere('narrator_id', $params['person_id']);
                });
            }

            // Sort options
            $sortBy = $params['sort_by'] ?? 'created_at';
            $sortOrder = $params['sort_order'] ?? 'desc';

            switch ($sortBy) {
                case 'title':
                    $query->orderBy('title', $sortOrder);
                    break;
                case 'duration':
                    $query->orderBy('duration', $sortOrder);
                    break;
                case 'play_count':
                    $query->orderBy('play_count', $sortOrder);
                    break;
                case 'rating':
                    $query->leftJoin('ratings', 'stories.id', '=', 'ratings.story_id')
                          ->selectRaw('stories.*, AVG(ratings.rating) as avg_rating')
                          ->groupBy('stories.id')
                          ->orderBy('avg_rating', $sortOrder);
                    break;
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortOrder);
                    break;
            }

            // Pagination
            $perPage = min($params['per_page'] ?? 20, 100);
            $results = $query->paginate($perPage);

            // Add additional data to each story
            $results->getCollection()->transform(function ($story) {
                $story->avg_rating = $story->ratings()->avg('rating');
                $story->rating_count = $story->ratings()->count();
                $story->favorite_count = $story->favorites()->count();
                $story->episode_count = $story->episodes()->count();
                return $story;
            });

            return [
                'stories' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'has_more' => $results->hasMorePages()
                ],
                'filters_applied' => $this->getAppliedFilters($params)
            ];

        } catch (\Exception $e) {
            Log::error('Search stories failed', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Search episodes with advanced filtering
     */
    public function searchEpisodes(array $params = []): array
    {
        try {
            $query = Episode::with(['story.category', 'story.director', 'story.writer'])
                           ->where('status', 'published');

            // Text search
            if (!empty($params['q'])) {
                $searchTerm = $params['q'];
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('story', function ($storyQuery) use ($searchTerm) {
                          $storyQuery->where('title', 'LIKE', "%{$searchTerm}%")
                                    ->orWhere('subtitle', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }

            // Story filter
            if (!empty($params['story_id'])) {
                $query->where('story_id', $params['story_id']);
            }

            // Duration filter
            if (!empty($params['min_duration'])) {
                $query->where('duration', '>=', $params['min_duration']);
            }
            if (!empty($params['max_duration'])) {
                $query->where('duration', '<=', $params['max_duration']);
            }

            // Premium filter
            if (isset($params['is_premium'])) {
                $query->where('is_premium', $params['is_premium']);
            }

            // Episode number filter
            if (!empty($params['episode_number'])) {
                $query->where('episode_number', $params['episode_number']);
            }

            // Sort options
            $sortBy = $params['sort_by'] ?? 'episode_number';
            $sortOrder = $params['sort_order'] ?? 'asc';

            switch ($sortBy) {
                case 'title':
                    $query->orderBy('title', $sortOrder);
                    break;
                case 'duration':
                    $query->orderBy('duration', $sortOrder);
                    break;
                case 'play_count':
                    $query->orderBy('play_count', $sortOrder);
                    break;
                case 'episode_number':
                default:
                    $query->orderBy('episode_number', $sortOrder);
                    break;
            }

            // Pagination
            $perPage = min($params['per_page'] ?? 20, 100);
            $results = $query->paginate($perPage);

            // Add additional data to each episode
            $results->getCollection()->transform(function ($episode) {
                $episode->avg_rating = $episode->ratings()->avg('rating');
                $episode->rating_count = $episode->ratings()->count();
                return $episode;
            });

            return [
                'episodes' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'has_more' => $results->hasMorePages()
                ],
                'filters_applied' => $this->getAppliedFilters($params)
            ];

        } catch (\Exception $e) {
            Log::error('Search episodes failed', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Search people (voice actors, directors, etc.)
     */
    public function searchPeople(array $params = []): array
    {
        try {
            $query = Person::with(['stories']);

            // Text search
            if (!empty($params['q'])) {
                $searchTerm = $params['q'];
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('bio', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Role filter
            if (!empty($params['role'])) {
                $query->where('role', $params['role']);
            }

            // Sort options
            $sortBy = $params['sort_by'] ?? 'name';
            $sortOrder = $params['sort_order'] ?? 'asc';

            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($params['per_page'] ?? 20, 100);
            $results = $query->paginate($perPage);

            // Add additional data to each person
            $results->getCollection()->transform(function ($person) {
                $person->story_count = $person->stories()->count();
                return $person;
            });

            return [
                'people' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'has_more' => $results->hasMorePages()
                ],
                'filters_applied' => $this->getAppliedFilters($params)
            ];

        } catch (\Exception $e) {
            Log::error('Search people failed', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Global search across all content types
     */
    public function globalSearch(array $params = []): array
    {
        try {
            $searchTerm = $params['q'] ?? '';
            $limit = min($params['limit'] ?? 10, 50);

            $results = [
                'stories' => [],
                'episodes' => [],
                'people' => [],
                'categories' => []
            ];

            if (empty($searchTerm)) {
                return $results;
            }

            // Search stories
            $stories = Story::with(['category'])
                           ->where('status', 'published')
                           ->where(function ($q) use ($searchTerm) {
                               $q->where('title', 'LIKE', "%{$searchTerm}%")
                                 ->orWhere('subtitle', 'LIKE', "%{$searchTerm}%")
                                 ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                           })
                           ->limit($limit)
                           ->get();

            $results['stories'] = $stories->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'subtitle' => $story->subtitle,
                    'category' => $story->category,
                    'type' => 'story'
                ];
            });

            // Search episodes
            $episodes = Episode::with(['story.category'])
                             ->where('status', 'published')
                             ->where(function ($q) use ($searchTerm) {
                                 $q->where('title', 'LIKE', "%{$searchTerm}%")
                                   ->orWhereHas('story', function ($storyQuery) use ($searchTerm) {
                                       $storyQuery->where('title', 'LIKE', "%{$searchTerm}%");
                                   });
                             })
                             ->limit($limit)
                             ->get();

            $results['episodes'] = $episodes->map(function ($episode) {
                return [
                    'id' => $episode->id,
                    'title' => $episode->title,
                    'episode_number' => $episode->episode_number,
                    'story' => $episode->story,
                    'type' => 'episode'
                ];
            });

            // Search people
            $people = Person::where(function ($q) use ($searchTerm) {
                               $q->where('name', 'LIKE', "%{$searchTerm}%")
                                 ->orWhere('bio', 'LIKE', "%{$searchTerm}%");
                           })
                           ->limit($limit)
                           ->get();

            $results['people'] = $people->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->name,
                    'role' => $person->role,
                    'type' => 'person'
                ];
            });

            // Search categories
            $categories = Category::where(function ($q) use ($searchTerm) {
                                   $q->where('name', 'LIKE', "%{$searchTerm}%")
                                     ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                               })
                               ->limit($limit)
                               ->get();

            $results['categories'] = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'type' => 'category'
                ];
            });

            return $results;

        } catch (\Exception $e) {
            Log::error('Global search failed', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get search suggestions based on popular searches
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        try {
            $suggestions = [];

            // Story title suggestions
            $storySuggestions = Story::where('status', 'published')
                                   ->where('title', 'LIKE', "%{$query}%")
                                   ->limit($limit)
                                   ->pluck('title')
                                   ->toArray();

            $suggestions = array_merge($suggestions, $storySuggestions);

            // Category suggestions
            $categorySuggestions = Category::where('name', 'LIKE', "%{$query}%")
                                         ->limit($limit)
                                         ->pluck('name')
                                         ->toArray();

            $suggestions = array_merge($suggestions, $categorySuggestions);

            // Person name suggestions
            $personSuggestions = Person::where('name', 'LIKE', "%{$query}%")
                                     ->limit($limit)
                                     ->pluck('name')
                                     ->toArray();

            $suggestions = array_merge($suggestions, $personSuggestions);

            // Remove duplicates and limit results
            $suggestions = array_unique($suggestions);
            $suggestions = array_slice($suggestions, 0, $limit);

            return $suggestions;

        } catch (\Exception $e) {
            Log::error('Get search suggestions failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get trending searches
     */
    public function getTrendingSearches(int $limit = 10): array
    {
        try {
            // This would typically come from a search log table
            // For now, we'll return popular story titles and categories
            $trendingStories = Story::where('status', 'published')
                                  ->orderBy('play_count', 'desc')
                                  ->limit($limit)
                                  ->pluck('title')
                                  ->toArray();

            $trendingCategories = Category::orderBy('story_count', 'desc')
                                        ->limit($limit)
                                        ->pluck('name')
                                        ->toArray();

            $trending = array_merge($trendingStories, $trendingCategories);
            $trending = array_unique($trending);
            $trending = array_slice($trending, 0, $limit);

            return $trending;

        } catch (\Exception $e) {
            Log::error('Get trending searches failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get search filters and options
     */
    public function getSearchFilters(): array
    {
        try {
            return [
                'categories' => Category::select('id', 'name', 'color')->get(),
                'age_groups' => ['3-5', '6-9', '10-12', '13+'],
                'duration_ranges' => [
                    ['min' => 0, 'max' => 300, 'label' => 'کمتر از 5 دقیقه'],
                    ['min' => 300, 'max' => 900, 'label' => '5-15 دقیقه'],
                    ['min' => 900, 'max' => 1800, 'label' => '15-30 دقیقه'],
                    ['min' => 1800, 'max' => 3600, 'label' => '30-60 دقیقه'],
                    ['min' => 3600, 'max' => null, 'label' => 'بیش از 60 دقیقه']
                ],
                'person_roles' => Person::distinct()->pluck('role')->filter()->values()->toArray(),
                'sort_options' => [
                    ['value' => 'created_at', 'label' => 'جدیدترین'],
                    ['value' => 'title', 'label' => 'عنوان'],
                    ['value' => 'duration', 'label' => 'مدت زمان'],
                    ['value' => 'play_count', 'label' => 'محبوبیت'],
                    ['value' => 'rating', 'label' => 'امتیاز']
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Get search filters failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get applied filters for response
     */
    private function getAppliedFilters(array $params): array
    {
        $applied = [];
        
        if (!empty($params['q'])) {
            $applied['search_term'] = $params['q'];
        }
        if (!empty($params['category_id'])) {
            $applied['category_id'] = $params['category_id'];
        }
        if (!empty($params['age_group'])) {
            $applied['age_group'] = $params['age_group'];
        }
        if (!empty($params['min_duration'])) {
            $applied['min_duration'] = $params['min_duration'];
        }
        if (!empty($params['max_duration'])) {
            $applied['max_duration'] = $params['max_duration'];
        }
        if (isset($params['is_premium'])) {
            $applied['is_premium'] = $params['is_premium'];
        }
        if (!empty($params['min_rating'])) {
            $applied['min_rating'] = $params['min_rating'];
        }
        if (!empty($params['person_id'])) {
            $applied['person_id'] = $params['person_id'];
        }
        if (!empty($params['sort_by'])) {
            $applied['sort_by'] = $params['sort_by'];
        }
        if (!empty($params['sort_order'])) {
            $applied['sort_order'] = $params['sort_order'];
        }

        return $applied;
    }
}
