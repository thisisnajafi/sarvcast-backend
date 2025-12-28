<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class VoiceActorPanelController extends Controller
{
    /**
     * Get stories where user is narrator or voice actor
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStories(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Get stories where user is narrator
        $narratorStories = Story::whereNarrator($user->id)
            ->with(['category', 'author', 'characters.voiceActor'])
            ->get();

        // Get stories where user is voice actor (through characters)
        $voiceActorStories = Story::whereVoiceActor($user->id)
            ->with(['category', 'author', 'narrator', 'characters.voiceActor'])
            ->get();

        // Merge and remove duplicates
        $allStories = $narratorStories->merge($voiceActorStories)->unique('id');

        // Transform stories for response
        $stories = $allStories->map(function ($story) use ($user) {
            return [
                'id' => $story->id,
                'title' => $story->title,
                'subtitle' => $story->subtitle,
                'image_url' => $story->image_url,
                'workflow_status' => $story->workflow_status,
                'workflow_status_label' => $story->workflow_status_label,
                'status' => $story->status,
                'is_published' => $story->status === 'published',
                'category' => $story->category,
                'author' => $story->author,
                'narrator' => $story->narrator,
                'characters' => $story->characters->map(function ($character) {
                    return [
                        'id' => $character->id,
                        'name' => $character->name,
                        'image_url' => $character->image_url,
                        'voice_actor' => $character->voiceActor ? [
                            'id' => $character->voiceActor->id,
                            'name' => $character->voiceActor->first_name . ' ' . $character->voiceActor->last_name,
                            'profile_image_url' => $character->voiceActor->profile_image_url,
                        ] : null,
                    ];
                }),
                'user_role' => $story->narrator_id === $user->id ? 'narrator' : 'voice_actor',
                'total_episodes' => $story->episodes()->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stories->values()
        ]);
    }

    /**
     * Get single story with full details including episodes and scripts
     * 
     * @param Request $request
     * @param Story $story
     * @return JsonResponse
     */
    public function getStoryDetails(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Check if user is narrator or voice actor for this story
        $isNarrator = $story->narrator_id === $user->id;
        $isVoiceActor = $story->characters()->where('voice_actor_id', $user->id)->exists();

        if (!$isNarrator && !$isVoiceActor) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این داستان را ندارید.'
            ], 403);
        }

        // Load relationships
        $story->load([
            'category',
            'author',
            'narrator',
            'characters.voiceActor',
            'episodes' => function ($query) {
                $query->orderBy('episode_number', 'asc');
            }
        ]);

        // Get episodes with script content
        $episodes = $story->episodes->map(function ($episode) {
            $scriptContent = null;
            
            if ($episode->script_file_url) {
                $scriptContent = $this->getScriptContent($episode->script_file_url);
            }

            return [
                'id' => $episode->id,
                'title' => $episode->title,
                'description' => $episode->description,
                'episode_number' => $episode->episode_number,
                'duration' => $episode->duration,
                'script_file_url' => $episode->script_file_url,
                'script_content' => $scriptContent,
                'status' => $episode->status,
                'is_premium' => $episode->is_premium,
            ];
        });

        // Get characters
        $characters = $story->characters->map(function ($character) {
            return [
                'id' => $character->id,
                'name' => $character->name,
                'image_url' => $character->image_url,
                'description' => $character->description,
                'voice_actor' => $character->voiceActor ? [
                    'id' => $character->voiceActor->id,
                    'name' => $character->voiceActor->first_name . ' ' . $character->voiceActor->last_name,
                    'profile_image_url' => $character->voiceActor->profile_image_url,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'story' => [
                    'id' => $story->id,
                    'title' => $story->title,
                    'subtitle' => $story->subtitle,
                    'description' => $story->description,
                    'image_url' => $story->image_url,
                    'workflow_status' => $story->workflow_status,
                    'workflow_status_label' => $story->workflow_status_label,
                    'status' => $story->status,
                    'is_published' => $story->status === 'published',
                    'category' => $story->category,
                ],
                'author' => $story->author ? [
                    'id' => $story->author->id,
                    'name' => $story->author->first_name . ' ' . $story->author->last_name,
                    'profile_image_url' => $story->author->profile_image_url,
                ] : null,
                'narrator' => $story->narrator ? [
                    'id' => $story->narrator->id,
                    'name' => $story->narrator->first_name . ' ' . $story->narrator->last_name,
                    'profile_image_url' => $story->narrator->profile_image_url,
                ] : null,
                'characters' => $characters,
                'episodes' => $episodes,
            ]
        ]);
    }

    /**
     * Get episode script content
     * 
     * @param Request $request
     * @param Story $story
     * @param Episode $episode
     * @return JsonResponse
     */
    public function getEpisodeScript(Request $request, Story $story, Episode $episode): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Verify episode belongs to story
        if ($episode->story_id !== $story->id) {
            return response()->json([
                'success' => false,
                'message' => 'اپیزود به این داستان تعلق ندارد.'
            ], 400);
        }

        // Check if user is narrator or voice actor for this story
        $isNarrator = $story->narrator_id === $user->id;
        $isVoiceActor = $story->characters()->where('voice_actor_id', $user->id)->exists();

        if (!$isNarrator && !$isVoiceActor) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این اپیزود را ندارید.'
            ], 403);
        }

        if (!$episode->script_file_url) {
            return response()->json([
                'success' => false,
                'message' => 'فایل اسکریپت برای این اپیزود موجود نیست.'
            ], 404);
        }

        $scriptContent = $this->getScriptContent($episode->script_file_url);

        if ($scriptContent === null) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در خواندن فایل اسکریپت.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'episode_id' => $episode->id,
                'episode_title' => $episode->title,
                'episode_number' => $episode->episode_number,
                'script_file_url' => $episode->script_file_url,
                'script_content' => $scriptContent,
            ]
        ]);
    }

    /**
     * Read script content from file URL
     * 
     * @param string $fileUrl
     * @return string|null
     */
    private function getScriptContent(string $fileUrl): ?string
    {
        try {
            // Extract path from URL
            // URL format: /storage/stories/scripts/filename.md or /storage/episodes/scripts/filename.md
            $path = str_replace('/storage/', '', parse_url($fileUrl, PHP_URL_PATH));
            
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->get($path);
            }

            // Try alternative path extraction
            $path = ltrim(parse_url($fileUrl, PHP_URL_PATH), '/');
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->get($path);
            }

            \Log::warning('Script file not found', [
                'file_url' => $fileUrl,
                'extracted_path' => $path
            ]);

            return null;
        } catch (\Exception $e) {
            \Log::error('Error reading script file', [
                'file_url' => $fileUrl,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
