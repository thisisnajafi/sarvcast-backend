<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Character;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterAdminController extends Controller
{
    /**
     * List all characters with optional search, story_id filter, and pagination.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Character::with(['story:id,title', 'voiceActor:id,first_name,last_name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('story_id')) {
            $query->where('story_id', $request->integer('story_id'));
        }

        $perPage = min((int) ($request->input('per_page', 15)), 100);
        $characters = $query->orderBy('id', 'desc')->paginate($perPage);

        return AdminApiResponse::paginated($characters);
    }

    /**
     * Show a single character.
     */
    public function apiShow(Character $character): JsonResponse
    {
        $character->load(['story:id,title', 'voiceActor:id,first_name,last_name']);

        return AdminApiResponse::success($character);
    }

    /**
     * Create a new character.
     */
    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate(Character::getValidationRules(), Character::getValidationMessages());

        $character = Character::create($validated);
        $character->load(['story:id,title', 'voiceActor:id,first_name,last_name']);

        return response()->json(['success' => true, 'data' => $character, 'message' => 'شخصیت با موفقیت ایجاد شد.'], 201);
    }

    /**
     * Update an existing character.
     */
    public function apiUpdate(Request $request, Character $character): JsonResponse
    {
        $validated = $request->validate(
            Character::getValidationRules($character->id),
            Character::getValidationMessages()
        );

        $character->update($validated);
        $character->load(['story:id,title', 'voiceActor:id,first_name,last_name']);

        return AdminApiResponse::success($character, 'شخصیت با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Delete a character.
     */
    public function apiDestroy(Character $character): JsonResponse
    {
        $name = $character->name;
        $character->delete();

        return AdminApiResponse::success(['name' => $name], 'شخصیت با موفقیت حذف شد.');
    }

    /**
     * Return all stories (id + title) for dropdowns.
     */
    public function apiStories(Request $request): JsonResponse
    {
        $stories = Story::select('id', 'title')
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('title')
            ->limit(200)
            ->get();

        return AdminApiResponse::success($stories);
    }
}
