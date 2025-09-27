<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImageTimeline;
use App\Models\Story;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TimelineManagementController extends Controller
{
    /**
     * Display a listing of timelines.
     */
    public function index(Request $request)
    {
        $query = ImageTimeline::with(['episode']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('scene_description', 'like', "%{$search}%")
                  ->orWhereHas('episode', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by transition type
        if ($request->filled('transition_type')) {
            $query->where('transition_type', $request->transition_type);
        }

        // Filter by key frame
        if ($request->filled('is_key_frame')) {
            $query->where('is_key_frame', $request->boolean('is_key_frame'));
        }

        // Filter by episode
        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $timelines = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => ImageTimeline::count(),
            'active' => ImageTimeline::count(), // All image timelines are considered active
            'inactive' => 0, // No inactive status for image timelines
            'draft' => 0, // No draft status for image timelines
            'key_frames' => ImageTimeline::where('is_key_frame', true)->count(),
            'non_key_frames' => ImageTimeline::where('is_key_frame', false)->count(),
            'fade_transitions' => ImageTimeline::where('transition_type', 'fade')->count(),
            'cut_transitions' => ImageTimeline::where('transition_type', 'cut')->count(),
            'slide_transitions' => ImageTimeline::where('transition_type', 'slide')->count(),
            'dissolve_transitions' => ImageTimeline::where('transition_type', 'dissolve')->count(),
        ];

        $stories = Story::where('status', 'published')->get();
        $episodes = Episode::where('status', 'published')->get();

        return view('admin.timeline-management.index', compact('timelines', 'stats', 'stories', 'episodes'));
    }

    /**
     * Show the form for creating a new timeline.
     */
    public function create()
    {
        $stories = Story::where('status', 'published')->get();
        $episodes = Episode::where('status', 'published')->get();
        return view('admin.timeline-management.create', compact('stories', 'episodes'));
    }

    /**
     * Store a newly created timeline.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => 'required|exists:episodes,id',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'image_url' => 'required|string|max:500',
            'image_order' => 'required|integer|min:0',
            'scene_description' => 'nullable|string|max:1000',
            'transition_type' => 'nullable|string|in:fade,slide,cut,dissolve',
            'is_key_frame' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'episode_id' => $request->episode_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'image_url' => $request->image_url,
                'image_order' => $request->image_order,
                'scene_description' => $request->scene_description,
                'transition_type' => $request->transition_type ?? 'fade',
                'is_key_frame' => $request->boolean('is_key_frame', false),
            ];

            $timeline = ImageTimeline::create($data);

            DB::commit();

            return redirect()->route('admin.timeline-management.index')
                ->with('success', 'تایم‌لاین تصویر با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در ایجاد تایم‌لاین: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified timeline.
     */
    public function show(ImageTimeline $timeline)
    {
        $timeline->load(['episode', 'voiceActor']);
        
        // Get related timelines for the same episode
        $relatedTimelines = ImageTimeline::where('episode_id', $timeline->episode_id)
            ->where('id', '!=', $timeline->id)
            ->with(['episode'])
            ->orderBy('image_order')
            ->limit(5)
            ->get();

        return view('admin.timeline-management.show', compact('timeline', 'relatedTimelines'));
    }

    /**
     * Show the form for editing the specified timeline.
     */
    public function edit(ImageTimeline $timeline)
    {
        $stories = Story::where('status', 'published')->get();
        $episodes = Episode::where('status', 'published')->get();
        return view('admin.timeline-management.edit', compact('timeline', 'stories', 'episodes'));
    }

    /**
     * Update the specified timeline.
     */
    public function update(Request $request, ImageTimeline $timeline)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => 'required|exists:episodes,id',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'image_url' => 'required|string|max:500',
            'image_order' => 'required|integer|min:0',
            'scene_description' => 'nullable|string|max:1000',
            'transition_type' => 'nullable|string|in:fade,slide,cut,dissolve',
            'is_key_frame' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'episode_id' => $request->episode_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'image_url' => $request->image_url,
                'image_order' => $request->image_order,
                'scene_description' => $request->scene_description,
                'transition_type' => $request->transition_type ?? 'fade',
                'is_key_frame' => $request->boolean('is_key_frame', false),
            ];

            $timeline->update($data);

            DB::commit();

            return redirect()->route('admin.timeline-management.index')
                ->with('success', 'تایم‌لاین تصویر با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در به‌روزرسانی تایم‌لاین: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified timeline.
     */
    public function destroy(ImageTimeline $timeline)
    {
        try {
            $timeline->delete();
            return redirect()->route('admin.timeline-management.index')
                ->with('success', 'تایم‌لاین تصویر با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف تایم‌لاین: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on timelines.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete',
            'timeline_ids' => 'required|array|min:1',
            'timeline_ids.*' => 'exists:image_timelines,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک تایم‌لاین را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $timelines = ImageTimeline::whereIn('id', $request->timeline_ids);

            switch ($request->action) {
                case 'delete':
                    $timelines->delete();
                    break;
            }

            DB::commit();

            $actionLabels = [
                'delete' => 'حذف',
            ];

            return redirect()->back()
                ->with('success', 'عملیات ' . $actionLabels[$request->action] . ' با موفقیت انجام شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در انجام عملیات: ' . $e->getMessage()]);
        }
    }

    /**
     * Get timeline statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_timelines' => ImageTimeline::count(),
            'key_frames' => ImageTimeline::where('is_key_frame', true)->count(),
            'non_key_frames' => ImageTimeline::where('is_key_frame', false)->count(),
            'by_transition_type' => ImageTimeline::selectRaw('transition_type, COUNT(*) as count')
                ->groupBy('transition_type')
                ->get(),
            'timelines_by_episode' => ImageTimeline::selectRaw('episode_id, COUNT(*) as count')
                ->with('episode')
                ->groupBy('episode_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'timelines_by_month' => ImageTimeline::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_timelines' => ImageTimeline::with(['episode'])->orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return view('admin.timeline-management.statistics', compact('stats'));
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = ImageTimeline::with(['episode']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('scene_description', 'like', "%{$search}%")
                  ->orWhereHas('episode', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by transition type
        if ($request->filled('transition_type')) {
            $query->where('transition_type', $request->transition_type);
        }

        // Filter by key frame
        if ($request->filled('is_key_frame')) {
            $query->where('is_key_frame', $request->boolean('is_key_frame'));
        }

        // Filter by episode
        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $timelines = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $timelines->items(),
            'pagination' => [
                'current_page' => $timelines->currentPage(),
                'last_page' => $timelines->lastPage(),
                'per_page' => $timelines->perPage(),
                'total' => $timelines->total(),
            ]
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'image_url' => 'required|string|max:500',
            'image_order' => 'required|integer|min:0',
            'scene_description' => 'nullable|string|max:1000',
            'transition_type' => 'nullable|string|in:fade,slide,cut,dissolve',
            'is_key_frame' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $timeline = ImageTimeline::create([
                'episode_id' => $request->episode_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'image_url' => $request->image_url,
                'image_order' => $request->image_order,
                'scene_description' => $request->scene_description,
                'transition_type' => $request->transition_type ?? 'fade',
                'is_key_frame' => $request->boolean('is_key_frame', false),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین تصویر با موفقیت ایجاد شد.',
                'data' => $timeline->load(['episode'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating timeline: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShow(ImageTimeline $timeline)
    {
        $timeline->load(['episode']);

        return response()->json([
            'success' => true,
            'data' => $timeline
        ]);
    }

    public function apiUpdate(Request $request, ImageTimeline $timeline)
    {
        $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'image_url' => 'required|string|max:500',
            'image_order' => 'required|integer|min:0',
            'scene_description' => 'nullable|string|max:1000',
            'transition_type' => 'nullable|string|in:fade,slide,cut,dissolve',
            'is_key_frame' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $timeline->update([
                'episode_id' => $request->episode_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'image_url' => $request->image_url,
                'image_order' => $request->image_order,
                'scene_description' => $request->scene_description,
                'transition_type' => $request->transition_type ?? 'fade',
                'is_key_frame' => $request->boolean('is_key_frame', false),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین تصویر با موفقیت به‌روزرسانی شد.',
                'data' => $timeline->load(['episode'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating timeline: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDestroy(ImageTimeline $timeline)
    {
        try {
            DB::beginTransaction();

            $timeline->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین تصویر با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting timeline: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }
}
