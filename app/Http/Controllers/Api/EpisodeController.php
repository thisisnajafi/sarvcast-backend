<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\PlayHistory;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EpisodeController extends Controller
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }
    /**
     * Get paginated list of episodes
     */
    public function index(Request $request)
    {
        $query = Episode::with(['story', 'narrator', 'people', 'imageTimelines.voiceActor.person'])
            ->published();

        // Apply filters
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'episode_number');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['episode_number', 'title', 'duration', 'play_count', 'rating', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $episodes = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $episodes->items(),
            'pagination' => [
                'current_page' => $episodes->currentPage(),
                'last_page' => $episodes->lastPage(),
                'per_page' => $episodes->perPage(),
                'total' => $episodes->total()
            ]
        ]);
    }

    /**
     * Get episode details
     */
    public function show(Request $request, Episode $episode)
    {
        $includeTimeline = $request->get('include_timeline', false);
        
        $episode->load(['story', 'narrator', 'people', 'imageTimelines.voiceActor.person']);

        // Check access control
        $user = $request->user();
        $accessInfo = $this->accessControlService->canAccessEpisode($user ? $user->id : 0, $episode->id);

        if (!$accessInfo['has_access']) {
            return response()->json([
                'success' => false,
                'message' => $accessInfo['message'],
                'error_code' => strtoupper($accessInfo['reason']),
                'data' => [
                    'access_info' => $accessInfo,
                    'upgrade_required' => $accessInfo['reason'] === 'premium_required'
                ]
            ], 403);
        }

        $responseData = [
            'episode' => $episode,
            'access_info' => $accessInfo
        ];

        // Add timeline data if episode uses image timeline
        if ($episode->use_image_timeline) {
            $responseData['image_timeline'] = $episode->imageTimelines->map(function($timeline) {
                return $timeline->toApiResponse();
            });
        }

        return response()->json([
            'success' => true,
            'data' => $responseData
        ]);
    }

    /**
     * Record episode play
     */
    public function play(Request $request, Episode $episode)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Check access control
        $accessInfo = $this->accessControlService->canAccessEpisode($user->id, $episode->id);

        if (!$accessInfo['has_access']) {
            return response()->json([
                'success' => false,
                'message' => $accessInfo['message'],
                'error_code' => strtoupper($accessInfo['reason']),
                'data' => [
                    'access_info' => $accessInfo,
                    'upgrade_required' => $accessInfo['reason'] === 'premium_required'
                ]
            ], 403);
        }

        // Record play history
        PlayHistory::create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'played_at' => now(),
            'duration' => $episode->duration
        ]);

        // Update episode play count
        $episode->increment('play_count');

        return response()->json([
            'success' => true,
            'message' => 'Episode play recorded',
            'data' => [
                'episode' => $episode->fresh()
            ]
        ]);
    }

    /**
     * Bookmark episode
     */
    public function bookmark(Episode $episode)
    {
        $user = Auth::user();

        // Check if already bookmarked
        $existingBookmark = $user->favorites()->where('episode_id', $episode->id)->first();
        
        if ($existingBookmark) {
            return response()->json([
                'success' => false,
                'message' => 'Episode is already bookmarked'
            ], 400);
        }

        // Create bookmark
        $user->favorites()->create([
            'episode_id' => $episode->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Episode bookmarked successfully'
        ]);
    }

    /**
     * Remove episode bookmark
     */
    public function removeBookmark(Episode $episode)
    {
        $user = Auth::user();

        $user->favorites()->where('episode_id', $episode->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Episode bookmark removed successfully'
        ]);
    }
}
