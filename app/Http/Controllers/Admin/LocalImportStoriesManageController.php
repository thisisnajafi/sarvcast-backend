<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Services\LocalImportStoriesManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocalImportStoriesManageController extends Controller
{
    public function __construct(
        private readonly LocalImportStoriesManageService $manageService,
    ) {}

    public function deleteStory(Request $request): JsonResponse
    {
        $validated = $this->validateStoryRef($request);

        try {
            $result = $this->manageService->deleteStory(
                $validated['story_id'] ?? null,
                $validated['folder_name'] ?? null,
                $validated['story_slug'] ?? null,
            );

            return AdminApiResponse::success($result, 'Story deleted on server.');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function deleteEpisode(Request $request): JsonResponse
    {
        $validated = $this->validateEpisodeRef($request);

        try {
            $result = $this->manageService->deleteEpisode(
                $validated['episode_id'] ?? null,
                $validated['story_id'] ?? null,
                $validated['folder_name'] ?? null,
                $validated['story_slug'] ?? null,
                $validated['episode_number'] ?? null,
                $validated['episode_slug'] ?? null,
            );

            return AdminApiResponse::success($result, 'Episode deleted on server.');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function deleteScript(Request $request): JsonResponse
    {
        $validated = $this->validateEpisodeRef($request);

        try {
            $result = $this->manageService->deleteEpisodeScript(
                $validated['episode_id'] ?? null,
                $validated['story_id'] ?? null,
                $validated['folder_name'] ?? null,
                $validated['story_slug'] ?? null,
                $validated['episode_number'] ?? null,
                $validated['episode_slug'] ?? null,
            );

            return AdminApiResponse::success($result, 'Episode script removed on server.');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function deleteCharacter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'story_id' => ['nullable', 'integer', 'exists:stories,id'],
            'folder_name' => ['nullable', 'string', 'max:255'],
            'story_slug' => ['nullable', 'string', 'max:191'],
            'character_id' => ['nullable', 'integer', 'exists:characters,id'],
            'character_key' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $result = $this->manageService->deleteCharacter(
                $validated['character_id'] ?? null,
                $validated['story_id'] ?? null,
                $validated['folder_name'] ?? null,
                $validated['story_slug'] ?? null,
                $validated['character_key'] ?? null,
            );

            return AdminApiResponse::success($result, 'Character removed on server.');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function updateCharacters(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'story_id' => ['nullable', 'integer', 'exists:stories,id'],
            'folder_name' => ['nullable', 'string', 'max:255'],
            'story_slug' => ['nullable', 'string', 'max:191'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $temp = $request->file('file')->getRealPath();
        if (! is_string($temp) || $temp === '') {
            return response()->json(['success' => false, 'message' => 'Invalid upload.'], 422);
        }

        try {
            $result = $this->manageService->updateCharactersFile(
                $temp,
                $validated['story_id'] ?? null,
                $validated['folder_name'] ?? null,
                $validated['story_slug'] ?? null,
            );

            return AdminApiResponse::success($result, 'characters_and_objects.json updated on server.');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function updateScript(Request $request): JsonResponse
    {
        $validated = $this->validateEpisodeRef($request);
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $temp = $request->file('file')->getRealPath();
        if (! is_string($temp) || $temp === '') {
            return response()->json(['success' => false, 'message' => 'Invalid upload.'], 422);
        }

        try {
            $result = $this->manageService->updateEpisodeScript(
                $temp,
                $validated['episode_id'] ?? null,
                $validated['story_id'] ?? null,
                $validated['folder_name'] ?? null,
                $validated['story_slug'] ?? null,
                $validated['episode_number'] ?? null,
                $validated['episode_slug'] ?? null,
            );

            return AdminApiResponse::success($result, 'Episode script updated on server.');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function updatePrompts(Request $request): JsonResponse
    {
        $validated = $this->validateEpisodeRef($request);
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $temp = $request->file('file')->getRealPath();
        if (! is_string($temp) || $temp === '') {
            return response()->json(['success' => false, 'message' => 'Invalid upload.'], 422);
        }

        try {
            $result = $this->manageService->updateEpisodePrompts(
                $temp,
                $validated['episode_id'] ?? null,
                $validated['story_id'] ?? null,
                $validated['folder_name'] ?? null,
                $validated['story_slug'] ?? null,
                $validated['episode_number'] ?? null,
                $validated['episode_slug'] ?? null,
            );

            return AdminApiResponse::success($result, 'Episode image prompts updated on server.');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStoryRef(Request $request): array
    {
        $validated = $request->validate([
            'story_id' => ['nullable', 'integer', 'exists:stories,id'],
            'folder_name' => ['nullable', 'string', 'max:255'],
            'story_slug' => ['nullable', 'string', 'max:191'],
        ]);

        if (empty($validated['story_id']) && empty($validated['folder_name']) && empty($validated['story_slug'])) {
            throw new \InvalidArgumentException('Provide story_id, folder_name, or story_slug.');
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEpisodeRef(Request $request): array
    {
        $validated = $request->validate([
            'episode_id' => ['nullable', 'integer', 'exists:episodes,id'],
            'story_id' => ['nullable', 'integer', 'exists:stories,id'],
            'folder_name' => ['nullable', 'string', 'max:255'],
            'story_slug' => ['nullable', 'string', 'max:191'],
            'episode_number' => ['nullable', 'integer', 'min:1'],
            'episode_slug' => ['nullable', 'string', 'max:191'],
        ]);

        if (empty($validated['episode_id'])
            && empty($validated['episode_number'])
            && empty($validated['episode_slug'])) {
            throw new \InvalidArgumentException('Provide episode_id, episode_number, or episode_slug.');
        }

        if (empty($validated['episode_id'])
            && empty($validated['story_id'])
            && empty($validated['folder_name'])
            && empty($validated['story_slug'])) {
            throw new \InvalidArgumentException('Provide story reference: story_id, folder_name, or story_slug.');
        }

        return $validated;
    }

    private function failure(\Throwable $e): JsonResponse
    {
        Log::error('Local import manage failed', ['error' => $e->getMessage()]);

        $status = str_contains($e->getMessage(), 'not found') ? 404 : 422;

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'error' => 'MANAGE_FAILED',
        ], $status);
    }
}
