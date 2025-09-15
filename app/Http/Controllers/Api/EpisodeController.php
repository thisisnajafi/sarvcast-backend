<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\PlayHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EpisodeController extends Controller
{
    /**
     * Get episode details
     */
    public function show(Episode $episode)
    {
        $episode->load(['story', 'narrator', 'people']);

        // Check if user has access to premium content
        if ($episode->is_premium && (!Auth::check() || !Auth::user()->hasActiveSubscription())) {
            return response()->json([
                'success' => false,
                'message' => 'This episode requires a premium subscription'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'episode' => $episode
            ]
        ]);
    }

    /**
     * Record episode play
     */
    public function play(Episode $episode)
    {
        $user = Auth::user();

        // Check if user has access to premium content
        if ($episode->is_premium && !$user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'This episode requires a premium subscription'
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
