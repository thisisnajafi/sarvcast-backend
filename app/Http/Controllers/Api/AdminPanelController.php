<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminPanelController extends Controller
{
    /**
     * Get dashboard statistics (super admin only)
     * 
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalRevenue = Payment::where('status', 'completed')->sum('net_amount');
        $totalStories = Story::count();
        $totalEpisodes = Episode::count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_revenue' => (float) $totalRevenue,
                'total_stories' => $totalStories,
                'total_episodes' => $totalEpisodes,
            ]
        ]);
    }

    /**
     * Get all stories with pagination and filters (admin & super admin)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStories(Request $request): JsonResponse
    {
        $query = Story::with(['category', 'author', 'narrator', 'characters.voiceActor']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->workflow_status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
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
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'play_count':
                $query->orderBy('play_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            default:
                $query->latest();
        }

        $perPage = min($request->get('per_page', 20), 100); // Max 100 per page
        $stories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stories
        ]);
    }

    /**
     * Get all episodes with pagination and filters (admin & super admin)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getEpisodes(Request $request): JsonResponse
    {
        $query = Episode::with(['story', 'narrator']);

        // Apply filters
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'episode_number':
                $query->orderBy('episode_number', 'asc');
                break;
            case 'play_count':
                $query->orderBy('play_count', 'desc');
                break;
            case 'duration':
                $query->orderBy('duration', 'desc');
                break;
            default:
                $query->latest();
        }

        $perPage = min($request->get('per_page', 20), 100); // Max 100 per page
        $episodes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $episodes
        ]);
    }

    /**
     * Get all users with pagination and filters (super admin only)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsers(Request $request): JsonResponse
    {
        $query = User::with(['profiles']);

        // Apply filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name':
                $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
                break;
            default:
                $query->latest();
        }

        $perPage = min($request->get('per_page', 20), 100); // Max 100 per page
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Assign voice_actor role to a user (super admin only)
     * 
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function assignVoiceActorRole(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        // Check if user already has voice_actor role
        if ($user->role === User::ROLE_VOICE_ACTOR) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر قبلاً نقش صداپیشه دارد.'
            ], 400);
        }

        // Assign voice_actor role
        $user->update(['role' => User::ROLE_VOICE_ACTOR]);

        return response()->json([
            'success' => true,
            'message' => 'نقش صداپیشه با موفقیت به کاربر اختصاص داده شد.',
            'data' => [
                'user' => $user->fresh()
            ]
        ]);
    }
}
