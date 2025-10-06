<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EpisodeTimelineController extends Controller
{
    protected $imageProcessingService;

    public function __construct(ImageProcessingService $imageProcessingService)
    {
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Display timeline for an episode
     */
    public function index(Episode $episode)
    {
        $timelines = $episode->imageTimelines()->orderBy('image_order')->get();
        
        return view('admin.episodes.timeline.index', compact('episode', 'timelines'));
    }

    /**
     * Show form to create new timeline entry
     */
    public function create(Episode $episode)
    {
        return view('admin.episodes.timeline.create', compact('episode'));
    }

    /**
     * Store new timeline entry
     */
    public function store(Request $request, Episode $episode)
    {
        $request->validate([
            'image_timeline_data' => 'required|string',
        ], [
            'image_timeline_data.required' => 'داده‌های تایم‌لاین الزامی است',
        ]);

        try {
            DB::beginTransaction();

            $imageTimelineData = json_decode($request->image_timeline_data, true);
            
            if (empty($imageTimelineData)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'لطفاً حداقل یک تصویر برای تایم‌لاین اضافه کنید.');
            }

            foreach ($imageTimelineData as $index => $timelineData) {
                // Validate timeline doesn't overlap with existing entries
                $overlappingTimeline = $episode->imageTimelines()
                    ->where(function($query) use ($timelineData) {
                        $query->whereBetween('start_time', [$timelineData['start_time'], $timelineData['end_time']])
                              ->orWhereBetween('end_time', [$timelineData['start_time'], $timelineData['end_time']])
                              ->orWhere(function($q) use ($timelineData) {
                                  $q->where('start_time', '<=', $timelineData['start_time'])
                                    ->where('end_time', '>=', $timelineData['end_time']);
                              });
                    })
                    ->first();

                if ($overlappingTimeline) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "تایم‌لاین شماره " . ($index + 1) . " با تایم‌لاین موجود تداخل دارد.");
                }

                // Handle image upload - get the actual file from request
                $imageFile = null;
                
                // Look for the file in the request with the correct index
                $fileKey = 'timeline_image_' . $index;
                if ($request->hasFile($fileKey)) {
                    $imageFile = $request->file($fileKey);
                }
                
                if (!$imageFile) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "فایل تصویر برای تایم‌لاین شماره " . ($index + 1) . " یافت نشد.");
                }
                
                $imagePath = $this->handleImageUpload($imageFile, $request);

                // Create timeline entry
                $episode->imageTimelines()->create([
                    'start_time' => $timelineData['start_time'],
                    'end_time' => $timelineData['end_time'],
                    'image_url' => $imagePath,
                    'image_order' => $timelineData['image_order'],
                    'scene_description' => $timelineData['scene_description'],
                    'transition_type' => $timelineData['transition_type'],
                    'is_key_frame' => $timelineData['is_key_frame'],
                ]);
            }

            // Update episode to use image timeline
            $episode->update(['use_image_timeline' => true]);

            DB::commit();

            return redirect()->route('admin.episodes.timeline.index', $episode)
                ->with('success', count($imageTimelineData) . ' تایم‌لاین با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create timeline', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'خطا در ایجاد تایم‌لاین: ' . $e->getMessage());
        }
    }

    /**
     * Show form to edit timeline entry
     */
    public function edit(Episode $episode, ImageTimeline $timeline)
    {
        return view('admin.episodes.timeline.edit', compact('episode', 'timeline'));
    }

    /**
     * Update timeline entry
     */
    public function update(Request $request, Episode $episode, ImageTimeline $timeline)
    {
        $request->validate([
            'start_time' => 'required|integer|min:0|max:' . $episode->duration,
            'end_time' => 'required|integer|min:1|max:' . $episode->duration,
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'scene_description' => 'nullable|string|max:1000',
            'transition_type' => 'required|in:fade,cut,dissolve,slide',
            'is_key_frame' => 'boolean',
            'image_order' => 'required|integer|min:1',
            'resize_image' => 'boolean',
            'image_width' => 'nullable|integer|min:100|max:2000',
            'image_height' => 'nullable|integer|min:100|max:2000',
        ]);

        try {
            DB::beginTransaction();

            // Validate timeline doesn't overlap with other entries (excluding current)
            $overlappingTimeline = $episode->imageTimelines()
                ->where('id', '!=', $timeline->id)
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                          ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                          ->orWhere(function($q) use ($request) {
                              $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                          });
                })
                ->first();

            if ($overlappingTimeline) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'این بازه زمانی با تایم‌لاین موجود تداخل دارد.');
            }

            $data = $request->except(['image_file', 'resize_image', 'image_width', 'image_height']);

            // Handle image upload if new image provided
            if ($request->hasFile('image_file')) {
                // Delete old image
                if ($timeline->image_url && file_exists(public_path($timeline->image_url))) {
                    unlink(public_path($timeline->image_url));
                }

                $imageFile = $request->file('image_file');
                $data['image_url'] = $this->handleImageUpload($imageFile, $request);
            }

            $timeline->update($data);

            DB::commit();

            return redirect()->route('admin.episodes.timeline.index', $episode)
                ->with('success', 'تایم‌لاین با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update timeline', [
                'timeline_id' => $timeline->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'خطا در به‌روزرسانی تایم‌لاین: ' . $e->getMessage());
        }
    }

    /**
     * Delete timeline entry
     */
    public function destroy(Episode $episode, ImageTimeline $timeline)
    {
        try {
            DB::beginTransaction();

            // Delete associated image file
            if ($timeline->image_url && file_exists(public_path($timeline->image_url))) {
                unlink(public_path($timeline->image_url));
            }

            $timeline->delete();

            // Update episode if no more timelines
            if ($episode->imageTimelines()->count() === 0) {
                $episode->update(['use_image_timeline' => false]);
            }

            DB::commit();

            return redirect()->route('admin.episodes.timeline.index', $episode)
                ->with('success', 'تایم‌لاین با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete timeline', [
                'timeline_id' => $timeline->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.episodes.timeline.index', $episode)
                ->with('error', 'خطا در حذف تایم‌لاین: ' . $e->getMessage());
        }
    }

    /**
     * Bulk operations on timeline entries
     */
    public function bulkAction(Request $request, Episode $episode)
    {
        $request->validate([
            'action' => 'required|in:delete,reorder,change_transition',
            'timeline_ids' => 'required|array|min:1',
            'timeline_ids.*' => 'integer|exists:image_timelines,id',
            'transition_type' => 'required_if:action,change_transition|in:fade,cut,dissolve,slide',
            'order_data' => 'required_if:action,reorder|array'
        ]);

        try {
            DB::beginTransaction();

            $timelineIds = $request->timeline_ids;
            $action = $request->action;
            $successCount = 0;

            switch ($action) {
                case 'delete':
                    foreach ($timelineIds as $timelineId) {
                        $timeline = ImageTimeline::find($timelineId);
                        if ($timeline && $timeline->episode_id === $episode->id) {
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
                        ->where('episode_id', $episode->id)
                        ->update(['transition_type' => $request->transition_type]);
                    $successCount = count($timelineIds);
                    break;

                case 'reorder':
                    $orderData = $request->order_data;
                    foreach ($orderData as $orderItem) {
                        if (isset($orderItem['id']) && isset($orderItem['image_order'])) {
                            ImageTimeline::where('id', $orderItem['id'])
                                ->where('episode_id', $episode->id)
                                ->update(['image_order' => $orderItem['image_order']]);
                        }
                    }
                    $successCount = count($orderData);
                    break;
            }

            DB::commit();

            return redirect()->route('admin.episodes.timeline.index', $episode)
                ->with('success', "عملیات {$action} روی {$successCount} تایم‌لاین انجام شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk timeline action failed', [
                'episode_id' => $episode->id,
                'action' => $request->action,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.episodes.timeline.index', $episode)
                ->with('error', 'خطا در انجام عملیات گروهی: ' . $e->getMessage());
        }
    }

    /**
     * Handle image upload with processing
     */
    private function handleImageUpload($imageFile, Request $request)
    {
        // Ensure directory exists
        $timelineDir = public_path('images/episodes/timeline');
        if (!file_exists($timelineDir)) {
            mkdir($timelineDir, 0755, true);
        }

        // Generate unique filename
        $extension = $imageFile->getClientOriginalExtension();
        $randomString = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 32);
        $datetime = now()->format('Y-m-d_H-i-s');
        $filename = 'timeline_' . $randomString . '-' . $datetime . '.' . $extension;

        // Save image
        $imagePath = $imageFile->move($timelineDir, $filename);
        $relativePath = 'images/episodes/timeline/' . $filename;

        // Process image if requested
        if ($request->boolean('resize_image')) {
            try {
                $imageWidth = $request->input('image_width', 800);
                $imageHeight = $request->input('image_height', 600);
                
                $processedImage = $this->imageProcessingService->processImage(
                    $imagePath,
                    [
                        'resize' => ['width' => $imageWidth, 'height' => $imageHeight],
                        'optimize' => true,
                        'quality' => 85
                    ]
                );

                if ($processedImage['success']) {
                    $relativePath = $processedImage['data']['url'] ?? $relativePath;
                }
            } catch (\Exception $e) {
                Log::warning('Image processing failed', [
                    'filename' => $filename,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $relativePath;
    }
}