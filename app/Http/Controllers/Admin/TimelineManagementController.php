<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\ImageTimeline;
use Illuminate\Http\Request;

class TimelineManagementController extends Controller
{
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
}