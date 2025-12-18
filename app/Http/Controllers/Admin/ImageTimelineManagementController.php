<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImageTimeline;
use App\Models\Episode;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageTimelineManagementController extends Controller
{
    /**
     * Display a listing of image timelines
     */
    public function index(Request $request)
    {
        $query = ImageTimeline::with(['episode.story']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('episode', function($episodeQuery) use ($searchTerm) {
                    $episodeQuery->where('title', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('episode.story', function($storyQuery) use ($searchTerm) {
                    $storyQuery->where('title', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Filter by episode
        if ($request->has('episode_id') && $request->episode_id) {
            $query->where('episode_id', $request->episode_id);
        }

        // Filter by story
        if ($request->has('story_id') && $request->story_id) {
            $query->whereHas('episode', function($q) use ($request) {
                $q->where('story_id', $request->story_id);
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'start_time');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['start_time', 'end_time', 'image_order', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $imageTimelines = $query->paginate(20);

        // Get statistics
        $stats = [
            'total' => ImageTimeline::count(),
            'active' => ImageTimeline::where('status', 'active')->count(),
            'inactive' => ImageTimeline::where('status', 'inactive')->count(),
            'total_episodes' => ImageTimeline::distinct('episode_id')->count(),
            'total_stories' => ImageTimeline::distinct('episode_id')->count(),
            'total_duration' => ImageTimeline::sum('end_time') - ImageTimeline::sum('start_time'),
        ];

        // Get episodes and stories for filters
        $episodes = Episode::with('story')->orderBy('title')->get();
        $stories = Story::orderBy('title')->get();

        return view('admin.image-timelines.index', compact('imageTimelines', 'stats', 'episodes', 'stories'));
    }

    /**
     * Show the form for creating a new image timeline
     */
    public function create()
    {
        $episodes = Episode::with('story')->orderBy('title')->get();
        return view('admin.image-timelines.create', compact('episodes'));
    }

    /**
     * Store a newly created image timeline
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => 'required|exists:episodes,id',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'image_url' => 'required|url',
            'image_order' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
        ], [
            'episode_id.required' => 'انتخاب اپیزود الزامی است',
            'episode_id.exists' => 'اپیزود انتخاب شده وجود ندارد',
            'start_time.required' => 'زمان شروع الزامی است',
            'start_time.integer' => 'زمان شروع باید عدد باشد',
            'start_time.min' => 'زمان شروع نمی‌تواند منفی باشد',
            'end_time.required' => 'زمان پایان الزامی است',
            'end_time.integer' => 'زمان پایان باید عدد باشد',
            'end_time.min' => 'زمان پایان باید بزرگتر از صفر باشد',
            'image_url.required' => 'آدرس تصویر الزامی است',
            'image_url.url' => 'آدرس تصویر باید معتبر باشد',
            'image_order.integer' => 'ترتیب تصویر باید عدد باشد',
            'image_order.min' => 'ترتیب تصویر باید بزرگتر از صفر باشد',
            'status.required' => 'وضعیت الزامی است',
            'status.in' => 'وضعیت نامعتبر است',
            'description.max' => 'توضیحات نمی‌تواند بیش از 1000 کاراکتر باشد',
            'tags.max' => 'تگ‌ها نمی‌تواند بیش از 500 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate time range
        $episode = Episode::findOrFail($request->episode_id);
        if ($request->end_time > $episode->duration) {
            return redirect()->back()
                ->withErrors(['end_time' => 'زمان پایان نمی‌تواند بیشتر از مدت اپیزود باشد'])
                ->withInput();
        }

        if ($request->end_time <= $request->start_time) {
            return redirect()->back()
                ->withErrors(['end_time' => 'زمان پایان باید بزرگتر از زمان شروع باشد'])
                ->withInput();
        }

        $imageTimelineData = $request->only([
            'episode_id', 'start_time', 'end_time', 'image_url', 
            'image_order', 'status', 'description', 'tags'
        ]);

        // Set default image order if not provided
        if (!$imageTimelineData['image_order']) {
            $maxOrder = ImageTimeline::where('episode_id', $request->episode_id)->max('image_order') ?? 0;
            $imageTimelineData['image_order'] = $maxOrder + 1;
        }

        $imageTimeline = ImageTimeline::create($imageTimelineData);

        return redirect()->route('admin.image-timelines.index')
            ->with('success', 'تایم‌لاین تصویر با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified image timeline
     */
    public function show(ImageTimeline $imageTimeline)
    {
        $imageTimeline->load(['episode.story']);
        
        // Get related image timelines
        $relatedTimelines = ImageTimeline::where('episode_id', $imageTimeline->episode_id)
            ->where('id', '!=', $imageTimeline->id)
            ->with(['episode.story'])
            ->orderBy('start_time')
            ->get();

        return view('admin.image-timelines.show', compact('imageTimeline', 'relatedTimelines'));
    }

    /**
     * Show the form for editing the specified image timeline
     */
    public function edit(ImageTimeline $imageTimeline)
    {
        $episodes = Episode::with('story')->orderBy('title')->get();
        return view('admin.image-timelines.edit', compact('imageTimeline', 'episodes'));
    }

    /**
     * Update the specified image timeline
     */
    public function update(Request $request, ImageTimeline $imageTimeline)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => 'required|exists:episodes,id',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'image_url' => 'required|url',
            'image_order' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate time range
        $episode = Episode::findOrFail($request->episode_id);
        if ($request->end_time > $episode->duration) {
            return redirect()->back()
                ->withErrors(['end_time' => 'زمان پایان نمی‌تواند بیشتر از مدت اپیزود باشد'])
                ->withInput();
        }

        if ($request->end_time <= $request->start_time) {
            return redirect()->back()
                ->withErrors(['end_time' => 'زمان پایان باید بزرگتر از زمان شروع باشد'])
                ->withInput();
        }

        $imageTimelineData = $request->only([
            'episode_id', 'start_time', 'end_time', 'image_url', 
            'image_order', 'status', 'description', 'tags'
        ]);

        $imageTimeline->update($imageTimelineData);

        return redirect()->route('admin.image-timelines.index')
            ->with('success', 'تایم‌لاین تصویر با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified image timeline
     */
    public function destroy(ImageTimeline $imageTimeline)
    {
        $imageTimeline->delete();

        return redirect()->route('admin.image-timelines.index')
            ->with('success', 'تایم‌لاین تصویر با موفقیت حذف شد.');
    }

    /**
     * Toggle status
     */
    public function toggleStatus(ImageTimeline $imageTimeline)
    {
        $newStatus = $imageTimeline->status === 'active' ? 'inactive' : 'active';
        $imageTimeline->update(['status' => $newStatus]);
        
        $statusText = $newStatus === 'active' ? 'فعال شد' : 'غیرفعال شد';
        
        return response()->json([
            'success' => true,
            'message' => "تایم‌لاین تصویر {$statusText}",
            'newStatus' => $newStatus
        ]);
    }

    /**
     * Duplicate image timeline
     */
    public function duplicate(ImageTimeline $imageTimeline)
    {
        $newImageTimeline = $imageTimeline->replicate();
        $newImageTimeline->image_order = ImageTimeline::where('episode_id', $imageTimeline->episode_id)->max('image_order') + 1;
        $newImageTimeline->status = 'inactive';
        $newImageTimeline->save();

        return response()->json([
            'success' => true,
            'message' => 'تایم‌لاین تصویر با موفقیت کپی شد'
        ]);
    }

    /**
     * Export image timelines
     */
    public function export()
    {
        $imageTimelines = ImageTimeline::with(['episode.story'])->get();

        $csvData = [];
        $csvData[] = ['اپیزود', 'داستان', 'زمان شروع', 'زمان پایان', 'آدرس تصویر', 'ترتیب', 'وضعیت', 'توضیحات', 'تگ‌ها', 'تاریخ ایجاد'];

        foreach ($imageTimelines as $imageTimeline) {
            $csvData[] = [
                $imageTimeline->episode->title,
                $imageTimeline->episode->story->title,
                gmdate('H:i:s', $imageTimeline->start_time),
                gmdate('H:i:s', $imageTimeline->end_time),
                $imageTimeline->image_url,
                $imageTimeline->image_order,
                $imageTimeline->status === 'active' ? 'فعال' : 'غیرفعال',
                $imageTimeline->description,
                $imageTimeline->tags,
                $imageTimeline->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'image_timelines_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get image timeline statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => ImageTimeline::count(),
            'active' => ImageTimeline::where('status', 'active')->count(),
            'inactive' => ImageTimeline::where('status', 'inactive')->count(),
            'total_episodes' => ImageTimeline::distinct('episode_id')->count(),
            'total_stories' => ImageTimeline::distinct('episode_id')->count(),
            'total_duration' => ImageTimeline::sum('end_time') - ImageTimeline::sum('start_time'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete',
            'image_timeline_ids' => 'required|array|min:1',
            'image_timeline_ids.*' => 'exists:image_timelines,id'
        ]);

        $imageTimelineIds = $request->image_timeline_ids;
        $action = $request->action;
        $count = 0;

        switch ($action) {
            case 'activate':
                ImageTimeline::whereIn('id', $imageTimelineIds)->update(['status' => 'active']);
                $count = count($imageTimelineIds);
                $message = "{$count} تایم‌لاین تصویر فعال شد";
                break;
                
            case 'deactivate':
                ImageTimeline::whereIn('id', $imageTimelineIds)->update(['status' => 'inactive']);
                $count = count($imageTimelineIds);
                $message = "{$count} تایم‌لاین تصویر غیرفعال شد";
                break;
                
            case 'delete':
                ImageTimeline::whereIn('id', $imageTimelineIds)->delete();
                $count = count($imageTimelineIds);
                $message = "{$count} تایم‌لاین تصویر حذف شد";
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Get episodes for a story
     */
    public function getEpisodesForStory(Request $request)
    {
        $storyId = $request->story_id;
        $episodes = Episode::where('story_id', $storyId)->orderBy('title')->get();
        
        return response()->json([
            'success' => true,
            'episodes' => $episodes->map(function($episode) {
                return [
                    'id' => $episode->id,
                    'title' => $episode->title,
                    'duration' => $episode->duration
                ];
            })
        ]);
    }
}
