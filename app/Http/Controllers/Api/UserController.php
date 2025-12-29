<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Favorite;
use App\Models\PlayHistory;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get user favorites
     */
    public function favorites(Request $request)
    {
        $user = Auth::user();

        $favorites = $user->favorites()
            ->with(['story', 'episode'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'favorites' => $favorites->items(),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'per_page' => $favorites->perPage(),
                    'total' => $favorites->total()
                ]
            ]
        ]);
    }

    /**
     * Get user play history
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        $history = $user->playHistories()
            ->with(['episode.story'])
            ->latest('played_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $history->items(),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total()
                ]
            ]
        ]);
    }

    /**
     * Create child profile
     */
    public function createProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'age' => 'required|integer|min:3|max:18',
            'avatar_url' => 'nullable|url',
            'interests' => 'nullable|array',
            'parental_controls' => 'nullable|array'
        ]);

        $user = Auth::user();

        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can create child profiles'
            ], 403);
        }

        $profile = $user->profiles()->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Child profile created successfully',
            'data' => [
                'profile' => $profile
            ]
        ], 201);
    }

    /**
     * Get user profiles
     */
    public function profiles()
    {
        $user = Auth::user();
        $profiles = $user->profiles()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'profiles' => $profiles
            ]
        ]);
    }

    /**
     * Update child profile
     */
    public function updateProfile(Request $request, UserProfile $profile)
    {
        $user = Auth::user();

        if ($profile->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'age' => 'sometimes|integer|min:3|max:18',
            'avatar_url' => 'nullable|url',
            'interests' => 'nullable|array',
            'parental_controls' => 'nullable|array'
        ]);

        $profile->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'profile' => $profile
            ]
        ]);
    }

    /**
     * Delete child profile
     */
    public function deleteProfile(UserProfile $profile)
    {
        $user = Auth::user();

        if ($profile->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $profile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Profile deleted successfully'
        ]);
    }

    /**
     * Get all stories where a user has a role (author, narrator, or voice actor)
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserStories(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        // Get published stories as author/writer
        $storiesAsAuthor = Story::whereAuthor($userId)
            ->published()
            ->with(['category', 'characters.voiceActor'])
            ->get();

        // Get published stories as narrator
        $storiesAsNarrator = Story::whereNarrator($userId)
            ->published()
            ->with(['category', 'author', 'characters.voiceActor'])
            ->get();

        // Get published stories as voice actor (through characters)
        $storiesAsVoiceActor = Story::whereVoiceActor($userId)
            ->published()
            ->with(['category', 'author', 'narrator', 'characters.voiceActor'])
            ->get();

        // Calculate statistics
        $writerCount = $storiesAsAuthor->count();
        $narratorCount = $storiesAsNarrator->count();
        $voiceActorCount = $storiesAsVoiceActor->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_image_url' => $user->profile_image_url,
                    'bio' => $user->bio,
                    'role' => $user->role,
                ],
                'statistics' => [
                    'writer_count' => $writerCount,
                    'narrator_count' => $narratorCount,
                    'voice_actor_count' => $voiceActorCount,
                ],
                'stories_as_author' => $storiesAsAuthor,
                'stories_as_narrator' => $storiesAsNarrator,
                'stories_as_voice_actor' => $storiesAsVoiceActor,
            ]
        ]);
    }
}
