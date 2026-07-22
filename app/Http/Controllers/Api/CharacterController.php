<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CharacterRequest;
use App\Models\Character;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CharacterController extends Controller
{
    /**
     * Get all characters for a story
     *
     * @param Request $request
     * @param int $storyId
     * @return JsonResponse
     */
    public function index(Request $request, int $storyId): JsonResponse
    {
        $story = Story::findOrFail($storyId);

        // Backfill Character.image_url from story-editor production assets when present.
        try {
            app(\App\Services\StoryProductionImportService::class)
                ->syncCharacterImagesForDbStory($storyId);
        } catch (\Throwable $e) {
            \Log::warning('Character image sync from production assets failed', [
                'story_id' => $storyId,
                'error' => $e->getMessage(),
            ]);
        }

        $characters = Character::where('story_id', $storyId)
            ->with('voiceActor')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $characters
        ]);
    }

    /**
     * Create a new character for a story
     *
     * @param CharacterRequest $request
     * @param int $storyId
     * @return JsonResponse
     */
    public function store(CharacterRequest $request, int $storyId): JsonResponse
    {
        \Log::info('📝 CharacterController.store: Starting character creation', [
            'storyId' => $storyId,
            'route_storyId' => $request->route('storyId'),
            'route_story' => $request->route('story'),
            'request_story_id' => $request->input('story_id'),
            'request_name' => $request->input('name'),
            'request_description' => $request->input('description'),
            'request_voice_actor_id' => $request->input('voice_actor_id'),
            'has_image' => $request->hasFile('image'),
            'all_input' => $request->all(),
            'all_files' => $request->allFiles(),
        ]);

        // Use story_id from request if provided, otherwise use route parameter
        $finalStoryId = $request->input('story_id') ?? $storyId;

        \Log::info('📝 CharacterController.store: Using story ID', [
            'finalStoryId' => $finalStoryId,
            'from_request' => $request->input('story_id'),
            'from_route' => $storyId,
        ]);

        $story = Story::findOrFail($finalStoryId);

        $imageUrl = $request->image_url;

        // Handle image file upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($request->name) . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Ensure directory exists
            $directory = public_path('images/characters');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Move image to public/images/characters
            $image->move($directory, $imageName);

            // Store relative path
            $imageUrl = 'characters/' . $imageName;
        }

        \Log::info('📝 CharacterController.store: Creating character', [
            'story_id' => $finalStoryId,
            'name' => $request->name,
            'image_url' => $imageUrl,
            'voice_actor_id' => $request->voice_actor_id,
            'description' => $request->description,
        ]);

        $character = Character::create([
            'story_id' => $finalStoryId,
            'name' => $request->name,
            'image_url' => $imageUrl,
            'voice_actor_id' => $request->voice_actor_id,
            'description' => $request->description,
        ]);

        $character->load('voiceActor');

        \Log::info('✅ CharacterController.store: Character created successfully', [
            'character_id' => $character->id,
            'character_name' => $character->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'شخصیت با موفقیت ایجاد شد.',
            'data' => $character
        ], 201);
    }

    /**
     * Get a single character
     *
     * @param int $characterId
     * @return JsonResponse
     */
    public function show(int $characterId): JsonResponse
    {
        $character = Character::with(['story', 'voiceActor'])
            ->findOrFail($characterId);

        return response()->json([
            'success' => true,
            'data' => $character
        ]);
    }

    /**
     * Update a character
     *
     * @param CharacterRequest $request
     * @param int $characterId
     * @return JsonResponse
     */
    public function update(CharacterRequest $request, int $characterId): JsonResponse
    {
        $character = Character::findOrFail($characterId);

        $imageUrl = $request->image_url ?? $character->image_url;

        // Handle image file upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($character->image_url && !filter_var($character->image_url, FILTER_VALIDATE_URL)) {
                $oldImagePath = public_path('images/' . $character->image_url);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }

            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($request->name) . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Ensure directory exists
            $directory = public_path('images/characters');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Move image to public/images/characters
            $image->move($directory, $imageName);

            // Store relative path
            $imageUrl = 'characters/' . $imageName;
        }

        $character->update([
            'name' => $request->name,
            'image_url' => $imageUrl,
            'voice_actor_id' => $request->voice_actor_id,
            'description' => $request->description,
        ]);

        $character->load('voiceActor');

        return response()->json([
            'success' => true,
            'message' => 'شخصیت با موفقیت به‌روزرسانی شد.',
            'data' => $character
        ]);
    }

    /**
     * Delete a character
     *
     * @param int $characterId
     * @return JsonResponse
     */
    public function destroy(int $characterId): JsonResponse
    {
        $character = Character::findOrFail($characterId);
        $character->delete();

        return response()->json([
            'success' => true,
            'message' => 'شخصیت با موفقیت حذف شد.'
        ]);
    }

    /**
     * Assign voice actor to a character
     *
     * @param Request $request
     * @param int $characterId
     * @return JsonResponse
     */
    public function assignVoiceActor(Request $request, int $characterId): JsonResponse
    {
        $request->validate([
            'voice_actor_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if ($user && !in_array($user->role, [
                        User::ROLE_VOICE_ACTOR,
                        User::ROLE_ADMIN,
                        User::ROLE_SUPER_ADMIN
                    ])) {
                        $fail('کاربر انتخاب شده باید نقش صداپیشه، ادمین یا ادمین کل داشته باشد.');
                    }
                },
            ],
        ], [
            'voice_actor_id.required' => 'شناسه صداپیشه الزامی است',
            'voice_actor_id.exists' => 'کاربر انتخاب شده معتبر نیست',
        ]);

        $character = Character::findOrFail($characterId);
        $oldVoiceActorId = $character->voice_actor_id;
        $character->update(['voice_actor_id' => $request->voice_actor_id]);
        $character->load(['voiceActor', 'story']);

        // Send notifications
        $notificationService = app(\App\Services\NotificationService::class);
        
        // If voice actor was removed
        if ($oldVoiceActorId && !$character->voice_actor_id) {
            $oldVoiceActor = User::find($oldVoiceActorId);
            if ($oldVoiceActor) {
                $notificationService->sendVoiceActorRemovalNotification(
                    $oldVoiceActor,
                    'character',
                    [
                        'story_id' => $character->story_id,
                        'character_id' => $character->id,
                        'character_name' => $character->name,
                        'story_title' => $character->story->title ?? 'داستان'
                    ]
                );
            }
        }
        
        // If voice actor was assigned
        if ($character->voice_actor_id) {
            $voiceActor = User::find($character->voice_actor_id);
            if ($voiceActor) {
                $notificationService->sendVoiceActorAssignmentNotification(
                    $voiceActor,
                    'character',
                    [
                        'story_id' => $character->story_id,
                        'character_id' => $character->id,
                        'character_name' => $character->name,
                        'story_title' => $character->story->title ?? 'داستان'
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'صداپیشه با موفقیت به شخصیت اختصاص داده شد.',
            'data' => $character
        ]);
    }

    /**
     * Search voice actors by name
     * Includes voice actors, admins, and super admins (since admins can also be voice actors)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchVoiceActors(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'query.required' => 'جستجوی نام الزامی است',
            'query.min' => 'جستجو باید حداقل 2 کاراکتر باشد',
            'query.max' => 'جستجو نمی‌تواند بیشتر از 100 کاراکتر باشد',
        ]);

        $query = $request->input('query') ?? $request->get('query') ?? '';

        // Search for voice actors, admins, and super admins
        // Admins can also be voice actors and have profiles, so they should be included in the search
        $voiceActors = User::where(function($q) use ($query) {
                $q->where('first_name', 'like', '%' . $query . '%')
                  ->orWhere('last_name', 'like', '%' . $query . '%')
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $query . '%']);
            })
            ->whereIn('role', [
                User::ROLE_VOICE_ACTOR,
                User::ROLE_ADMIN,        // Admins can be voice actors
                User::ROLE_SUPER_ADMIN   // Super admins can be voice actors
            ])
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'profile_image_url', 'role')
            ->limit(20)
            ->get();

        $jsonResponse = response()->json([
            'success' => true,
            'data' => $voiceActors
        ]);
        // Disable caching for search results - they need to be retrieved immediately
        $jsonResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $jsonResponse->headers->set('Pragma', 'no-cache');
        $jsonResponse->headers->set('Expires', '0');
        return $jsonResponse;
    }
}
