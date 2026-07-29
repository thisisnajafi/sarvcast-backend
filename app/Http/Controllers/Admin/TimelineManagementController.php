<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminCsvExport;
use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Services\MediaLibraryService;
use App\Services\SyncTimelineFromScenesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TimelineManagementController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $mediaLibrary,
        private readonly SyncTimelineFromScenesService $syncFromScenes,
    ) {}

    private function validateTimelinePayload(Request $request, ?ImageTimeline $current = null): array
    {
        $validated = $request->validate([
            'story_id' => 'nullable|integer|exists:stories,id',
            'episode_id' => 'nullable|integer|exists:episodes,id',
            'voice_actor_id' => 'nullable|integer|exists:people,id',
            'character_id' => 'nullable|integer|exists:people,id',
            'scene_id' => 'nullable|integer',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:0',
            'image_url' => 'required|string|max:500',
            'image_order' => 'required|integer|min:1',
            'scene_description' => 'nullable|string|max:1000',
            'transition_type' => 'required|in:fade,cut,dissolve,slide',
            'is_key_frame' => 'boolean',
        ]);

        $start = (int) $validated['start_time'];
        $end = (int) $validated['end_time'];
        if ($end <= $start) {
            throw ValidationException::withMessages([
                'end_time' => ['زمان پایان باید بزرگ‌تر از زمان شروع باشد.'],
            ]);
        }

        $episodeId = isset($validated['episode_id']) ? (int) $validated['episode_id'] : null;
        if ($episodeId) {
            $episode = Episode::find($episodeId);
            if ($episode && (int) $episode->duration > 0) {
                // Episode duration is stored in minutes; timeline uses seconds.
                // Allow client-measured audio length when longer than stored minutes.
                $maxSeconds = (int) $episode->duration * 60;
                $audioSeconds = (int) $request->input('duration_seconds', 0);
                if ($audioSeconds > $maxSeconds) {
                    $maxSeconds = $audioSeconds;
                }
                if ($end > $maxSeconds) {
                    throw ValidationException::withMessages([
                        'end_time' => ["زمان پایان نباید از مدت اپیزود ({$maxSeconds} ثانیه) بیشتر باشد."],
                    ]);
                }
            }

            $overlapQuery = ImageTimeline::query()
                ->where('episode_id', $episodeId)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start);

            if ($current) {
                $overlapQuery->where('id', '!=', $current->id);
            }

            if ($overlapQuery->exists()) {
                throw ValidationException::withMessages([
                    'start_time' => ['این بازه زمانی با فریم‌های موجود همپوشانی دارد.'],
                    'end_time' => ['این بازه زمانی با فریم‌های موجود همپوشانی دارد.'],
                ]);
            }
        }

        $validated['is_key_frame'] = $request->boolean('is_key_frame', false);

        return $validated;
    }

    /**
     * Display all timelines across all episodes
     */
    public function index(Request $request)
    {
        $query = ImageTimeline::with(['episode.story'])
            ->orderBy('created_at', 'desc');

        // Filter by episode if provided
        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
        }

        // Filter by story if provided
        if ($request->filled('story_id')) {
            $query->whereHas('episode', function($q) use ($request) {
                $q->where('story_id', $request->story_id);
            });
        }

        // Filter by transition type
        if ($request->filled('transition_type')) {
            $query->where('transition_type', $request->transition_type);
        }

        // Filter by key frame status
        if ($request->filled('is_key_frame')) {
            $query->where('is_key_frame', $request->is_key_frame);
        }

        $timelines = $query->paginate(20);

        // Get filter options
        $episodes = Episode::with('story')->orderBy('title')->get();
        $stories = \App\Models\Story::orderBy('title')->get();
        $transitionTypes = ['fade', 'cut', 'dissolve', 'slide'];

        return view('admin.timelines.index', compact('timelines', 'episodes', 'stories', 'transitionTypes'));
    }

    /**
     * Show timeline statistics
     */
    public function statistics()
    {
        $stats = [
            'total_timelines' => ImageTimeline::count(),
            'total_episodes_with_timelines' => Episode::where('use_image_timeline', true)->count(),
            'key_frames_count' => ImageTimeline::where('is_key_frame', true)->count(),
            'transition_types' => ImageTimeline::selectRaw('transition_type, COUNT(*) as count')
                ->groupBy('transition_type')
                ->get(),
            'recent_timelines' => ImageTimeline::with(['episode.story'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'episodes_with_most_timelines' => Episode::with('story')
                ->withCount('imageTimelines')
                ->where('use_image_timeline', true)
                ->orderBy('image_timelines_count', 'desc')
                ->limit(10)
                ->get()
        ];

        return view('admin.timelines.statistics', compact('stats'));
    }

    /**
     * Bulk operations on timelines
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,change_transition,change_key_frame',
            'timeline_ids' => 'required|array|min:1',
            'timeline_ids.*' => 'integer|exists:image_timelines,id',
            'transition_type' => 'required_if:action,change_transition|in:fade,cut,dissolve,slide',
            'is_key_frame' => 'required_if:action,change_key_frame|boolean'
        ]);

        try {
            $timelineIds = $request->timeline_ids;
            $action = $request->action;
            $successCount = 0;

            switch ($action) {
                case 'delete':
                    foreach ($timelineIds as $timelineId) {
                        $timeline = ImageTimeline::find($timelineId);
                        if ($timeline) {
                            // Delete image file
                            if ($timeline->image_url && file_exists(public_path($timeline->image_url))) {
                                unlink(public_path($timeline->image_url));
                            }
                            $timeline->delete();
                            $successCount++;
                        }
                    }
                    break;

                case 'change_transition':
                    ImageTimeline::whereIn('id', $timelineIds)
                        ->update(['transition_type' => $request->transition_type]);
                    $successCount = count($timelineIds);
                    break;

                case 'change_key_frame':
                    ImageTimeline::whereIn('id', $timelineIds)
                        ->update(['is_key_frame' => $request->boolean('is_key_frame')]);
                    $successCount = count($timelineIds);
                    break;
            }

            return redirect()->route('admin.timelines.index')
                ->with('success', "عملیات {$action} روی {$successCount} تایم‌لاین انجام شد.");

        } catch (\Exception $e) {
            return redirect()->route('admin.timelines.index')
                ->with('error', 'خطا در انجام عملیات گروهی: ' . $e->getMessage());
        }
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = ImageTimeline::with(['episode.story']);

        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id)
                ->orderBy('start_time')
                ->orderBy('image_order');
        } else {
            $query->orderBy('created_at', 'desc');
        }


        if ($request->filled('story_id')) {
            $query->whereHas('episode', function ($q) use ($request) {
                $q->where('story_id', $request->story_id);
            });
        }

        if ($request->filled('transition_type')) {
            $query->where('transition_type', $request->transition_type);
        }

        if ($request->filled('is_key_frame')) {
            $query->where('is_key_frame', $request->boolean('is_key_frame'));
        }

        $timelines = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $timelines->items(),
            'pagination' => [
                'current_page' => $timelines->currentPage(),
                'last_page' => $timelines->lastPage(),
                'per_page' => $timelines->perPage(),
                'total' => $timelines->total(),
            ],
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $this->validateTimelinePayload($request);
        $timeline = ImageTimeline::create($validated);
        $this->mediaLibrary->syncUsageFor($timeline, 'image_url', $timeline->image_url);

        return response()->json([
            'success' => true,
            'message' => 'تایم‌لاین با موفقیت ایجاد شد.',
            'data' => $timeline->load(['episode.story']),
        ]);
    }

    /**
     * Sync timeline frames from uploaded scene production images (even split by audio duration).
     */
    public function apiSyncFromScenes(Request $request)
    {
        $validated = $request->validate([
            'episode_id' => 'required|integer|exists:episodes,id',
            'duration_seconds' => 'nullable|integer|min:1',
            'replace' => 'sometimes|boolean',
        ]);

        $episode = Episode::findOrFail((int) $validated['episode_id']);
        $durationSeconds = (int) ($validated['duration_seconds'] ?? 0);
        if ($durationSeconds < 1) {
            $durationSeconds = max(1, (int) $episode->duration * 60);
        }
        $replace = array_key_exists('replace', $validated)
            ? (bool) $validated['replace']
            : true;

        try {
            $result = $this->syncFromScenes->sync($episode, $durationSeconds, $replace);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Timeline sync from scenes failed', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'همگام‌سازی تایم‌لاین از صحنه‌ها ناموفق بود.',
            ], 500);
        }

        foreach ($result['frames'] as $frame) {
            $this->mediaLibrary->syncUsageFor($frame, 'image_url', $frame->image_url);
        }

        return response()->json([
            'success' => true,
            'message' => 'فریم‌های تایم‌لاین از تصاویر صحنه همگام شدند.',
            'data' => [
                'frames' => $result['frames'],
                'duration_seconds' => $result['duration_seconds'],
                'scene_count' => $result['scene_count'],
                'replaced' => $result['replaced'],
            ],
        ]);
    }

    public function apiUploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        try {
            $mediaAsset = $this->mediaLibrary->upload($request->file('image'), [
                'folder' => 'timeline',
                'uploaded_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تصویر تایم‌لاین با موفقیت آپلود شد.',
                'data' => [
                    'image_url' => $mediaAsset->url,
                    'thumbnail_url' => $mediaAsset->thumbnail_url,
                    'media_asset_id' => $mediaAsset->id,
                    'relative_path' => $mediaAsset->path,
                    'file_name' => $mediaAsset->original_name,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Timeline image upload failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'آپلود تصویر تایم‌لاین ناموفق بود.',
            ], 500);
        }
    }

    /**
     * Apply many frame range updates in one transaction, validating the final
     * episode timeline as a whole (avoids false overlaps from one-by-one PUT).
     */
    public function apiBatchUpdate(Request $request)
    {
        $validated = $request->validate([
            'episode_id' => 'required|integer|exists:episodes,id',
            'duration_seconds' => 'nullable|integer|min:1',
            'frames' => 'required|array|min:1',
            'frames.*.id' => 'required|integer|exists:image_timelines,id',
            'frames.*.start_time' => 'required|integer|min:0',
            'frames.*.end_time' => 'required|integer|min:0',
            'frames.*.image_url' => 'nullable|string|max:500',
            'frames.*.image_order' => 'nullable|integer|min:1',
            'frames.*.scene_description' => 'nullable|string|max:1000',
            'frames.*.transition_type' => 'nullable|in:fade,cut,dissolve,slide',
            'frames.*.is_key_frame' => 'nullable|boolean',
        ]);

        $episodeId = (int) $validated['episode_id'];
        $episode = Episode::findOrFail($episodeId);
        $incoming = collect($validated['frames']);
        $ids = $incoming->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->count() !== $incoming->count()) {
            throw ValidationException::withMessages([
                'frames' => ['شناسه فریم‌ها نباید تکراری باشد.'],
            ]);
        }

        $existing = ImageTimeline::query()
            ->where('episode_id', $episodeId)
            ->whereIn('id', $ids->all())
            ->get()
            ->keyBy('id');

        if ($existing->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'frames' => ['برخی فریم‌ها متعلق به این اپیزود نیستند یا پیدا نشدند.'],
            ]);
        }

        $maxSeconds = max(1, (int) $episode->duration * 60);
        $audioSeconds = (int) ($validated['duration_seconds'] ?? 0);
        if ($audioSeconds > $maxSeconds) {
            $maxSeconds = $audioSeconds;
        }

        $allRows = ImageTimeline::query()
            ->where('episode_id', $episodeId)
            ->get()
            ->keyBy('id');

        $proposed = [];
        foreach ($allRows as $id => $row) {
            $proposed[$id] = [
                'id' => (int) $id,
                'start' => (int) $row->start_time,
                'end' => (int) $row->end_time,
                'label' => trim((string) ($row->scene_description ?: '')) ?: "#{$id}",
            ];
        }

        foreach ($incoming as $frame) {
            $id = (int) $frame['id'];
            $start = (int) $frame['start_time'];
            $end = (int) $frame['end_time'];
            $label = trim((string) ($frame['scene_description'] ?? $proposed[$id]['label'] ?? '')) ?: "#{$id}";

            if ($end <= $start) {
                throw ValidationException::withMessages([
                    'frames' => ["فریم «{$label}» (#{$id}): پایان باید بزرگ‌تر از شروع باشد."],
                ]);
            }
            if ($end > $maxSeconds) {
                throw ValidationException::withMessages([
                    'frames' => ["فریم «{$label}» (#{$id}): پایان از مدت صوت ({$maxSeconds}ث) بیشتر است."],
                ]);
            }

            $proposed[$id]['start'] = $start;
            $proposed[$id]['end'] = $end;
            $proposed[$id]['label'] = $label;
        }

        $sorted = collect($proposed)->sortBy([['start', 'asc'], ['end', 'asc']])->values();
        for ($i = 0; $i < $sorted->count(); $i++) {
            for ($j = $i + 1; $j < $sorted->count(); $j++) {
                $a = $sorted[$i];
                $b = $sorted[$j];
                if ($b['start'] >= $a['end']) {
                    break;
                }
                if ($a['start'] < $b['end'] && $a['end'] > $b['start']) {
                    throw ValidationException::withMessages([
                        'frames' => [
                            "همپوشانی بین «{$a['label']}» (#{$a['id']}) و «{$b['label']}» (#{$b['id']}).",
                        ],
                    ]);
                }
            }
        }

        DB::transaction(function () use ($incoming, $existing) {
            foreach ($incoming as $frame) {
                $id = (int) $frame['id'];
                /** @var ImageTimeline $row */
                $row = $existing[$id];
                $payload = [
                    'start_time' => (int) $frame['start_time'],
                    'end_time' => (int) $frame['end_time'],
                ];
                if (array_key_exists('image_url', $frame) && $frame['image_url'] !== null && $frame['image_url'] !== '') {
                    $payload['image_url'] = $frame['image_url'];
                }
                if (array_key_exists('image_order', $frame) && $frame['image_order'] !== null) {
                    $payload['image_order'] = (int) $frame['image_order'];
                }
                if (array_key_exists('scene_description', $frame)) {
                    $payload['scene_description'] = $frame['scene_description'];
                }
                if (array_key_exists('transition_type', $frame) && $frame['transition_type']) {
                    $payload['transition_type'] = $frame['transition_type'];
                }
                if (array_key_exists('is_key_frame', $frame)) {
                    $payload['is_key_frame'] = (bool) $frame['is_key_frame'];
                }
                $row->update($payload);
            }
        });

        Cache::forget("episode_timeline_{$episodeId}");
        Cache::forget("episode_timeline_{$episodeId}_with_voice_actors");

        $updated = ImageTimeline::query()
            ->where('episode_id', $episodeId)
            ->whereIn('id', $ids->all())
            ->orderBy('start_time')
            ->orderBy('image_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'بازه‌های تایم‌لاین با موفقیت به‌صورت دسته‌ای ذخیره شدند.',
            'data' => [
                'updated_count' => $updated->count(),
                'frames' => $updated,
            ],
        ]);
    }

    public function apiShow(ImageTimeline $timeline)
    {
        return response()->json([
            'success' => true,
            'data' => $timeline->load(['episode.story']),
        ]);
    }

    public function apiUpdate(Request $request, ImageTimeline $timeline)
    {
        $validated = $this->validateTimelinePayload($request, $timeline);
        $timeline->update($validated);
        $this->mediaLibrary->syncUsageFor($timeline->fresh(), 'image_url', $timeline->image_url);

        return response()->json([
            'success' => true,
            'message' => 'تایم‌لاین با موفقیت به‌روزرسانی شد.',
            'data' => $timeline->load(['episode.story']),
        ]);
    }

    public function apiDestroy(ImageTimeline $timeline)
    {
        $timeline->delete();

        return response()->json([
            'success' => true,
            'message' => 'تایم‌لاین با موفقیت حذف شد.',
        ]);
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,change_transition,change_key_frame',
            'timeline_ids' => 'required|array|min:1',
            'timeline_ids.*' => 'integer|exists:image_timelines,id',
            'transition_type' => 'required_if:action,change_transition|in:fade,cut,dissolve,slide',
            'is_key_frame' => 'required_if:action,change_key_frame|boolean',
        ]);

        $timelineIds = $request->timeline_ids;
        $action = $request->action;

        if ($action === 'delete') {
            ImageTimeline::whereIn('id', $timelineIds)->delete();
        } elseif ($action === 'change_transition') {
            ImageTimeline::whereIn('id', $timelineIds)->update(['transition_type' => $request->transition_type]);
        } else {
            ImageTimeline::whereIn('id', $timelineIds)->update(['is_key_frame' => $request->boolean('is_key_frame')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'عملیات گروهی تایم‌لاین انجام شد.',
        ]);
    }

    public function apiStatistics()
    {
        $stats = [
            'total_timelines' => ImageTimeline::count(),
            'total_episodes_with_timelines' => Episode::where('use_image_timeline', true)->count(),
            'key_frames_count' => ImageTimeline::where('is_key_frame', true)->count(),
            'transition_types' => ImageTimeline::selectRaw('transition_type, COUNT(*) as count')->groupBy('transition_type')->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function apiExport(Request $request)
    {
        $query = ImageTimeline::with(['episode.story'])->orderByDesc('created_at');

        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
        }
        if ($request->filled('story_id')) {
            $query->whereHas('episode', fn ($q) => $q->where('story_id', $request->story_id));
        }
        if ($request->filled('transition_type')) {
            $query->where('transition_type', $request->transition_type);
        }
        if ($request->filled('is_key_frame')) {
            $query->where('is_key_frame', $request->boolean('is_key_frame'));
        }

        return AdminCsvExport::streamQuery(
            'timeline-management-'.now()->format('Y-m-d-His').'.csv',
            ['id', 'episode_id', 'story_title', 'episode_title', 'start_time', 'end_time', 'image_order', 'transition_type', 'is_key_frame', 'created_at'],
            $query,
            fn ($row) => [
                $row->id,
                $row->episode_id,
                $row->episode?->story?->title,
                $row->episode?->title,
                $row->start_time,
                $row->end_time,
                $row->image_order,
                $row->transition_type,
                $row->is_key_frame ? '1' : '0',
                $row->created_at?->toIso8601String(),
            ]
        );
    }
}