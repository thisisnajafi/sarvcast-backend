<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Favorite;
use App\Models\PlayHistory;
use App\Models\Story;
use App\Models\User;
use App\Models\ProfileView;
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
     * Get authenticated user's personalization profile
     */
    public function getUserProfile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->profilePayload(),
        ]);
    }

    /**
     * Update authenticated user's personalization profile (onboarding / settings)
     */
    public function updateUserProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:100',
            'gender' => 'sometimes|nullable|in:male,female,unspecified',
            'birthday' => 'sometimes|nullable|date|before:today',
            'account_type' => 'sometimes|in:child,parent,shared',
            'favorite_category_ids' => 'sometimes|nullable|array',
            'favorite_category_ids.*' => 'string',
            'onboarding_completed' => 'sometimes|boolean',
            'skip_onboarding' => 'sometimes|boolean',
        ]);

        if ($request->boolean('skip_onboarding')) {
            $user->update([
                'gender' => 'unspecified',
                'account_type' => 'child',
                'onboarding_completed' => true,
                'status' => User::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'آنبوردینگ رد شد',
                'data' => $user->fresh()->profilePayload(),
            ]);
        }

        $payload = collect($validated)->except(['skip_onboarding'])->all();

        if (array_key_exists('favorite_category_ids', $payload) && $payload['favorite_category_ids'] !== null) {
            $payload['favorite_category_ids'] = array_values(
                array_map('strval', $payload['favorite_category_ids'])
            );
        }

        if ($request->boolean('onboarding_completed')) {
            $payload['onboarding_completed'] = true;
            $payload['status'] = User::STATUS_ACTIVE;
        }

        $user->update($payload);

        if (!empty($user->name) && empty($user->first_name)) {
            $user->update(['first_name' => $user->name]);
        }

        $user->refresh();

        if ($user->onboarding_completed && $user->status !== User::STATUS_ACTIVE) {
            $user->update(['status' => User::STATUS_ACTIVE]);
        }

        return response()->json([
            'success' => true,
            'message' => 'پروفایل با موفقیت به‌روزرسانی شد',
            'data' => $user->fresh()->profilePayload(),
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

        // Get published stories as author
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
        $authorCount = $storiesAsAuthor->count();
        $narratorCount = $storiesAsNarrator->count();
        $voiceActorCount = $storiesAsVoiceActor->count();

        // Get profile view count
        $viewCount = ProfileView::where('viewed_user_id', $userId)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_image_url' => $user->profile_image_url,
                    'background_photo_url' => $user->background_photo_url,
                    'bio' => $user->bio,
                    'role' => $user->role,
                ],
                'statistics' => [
                    'author_count' => $authorCount,
                    'narrator_count' => $narratorCount,
                    'voice_actor_count' => $voiceActorCount,
                    'view_count' => $viewCount,
                ],
                'stories_as_author' => $storiesAsAuthor,
                'stories_as_narrator' => $storiesAsNarrator,
                'stories_as_voice_actor' => $storiesAsVoiceActor,
            ]
        ]);
    }

    /**
     * Track a profile view
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function trackProfileView(Request $request, int $userId)
    {
        $viewedUser = User::findOrFail($userId);
        $viewer = $request->user(); // Can be null for anonymous users

        // Don't track if user views their own profile
        if ($viewer && $viewer->id === $userId) {
            return response()->json([
                'success' => true,
                'message' => 'Self-view not tracked',
            ]);
        }

        // Check if already viewed today by same user (prevent spam)
        $today = now()->startOfDay();
        $existingView = ProfileView::where('viewed_user_id', $userId)
            ->where('viewer_id', $viewer?->id)
            ->where('created_at', '>=', $today)
            ->first();

        if ($existingView) {
            return response()->json([
                'success' => true,
                'message' => 'View already tracked today',
            ]);
        }

        // Create new view record
        ProfileView::create([
            'viewed_user_id' => $userId,
            'viewer_id' => $viewer?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Get updated view count
        $viewCount = ProfileView::where('viewed_user_id', $userId)->count();

        return response()->json([
            'success' => true,
            'message' => 'Profile view tracked',
            'data' => [
                'view_count' => $viewCount,
            ],
        ]);
    }

    /**
     * Get profile view count
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getProfileViewCount(Request $request, int $userId)
    {
        $viewCount = ProfileView::where('viewed_user_id', $userId)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'view_count' => $viewCount,
            ],
        ]);
    }

    /**
     * Update push notification preferences for authenticated user.
     */
    public function updateNotificationPreferences(Request $request)
    {
        $request->validate([
            'push_notifications_enabled' => 'required|boolean',
        ]);

        $user = Auth::user();
        $preferences = $user->preferences ?? [];
        $preferences['push_notifications_enabled'] = $request->boolean('push_notifications_enabled');
        $user->preferences = $preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'data' => [
                'push_notifications_enabled' => $preferences['push_notifications_enabled'],
            ],
        ]);
    }

    /**
     * Get visible Manji team members (public endpoint).
     */
    public function getTeamMembers(Request $request)
    {
        $members = \App\Models\TeamMember::query()
            ->visible()
            ->ordered()
            ->whereHas('user')
            ->with('user:id,first_name,last_name,phone_number,profile_image_url,bio')
            ->get()
            ->map(fn (\App\Models\TeamMember $member) => $member->toPublicArray())
            ->filter(fn (array $row) => ! empty($row))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }
}
