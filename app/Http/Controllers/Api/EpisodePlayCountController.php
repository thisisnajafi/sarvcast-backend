<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\PlayHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EpisodePlayCountController extends Controller
{
    /**
     * Increment episode play count
     */
    public function increment(Request $request, Episode $episode)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'duration_played' => 'nullable|integer|min:0',
            'completed' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Increment episode play count
        $episode->increment('play_count');

        // If user is authenticated, record play history
        if ($user) {
            $durationPlayed = $request->get('duration_played', 0);
            $completed = $request->boolean('completed', false);

            PlayHistory::create([
                'user_id' => $user->id,
                'episode_id' => $episode->id,
                'story_id' => $episode->story_id,
                'duration_played' => $durationPlayed,
                'completed' => $completed,
                'played_at' => now()
            ]);

            // Update story play count if this is the first play of this episode by this user
            $isFirstPlay = !PlayHistory::where('user_id', $user->id)
                ->where('episode_id', $episode->id)
                ->where('id', '!=', PlayHistory::latest()->first()->id)
                ->exists();

            if ($isFirstPlay) {
                $episode->story->increment('play_count');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Play count incremented successfully',
            'data' => [
                'episode_id' => $episode->id,
                'play_count' => $episode->fresh()->play_count
            ]
        ]);
    }

    /**
     * Get episode play statistics
     */
    public function statistics(Episode $episode)
    {
        $totalPlays = $episode->play_count;
        $uniqueListeners = $episode->playHistories()
            ->distinct('user_id')
            ->count();
        
        $completionRate = 0;
        if ($totalPlays > 0) {
            $completedPlays = $episode->playHistories()
                ->where('completed', true)
                ->count();
            $completionRate = round(($completedPlays / $totalPlays) * 100, 2);
        }

        $averageDurationPlayed = $episode->playHistories()
            ->avg('duration_played') ?? 0;

        $recentPlays = $episode->playHistories()
            ->with('user')
            ->orderBy('played_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Episode play statistics retrieved successfully',
            'data' => [
                'episode_id' => $episode->id,
                'total_plays' => $totalPlays,
                'unique_listeners' => $uniqueListeners,
                'completion_rate' => $completionRate,
                'average_duration_played' => round($averageDurationPlayed, 2),
                'recent_plays' => $recentPlays
            ]
        ]);
    }

    /**
     * Get user's play history for an episode
     */
    public function userHistory(Request $request, Episode $episode)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $playHistory = $episode->playHistories()
            ->where('user_id', $user->id)
            ->orderBy('played_at', 'desc')
            ->get();

        $totalPlays = $playHistory->count();
        $totalDurationPlayed = $playHistory->sum('duration_played');
        $completedPlays = $playHistory->where('completed', true)->count();
        $lastPlayed = $playHistory->first()?->played_at;

        return response()->json([
            'success' => true,
            'message' => 'User play history retrieved successfully',
            'data' => [
                'episode_id' => $episode->id,
                'total_plays' => $totalPlays,
                'total_duration_played' => $totalDurationPlayed,
                'completed_plays' => $completedPlays,
                'last_played' => $lastPlayed,
                'play_history' => $playHistory
            ]
        ]);
    }

    /**
     * Mark episode as completed
     */
    public function markCompleted(Request $request, Episode $episode)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'duration_played' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create or update play history
        $playHistory = PlayHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'episode_id' => $episode->id,
                'story_id' => $episode->story_id
            ],
            [
                'duration_played' => $request->get('duration_played', $episode->duration),
                'completed' => true,
                'played_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Episode marked as completed successfully',
            'data' => $playHistory
        ]);
    }
}