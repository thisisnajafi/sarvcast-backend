<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\ImageTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TimelineManagementController extends Controller
{
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
                $maxSeconds = (int) $episode->duration * 60;
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
        $query = ImageTimeline::with(['episode.story'])->orderBy('created_at', 'desc');

        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
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

        return response()->json([
            'success' => true,
            'message' => 'تایم‌لاین با موفقیت ایجاد شد.',
            'data' => $timeline->load(['episode.story']),
        ]);
    }

    public function apiUploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        try {
            $image = $request->file('image');
            $directory = public_path('images/timeline');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = now()->format('YmdHis') . '_' . uniqid('timeline_', true) . '.' . $image->getClientOriginalExtension();
            $image->move($directory, $filename);

            $relativePath = '/images/timeline/' . $filename;
            $publicUrl = url($relativePath);

            return response()->json([
                'success' => true,
                'message' => 'تصویر تایم‌لاین با موفقیت آپلود شد.',
                'data' => [
                    'image_url' => $publicUrl,
                    'relative_path' => $relativePath,
                    'file_name' => $filename,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Timeline image upload failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'آپلود تصویر تایم‌لاین ناموفق بود.',
            ], 500);
        }
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
}