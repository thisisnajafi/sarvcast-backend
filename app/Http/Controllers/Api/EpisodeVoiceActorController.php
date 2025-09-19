<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EpisodeVoiceActorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EpisodeVoiceActorController extends Controller
{
    protected $voiceActorService;

    public function __construct(EpisodeVoiceActorService $voiceActorService)
    {
        $this->voiceActorService = $voiceActorService;
    }

    /**
     * Get voice actors for episode
     */
    public function getVoiceActors(int $episodeId): JsonResponse
    {
        $result = $this->voiceActorService->getVoiceActorsForEpisode($episodeId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Add voice actor to episode
     */
    public function addVoiceActor(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'role' => 'required|string|max:100',
            'character_name' => 'nullable|string|max:255',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'voice_description' => 'nullable|string|max:1000',
            'is_primary' => 'boolean'
        ], [
            'person_id.required' => 'انتخاب صداپیشه الزامی است',
            'person_id.exists' => 'صداپیشه انتخاب شده وجود ندارد',
            'role.required' => 'نقش صداپیشه الزامی است',
            'role.max' => 'نقش صداپیشه نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'character_name.max' => 'نام شخصیت نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'start_time.required' => 'زمان شروع الزامی است',
            'start_time.integer' => 'زمان شروع باید عدد باشد',
            'start_time.min' => 'زمان شروع نمی‌تواند منفی باشد',
            'end_time.required' => 'زمان پایان الزامی است',
            'end_time.integer' => 'زمان پایان باید عدد باشد',
            'end_time.min' => 'زمان پایان باید حداقل 1 باشد',
            'voice_description.max' => 'توضیحات صدا نمی‌تواند بیشتر از 1000 کاراکتر باشد'
        ]);

        $result = $this->voiceActorService->addVoiceActor($episodeId, $request->all());
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Update voice actor
     */
    public function updateVoiceActor(Request $request, int $episodeId, int $voiceActorId): JsonResponse
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'role' => 'required|string|max:100',
            'character_name' => 'nullable|string|max:255',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'voice_description' => 'nullable|string|max:1000',
            'is_primary' => 'boolean'
        ], [
            'person_id.required' => 'انتخاب صداپیشه الزامی است',
            'person_id.exists' => 'صداپیشه انتخاب شده وجود ندارد',
            'role.required' => 'نقش صداپیشه الزامی است',
            'role.max' => 'نقش صداپیشه نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'character_name.max' => 'نام شخصیت نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'start_time.required' => 'زمان شروع الزامی است',
            'start_time.integer' => 'زمان شروع باید عدد باشد',
            'start_time.min' => 'زمان شروع نمی‌تواند منفی باشد',
            'end_time.required' => 'زمان پایان الزامی است',
            'end_time.integer' => 'زمان پایان باید عدد باشد',
            'end_time.min' => 'زمان پایان باید حداقل 1 باشد',
            'voice_description.max' => 'توضیحات صدا نمی‌تواند بیشتر از 1000 کاراکتر باشد'
        ]);

        $result = $this->voiceActorService->updateVoiceActor($voiceActorId, $request->all());
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Delete voice actor
     */
    public function deleteVoiceActor(int $episodeId, int $voiceActorId): JsonResponse
    {
        $result = $this->voiceActorService->deleteVoiceActor($voiceActorId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get voice actor for specific time
     */
    public function getVoiceActorForTime(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'time' => 'required|integer|min:0'
        ], [
            'time.required' => 'زمان الزامی است',
            'time.integer' => 'زمان باید عدد باشد',
            'time.min' => 'زمان نمی‌تواند منفی باشد'
        ]);

        $result = $this->voiceActorService->getVoiceActorForTime($episodeId, $request->time);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get all voice actors at specific time
     */
    public function getVoiceActorsAtTime(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'time' => 'required|integer|min:0'
        ], [
            'time.required' => 'زمان الزامی است',
            'time.integer' => 'زمان باید عدد باشد',
            'time.min' => 'زمان نمی‌تواند منفی باشد'
        ]);

        $result = $this->voiceActorService->getVoiceActorsAtTime($episodeId, $request->time);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get voice actors by role
     */
    public function getVoiceActorsByRole(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|max:100'
        ], [
            'role.required' => 'نقش الزامی است',
            'role.string' => 'نقش باید متن باشد',
            'role.max' => 'نقش نمی‌تواند بیشتر از 100 کاراکتر باشد'
        ]);

        $result = $this->voiceActorService->getVoiceActorsByRole($episodeId, $request->role);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get voice actor statistics for episode
     */
    public function getVoiceActorStatistics(int $episodeId): JsonResponse
    {
        $result = $this->voiceActorService->getVoiceActorStatistics($episodeId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
