<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Timeline;
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
        $query = Timeline::with(['story', 'episode']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('story', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('episode', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by story
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
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
            'total' => Timeline::count(),
            'active' => Timeline::where('status', 'active')->count(),
            'inactive' => Timeline::where('status', 'inactive')->count(),
            'draft' => Timeline::where('status', 'draft')->count(),
            'story_timelines' => Timeline::where('type', 'story')->count(),
            'episode_timelines' => Timeline::where('type', 'episode')->count(),
            'character_timelines' => Timeline::where('type', 'character')->count(),
            'event_timelines' => Timeline::where('type', 'event')->count(),
        ];

        $stories = Story::where('is_active', true)->get();
        $episodes = Episode::where('is_active', true)->get();

        return view('admin.timeline-management.index', compact('timelines', 'stats', 'stories', 'episodes'));
    }

    /**
     * Show the form for creating a new timeline.
     */
    public function create()
    {
        $stories = Story::where('is_active', true)->get();
        $episodes = Episode::where('is_active', true)->get();
        return view('admin.timeline-management.create', compact('stories', 'episodes'));
    }

    /**
     * Store a newly created timeline.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:story,episode,character,event',
            'status' => 'required|in:draft,active,inactive',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'color' => 'nullable|string|max:7',
            'priority' => 'nullable|integer|min:1|max:10',
            'tags' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'status' => $request->status,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'color' => $request->color,
                'priority' => $request->priority,
                'tags' => $request->tags,
                'created_by' => auth()->id(),
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('timeline/images', $imageName, 'public');
                $data['image'] = $imagePath;
            }

            $timeline = Timeline::create($data);

            DB::commit();

            return redirect()->route('admin.timeline-management.index')
                ->with('success', 'تایم‌لاین با موفقیت ایجاد شد.');

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
    public function show(Timeline $timeline)
    {
        $timeline->load(['story', 'episode', 'createdBy']);
        
        // Get related timelines
        $relatedTimelines = Timeline::where('type', $timeline->type)
            ->where('id', '!=', $timeline->id)
            ->with(['story', 'episode'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.timeline-management.show', compact('timeline', 'relatedTimelines'));
    }

    /**
     * Show the form for editing the specified timeline.
     */
    public function edit(Timeline $timeline)
    {
        $stories = Story::where('is_active', true)->get();
        $episodes = Episode::where('is_active', true)->get();
        return view('admin.timeline-management.edit', compact('timeline', 'stories', 'episodes'));
    }

    /**
     * Update the specified timeline.
     */
    public function update(Request $request, Timeline $timeline)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:story,episode,character,event',
            'status' => 'required|in:draft,active,inactive',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'color' => 'nullable|string|max:7',
            'priority' => 'nullable|integer|min:1|max:10',
            'tags' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'status' => $request->status,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'color' => $request->color,
                'priority' => $request->priority,
                'tags' => $request->tags,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($timeline->image && Storage::disk('public')->exists($timeline->image)) {
                    Storage::disk('public')->delete($timeline->image);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('timeline/images', $imageName, 'public');
                $data['image'] = $imagePath;
            }

            $timeline->update($data);

            DB::commit();

            return redirect()->route('admin.timeline-management.index')
                ->with('success', 'تایم‌لاین با موفقیت به‌روزرسانی شد.');

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
    public function destroy(Timeline $timeline)
    {
        try {
            // Delete associated image
            if ($timeline->image && Storage::disk('public')->exists($timeline->image)) {
                Storage::disk('public')->delete($timeline->image);
            }

            $timeline->delete();
            return redirect()->route('admin.timeline-management.index')
                ->with('success', 'تایم‌لاین با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف تایم‌لاین: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle timeline status.
     */
    public function toggleStatus(Timeline $timeline)
    {
        try {
            $newStatus = $timeline->status === 'active' ? 'inactive' : 'active';
            $timeline->update(['status' => $newStatus]);

            $statusLabel = $newStatus === 'active' ? 'فعال' : 'غیرفعال';
            return redirect()->back()
                ->with('success', "تایم‌لاین {$statusLabel} شد.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در تغییر وضعیت: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on timelines.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'timeline_ids' => 'required|array|min:1',
            'timeline_ids.*' => 'exists:timelines,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک تایم‌لاین را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $timelines = Timeline::whereIn('id', $request->timeline_ids);

            switch ($request->action) {
                case 'activate':
                    $timelines->update(['status' => 'active']);
                    break;

                case 'deactivate':
                    $timelines->update(['status' => 'inactive']);
                    break;

                case 'delete':
                    // Delete associated images
                    foreach ($timelines->get() as $timeline) {
                        if ($timeline->image && Storage::disk('public')->exists($timeline->image)) {
                            Storage::disk('public')->delete($timeline->image);
                        }
                    }
                    $timelines->delete();
                    break;
            }

            DB::commit();

            $actionLabels = [
                'activate' => 'فعال‌سازی',
                'deactivate' => 'غیرفعال‌سازی',
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
            'total_timelines' => Timeline::count(),
            'active_timelines' => Timeline::where('status', 'active')->count(),
            'inactive_timelines' => Timeline::where('status', 'inactive')->count(),
            'draft_timelines' => Timeline::where('status', 'draft')->count(),
            'by_type' => Timeline::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'by_status' => Timeline::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'timelines_by_month' => Timeline::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_timelines' => Timeline::with(['story', 'episode'])->orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return view('admin.timeline-management.statistics', compact('stats'));
    }
}
