<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoiceActorsManagementController extends Controller
{
    /**
     * Display a listing of voice actors (users with voice_actor role)
     */
    public function index(Request $request)
    {
        $query = User::whereIn('role', [
            User::ROLE_VOICE_ACTOR,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $voiceActors = $query->withCount([
            'characters as total_characters',
            'storiesAsNarrator as total_stories_narrated',
            'storiesAsAuthor as total_stories_authored'
        ])
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->paginate(20);

        // Statistics
        $stats = [
            'total_voice_actors' => User::where('role', User::ROLE_VOICE_ACTOR)->count(),
            'total_admins' => User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->count(),
            'active_users' => User::whereIn('role', [User::ROLE_VOICE_ACTOR, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                ->where('status', 'active')->count(),
            'total_characters_assigned' => Character::whereNotNull('voice_actor_id')->count(),
        ];

        return view('admin.voice-actors.index', compact('voiceActors', 'stats'));
    }

    /**
     * Show the profile of a voice actor
     */
    public function show(User $voiceActor)
    {
        // Ensure user has eligible role
        if (!in_array($voiceActor->role, [
            User::ROLE_VOICE_ACTOR,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN
        ])) {
            abort(404);
        }

        $voiceActor->load([
            'characters.story',
            'storiesAsNarrator',
            'storiesAsAuthor'
        ]);

        $stats = [
            'total_characters' => $voiceActor->characters()->count(),
            'total_stories_narrated' => $voiceActor->storiesAsNarrator()->count(),
            'total_stories_authored' => $voiceActor->storiesAsAuthor()->count(),
            'total_episodes' => Episode::whereHas('story', function($q) use ($voiceActor) {
                $q->where('narrator_id', $voiceActor->id);
            })->count(),
        ];

        return view('admin.voice-actors.show', compact('voiceActor', 'stats'));
    }

    /**
     * Show the form for editing a voice actor
     */
    public function edit(User $voiceActor)
    {
        // Ensure user has eligible role
        if (!in_array($voiceActor->role, [
            User::ROLE_VOICE_ACTOR,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN
        ])) {
            abort(404);
        }

        return view('admin.voice-actors.edit', compact('voiceActor'));
    }

    /**
     * Update the voice actor profile
     */
    public function update(Request $request, User $voiceActor)
    {
        // Ensure user has eligible role
        if (!in_array($voiceActor->role, [
            User::ROLE_VOICE_ACTOR,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN
        ])) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/|unique:users,phone_number,' . $voiceActor->id,
            'status' => 'required|in:active,pending,blocked',
        ]);

        $voiceActor->update($validated);

        return redirect()->route('admin.voice-actors.show', $voiceActor)
            ->with('success', 'پروفایل صداپیشه با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Update the role of a voice actor
     */
    public function updateRole(Request $request, User $voiceActor)
    {
        $validated = $request->validate([
            'role' => 'required|in:' . User::ROLE_VOICE_ACTOR . ',' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN,
        ]);

        $voiceActor->update(['role' => $validated['role']]);

        return redirect()->back()
            ->with('success', 'نقش کاربر با موفقیت تغییر کرد.');
    }
}

