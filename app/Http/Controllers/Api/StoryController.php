<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Favorite;
use App\Models\Rating;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoryController extends Controller
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Transform stories for API response
     */
    private function transformStories($stories)
    {
        foreach ($stories as $story) {
            // Duration is already in seconds in database, no conversion needed
            // The API response should match the Flutter documentation format
        }
        return $stories;
    }
    /**
     * Get paginated list of stories
     */
    public function index(Request $request)
    {
        $query = Story::with(['category', 'director', 'narrator'])
            ->published();

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->orderBy('play_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            default:
                $query->latest();
        }

        $stories = $query->paginate($request->get('per_page', 20));

        // Transform stories to match API specification
        $transformedStories = $this->transformStories($stories->items());

        return response()->json([
            'success' => true,
            'data' => $transformedStories
        ]);
    }

    /**
     * Get detailed story information
     */
    public function show(Request $request, Story $story)
    {
        $story->load(['category', 'director', 'writer', 'author', 'narrator', 'episodes', 'people']);
        
        // Check access control
        $user = $request->user();
        $accessInfo = $this->accessControlService->canAccessStory($user ? $user->id : 0, $story->id);
        
        // Check if user has favorited this story
        $isFavorite = false;
        if ($user) {
            $isFavorite = Favorite::where('user_id', $user->id)
                ->where('story_id', $story->id)
                ->exists();
        }

        // Get user's rating if authenticated
        $userRating = null;
        if ($user) {
            $rating = Rating::where('user_id', $user->id)
                ->where('story_id', $story->id)
                ->first();
            $userRating = $rating ? $rating->rating : null;
        }

        // Filter episodes based on access
        $accessibleEpisodes = [];
        if ($accessInfo['has_access']) {
            foreach ($story->episodes as $episode) {
                $episodeAccess = $this->accessControlService->canAccessEpisode($user ? $user->id : 0, $episode->id);
                if ($episodeAccess['has_access']) {
                    $accessibleEpisodes[] = $episode;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'story' => $story,
                'access_info' => $accessInfo,
                'accessible_episodes' => $accessibleEpisodes,
                'is_favorite' => $isFavorite,
                'user_rating' => $userRating
            ]
        ]);
    }

    /**
     * Get episodes for a specific story
     */
    public function episodes(Story $story, Request $request)
    {
        $query = $story->episodes()->published();

        // Filter by premium status if user doesn't have active subscription
        if (Auth::check() && !Auth::user()->hasActiveSubscription()) {
            $query->where('is_premium', false);
        }

        $episodes = $query->with(['narrator', 'people', 'imageTimelines.voiceActor.person'])->orderBy('episode_number')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'episodes' => $episodes
            ]
        ]);
    }

    /**
     * Add story to favorites
     */
    public function addFavorite(Story $story)
    {
        $user = Auth::user();

        // Check if already favorited
        if (Favorite::where('user_id', $user->id)->where('story_id', $story->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Story is already in favorites'
            ], 400);
        }

        Favorite::create([
            'user_id' => $user->id,
            'story_id' => $story->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Story added to favorites'
        ]);
    }

    /**
     * Remove story from favorites
     */
    public function removeFavorite(Story $story)
    {
        $user = Auth::user();

        Favorite::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Story removed from favorites'
        ]);
    }

    /**
     * Get featured stories for the home page
     */
    public function featured(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
            ->published()
            ->where('is_featured', true)
            ->orderBy('featured_order', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Featured stories retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }

    /**
     * Get popular stories based on play count and ratings
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', 'all'); // daily, weekly, monthly, all
        
        $query = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
            ->published();

        // Apply time period filter
        if ($period !== 'all') {
            $days = match($period) {
                'daily' => 1,
                'weekly' => 7,
                'monthly' => 30,
                default => 1
            };
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $stories = $query->orderBy('play_count', 'desc')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Popular stories retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }

    /**
     * Get recently added stories
     */
    public function recent(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
            ->published()
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Recent stories retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }

    /**
     * Get personalized story recommendations for the user
     */
    public function recommendations(Request $request)
    {
        $limit = $request->get('limit', 10);
        $user = $request->user();

        if (!$user) {
            // Return popular stories for non-authenticated users
            $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
                ->published()
                ->orderBy('play_count', 'desc')
                ->limit($limit)
                ->get();
        } else {
            // Get personalized recommendations based on user preferences
            $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
                ->published()
                ->whereNotIn('id', function($query) use ($user) {
                    $query->select('story_id')
                        ->from('play_histories')
                        ->where('user_id', $user->id);
                })
                ->orderBy('rating', 'desc')
                ->limit($limit)
                ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Story recommendations retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }
}
