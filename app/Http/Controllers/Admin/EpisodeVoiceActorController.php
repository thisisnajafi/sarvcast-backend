<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\EpisodeVoiceActor;
use App\Models\Person;
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
     * Display voice actors for episode
     */
    public function index(int $episodeId)
    {
        $episode = Episode::with(['story', 'voiceActors.person'])->findOrFail($episodeId);
        
        return view('admin.episodes.voice-actors.index', compact('episode'));
    }

    /**
     * Show the form for creating a new voice actor
     */
    public function create(int $episodeId)
    {
        $episode = Episode::findOrFail($episodeId);
        $people = Person::orderBy('name')->get();
        
        return view('admin.episodes.voice-actors.create', compact('episode', 'people'));
    }

    /**
     * Store a newly created voice actor
     */
    public function store(Request $request, int $episodeId)
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
            'start_time.required' => 'زمان شروع الزامی است',
            'end_time.required' => 'زمان پایان الزامی است'
        ]);

        $result = $this->voiceActorService->addVoiceActor($episodeId, $request->all());

        if ($result['success']) {
            return redirect()
                ->route('admin.episodes.voice-actors.index', $episodeId)
                ->with('success', $result['message']);
        } else {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }
    }

    /**
     * Show the form for editing the specified voice actor
     */
    public function edit(int $episodeId, int $voiceActorId)
    {
        $episode = Episode::findOrFail($episodeId);
        $voiceActor = EpisodeVoiceActor::with('person')->findOrFail($voiceActorId);
        $people = Person::orderBy('name')->get();
        
        return view('admin.episodes.voice-actors.edit', compact('episode', 'voiceActor', 'people'));
    }

    /**
     * Update the specified voice actor
     */
    public function update(Request $request, int $episodeId, int $voiceActorId)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'role' => 'required|string|max:100',
            'character_name' => 'nullable|string|max:255',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'voice_description' => 'nullable|string|max:1000',
            'is_primary' => 'boolean'
        ]);

        $result = $this->voiceActorService->updateVoiceActor($voiceActorId, $request->all());

        if ($result['success']) {
            return redirect()
                ->route('admin.episodes.voice-actors.index', $episodeId)
                ->with('success', $result['message']);
        } else {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }
    }

    /**
     * Remove the specified voice actor
     */
    public function destroy(int $episodeId, int $voiceActorId)
    {
        $result = $this->voiceActorService->deleteVoiceActor($voiceActorId);

        if ($result['success']) {
            return redirect()
                ->route('admin.episodes.voice-actors.index', $episodeId)
                ->with('success', $result['message']);
        } else {
            return back()
                ->with('error', $result['message']);
        }
    }

    /**
     * Get voice actors data for AJAX requests
     */
    public function getVoiceActorsData(int $episodeId): JsonResponse
    {
        $result = $this->voiceActorService->getVoiceActorsForEpisode($episodeId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get voice actor statistics for AJAX requests
     */
    public function getVoiceActorStatistics(int $episodeId): JsonResponse
    {
        $result = $this->voiceActorService->getVoiceActorStatistics($episodeId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Bulk actions for voice actors
     */
    public function bulkAction(Request $request, int $episodeId)
    {
        $request->validate([
            'action' => 'required|string|in:delete,update_primary',
            'voice_actor_ids' => 'required|array|min:1',
            'voice_actor_ids.*' => 'exists:episode_voice_actors,id'
        ]);

        $action = $request->action;
        $voiceActorIds = $request->voice_actor_ids;

        try {
            switch ($action) {
                case 'delete':
                    foreach ($voiceActorIds as $voiceActorId) {
                        $this->voiceActorService->deleteVoiceActor($voiceActorId);
                    }
                    $message = 'صداپیشگان انتخاب شده با موفقیت حذف شدند';
                    break;

                case 'update_primary':
                    // Remove primary status from all voice actors
                    EpisodeVoiceActor::where('episode_id', $episodeId)->update(['is_primary' => false]);
                    
                    // Set first selected voice actor as primary
                    EpisodeVoiceActor::whereIn('id', $voiceActorIds)
                        ->where('episode_id', $episodeId)
                        ->first()
                        ?->update(['is_primary' => true]);
                    
                    $message = 'صداپیشه اصلی با موفقیت به‌روزرسانی شد';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available people for voice actor selection
     */
    public function getAvailablePeople(Request $request): JsonResponse
    {
        $query = Person::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('role')) {
            $query->whereJsonContains('roles', $request->role);
        }

        $people = $query->orderBy('name')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $people->map(function($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->name,
                    'image_url' => $person->image_url,
                    'roles' => $person->roles,
                    'is_verified' => $person->is_verified
                ];
            })
        ]);
    }

    /**
     * Validate voice actor time range
     */
    public function validateTimeRange(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'person_id' => 'required|exists:people,id',
            'exclude_id' => 'nullable|exists:episode_voice_actors,id'
        ]);

        try {
            $episode = Episode::findOrFail($episodeId);
            
            // Check if times are within episode duration
            if ($request->start_time >= $request->end_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'زمان شروع باید کمتر از زمان پایان باشد'
                ]);
            }

            if ($request->end_time > $episode->duration) {
                return response()->json([
                    'success' => false,
                    'message' => 'زمان پایان نمی‌تواند بیشتر از مدت زمان قسمت باشد'
                ]);
            }

            // Check for overlaps
            $query = EpisodeVoiceActor::where('episode_id', $episodeId)
                ->where('person_id', $request->person_id)
                ->where(function($q) use ($request) {
                    $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function($q2) use ($request) {
                          $q2->where('start_time', '<=', $request->start_time)
                             ->where('end_time', '>=', $request->end_time);
                      });
                });

            if ($request->exclude_id) {
                $query->where('id', '!=', $request->exclude_id);
            }

            $overlapping = $query->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'زمان مشخص شده با صداپیشه دیگری تداخل دارد'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'زمان مشخص شده معتبر است'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی: ' . $e->getMessage()
            ], 500);
        }
    }
}
