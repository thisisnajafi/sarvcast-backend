<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StoryTimelineController extends Controller
{
    protected $imageProcessingService;

    public function __construct(ImageProcessingService $imageProcessingService)
    {
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Display timeline for a story
     */
    public function index(Story $story)
    {
        $timelines = $story->imageTimelines()->orderBy('image_order')->get();
        
        return view('admin.stories.timeline.index', compact('story', 'timelines'));
    }

    /**
     * Show form to create new timeline entry
     */
    public function create(Story $story)
    {
        return view('admin.stories.timeline.create', compact('story'));
    }

    /**
     * Store new timeline entry
     */
    public function store(Request $request, Story $story)
    {
        $request->validate([
            'image_timeline_data' => 'required|string',
            'selected_episode_id' => 'required|exists:episodes,id',
        ], [
            'image_timeline_data.required' => 'داده‌های تایم‌لاین الزامی است',
            'selected_episode_id.required' => 'انتخاب اپیزود الزامی است',
            'selected_episode_id.exists' => 'اپیزود انتخاب شده وجود ندارد',
        ]);

        try {
            DB::beginTransaction();

            $imageTimelineData = json_decode($request->image_timeline_data, true);
            $selectedEpisodeId = $request->selected_episode_id;
            
            // Debug logging
            Log::info('Timeline creation request', [
                'story_id' => $story->id,
                'selected_episode_id' => $selectedEpisodeId,
                'timeline_data_count' => count($imageTimelineData),
                'timeline_data' => $imageTimelineData,
                'files_received' => array_keys($request->allFiles())
            ]);
            
            if (empty($imageTimelineData)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'لطفاً حداقل یک تصویر برای تایم‌لاین اضافه کنید.');
            }

            // Check if episode already has a timeline
            $existingTimeline = $story->imageTimelines()
                ->where('episode_id', $selectedEpisodeId)
                ->first();

            if ($existingTimeline) {
                Log::info("Episode already has timeline, updating existing timeline", [
                    'existing_timeline_id' => $existingTimeline->id,
                    'episode_id' => $selectedEpisodeId
                ]);
                
                // Delete existing timeline and its image
                if ($existingTimeline->image_url && file_exists(public_path($existingTimeline->image_url))) {
                    unlink(public_path($existingTimeline->image_url));
                }
                $existingTimeline->delete();
            }

            // Validate timeline data for gaps and overlaps
            $sortedTimelineData = collect($imageTimelineData)->sortBy('start_time')->values();
            $errors = $this->validateTimelineData($sortedTimelineData->toArray(), $selectedEpisodeId);
            
            if (!empty($errors)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', implode(', ', $errors));
            }

            // Get all uploaded files and match them with timeline data
            $uploadedFiles = $request->allFiles();
            $timelineFiles = [];
            
            // Extract timeline image files and sort them by index
            foreach ($uploadedFiles as $key => $file) {
                if (strpos($key, 'timeline_image_') === 0) {
                    $fileIndex = (int) str_replace('timeline_image_', '', $key);
                    $timelineFiles[$fileIndex] = $file;
                }
            }
            
            // Sort files by index to ensure correct order
            ksort($timelineFiles);
            
            Log::info("Processing timeline files", [
                'total_timeline_entries' => count($imageTimelineData),
                'total_uploaded_files' => count($timelineFiles),
                'file_indices' => array_keys($timelineFiles),
                'php_max_file_uploads' => ini_get('max_file_uploads'),
                'php_post_max_size' => ini_get('post_max_size'),
                'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_memory_limit' => ini_get('memory_limit')
            ]);

            // Check for PHP upload limit
            $maxFileUploads = 300;
            if ($maxFileUploads && count($imageTimelineData) > $maxFileUploads) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "تعداد تصاویر تایم‌لاین (" . count($imageTimelineData) . ") از محدودیت PHP ({$maxFileUploads} فایل در هر درخواست) بیشتر است. لطفاً تعداد تصاویر را کاهش دهید یا با مدیر سیستم تماس بگیرید تا محدودیت افزایش یابد.")
                    ->with('warning', "راه‌حل: محدودیت PHP را افزایش دهید (max_file_uploads = {$maxFileUploads} یا بیشتر) یا تصاویر را در چند مرحله آپلود کنید.");
            }

            // Validate that we have enough files for all timeline entries
            if (count($timelineFiles) !== count($imageTimelineData)) {
                $errorMessage = "تعداد فایل‌های آپلود شده (" . count($timelineFiles) . ") با تعداد ورودی‌های تایم‌لاین (" . count($imageTimelineData) . ") مطابقت ندارد.";
                
                // Add PHP limit information if applicable
                if ($maxFileUploads && count($imageTimelineData) > $maxFileUploads) {
                    $errorMessage .= " این ممکن است به دلیل محدودیت PHP باشد (حداکثر {$maxFileUploads} فایل در هر درخواست).";
                }
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMessage);
            }

            // Process timeline entries - create one timeline per episode
            $fileIndex = 0;
            foreach ($imageTimelineData as $index => $timelineData) {
                // Debug each timeline entry
                Log::info("Processing timeline entry {$index}", $timelineData);
                
                Log::info("No overlap detected for entry {$index}", [
                    'timeline_data' => $timelineData,
                    'selected_episode_id' => $selectedEpisodeId
                ]);

                // Handle image upload - get the actual file from uploaded files
                $imageFile = null;
                
                // Get the next available file
                if (isset($timelineFiles[$fileIndex])) {
                    $imageFile = $timelineFiles[$fileIndex];
                    Log::info("Found image file for timeline {$index}", [
                        'file_index' => $fileIndex,
                        'file_size' => $imageFile->getSize(),
                        'file_name' => $imageFile->getClientOriginalName()
                    ]);
                    $fileIndex++;
                } else {
                    Log::warning("No image file found for timeline {$index}", [
                        'file_index' => $fileIndex,
                        'available_files' => array_keys($timelineFiles)
                    ]);
                }
                
                if (!$imageFile) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "فایل تصویر برای تایم‌لاین شماره " . ($index + 1) . " یافت نشد.");
                }
                
                $imagePath = $this->handleImageUpload($imageFile, $request);
                
                Log::info("Created timeline entry {$index}", [
                    'image_path' => $imagePath,
                    'timeline_data' => $timelineData
                ]);

                // Create timeline entry
                $story->imageTimelines()->create([
                    'episode_id' => $selectedEpisodeId, // Use the selected episode
                    'start_time' => $timelineData['start_time'],
                    'end_time' => $timelineData['end_time'],
                    'image_url' => $imagePath,
                    'image_order' => $timelineData['image_order'],
                    'scene_description' => $timelineData['scene_description'],
                    'transition_type' => $timelineData['transition_type'],
                    'is_key_frame' => $timelineData['is_key_frame'],
                ]);
            }

            // Update story to use image timeline
            $story->update(['use_image_timeline' => true]);

            DB::commit();

            Log::info('Timeline creation completed successfully', [
                'story_id' => $story->id,
                'selected_episode_id' => $selectedEpisodeId,
                'timelines_created' => count($imageTimelineData)
            ]);

            return redirect()->route('admin.stories.timeline.index', $story)
                ->with('success', count($imageTimelineData) . ' تایم‌لاین با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create timeline', [
                'story_id' => $story->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['timeline_image_*'])
            ]);

            // Provide more detailed error information
            $errorMessage = 'خطا در ایجاد تایم‌لاین: ' . $e->getMessage();
            
            // Add specific error context
            if (str_contains($e->getMessage(), 'validation')) {
                $errorMessage = 'خطا در اعتبارسنجی داده‌ها: ' . $e->getMessage();
            } elseif (str_contains($e->getMessage(), 'upload')) {
                $errorMessage = 'خطا در آپلود تصویر: ' . $e->getMessage();
            } elseif (str_contains($e->getMessage(), 'database')) {
                $errorMessage = 'خطا در ذخیره‌سازی: ' . $e->getMessage();
            } elseif (str_contains($e->getMessage(), 'overlap')) {
                $errorMessage = 'تداخل زمانی در تایم‌لاین: ' . $e->getMessage();
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage)
                ->with('error_details', [
                    'error_code' => 'STORY_TIMELINE_CREATION_FAILED',
                    'timestamp' => now()->toISOString(),
                    'story_id' => $story->id,
                    'selected_episode_id' => $request->selected_episode_id
                ]);
        }
    }

    /**
     * Show form to edit timeline entry
     */
    public function edit(Story $story, ImageTimeline $timeline)
    {
        return view('admin.stories.timeline.edit', compact('story', 'timeline'));
    }

    /**
     * Update timeline entry
     */
    public function update(Request $request, Story $story, ImageTimeline $timeline)
    {
        $request->validate([
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
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
            $overlappingTimeline = $story->imageTimelines()
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

            return redirect()->route('admin.stories.timeline.index', $story)
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
    public function destroy(Story $story, ImageTimeline $timeline)
    {
        try {
            DB::beginTransaction();

            // Delete associated image file
            if ($timeline->image_url && file_exists(public_path($timeline->image_url))) {
                unlink(public_path($timeline->image_url));
            }

            $timeline->delete();

            // Update story if no more timelines
            if ($story->imageTimelines()->count() === 0) {
                $story->update(['use_image_timeline' => false]);
            }

            DB::commit();

            return redirect()->route('admin.stories.timeline.index', $story)
                ->with('success', 'تایم‌لاین با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete timeline', [
                'timeline_id' => $timeline->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.timeline.index', $story)
                ->with('error', 'خطا در حذف تایم‌لاین: ' . $e->getMessage());
        }
    }

    /**
     * Bulk operations on timeline entries
     */
    public function bulkAction(Request $request, Story $story)
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
                        if ($timeline && $timeline->story_id === $story->id) {
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
                        ->where('story_id', $story->id)
                        ->update(['transition_type' => $request->transition_type]);
                    $successCount = count($timelineIds);
                    break;

                case 'reorder':
                    $orderData = $request->order_data;
                    foreach ($orderData as $orderItem) {
                        if (isset($orderItem['id']) && isset($orderItem['image_order'])) {
                            ImageTimeline::where('id', $orderItem['id'])
                                ->where('story_id', $story->id)
                                ->update(['image_order' => $orderItem['image_order']]);
                        }
                    }
                    $successCount = count($orderData);
                    break;
            }

            DB::commit();

            return redirect()->route('admin.stories.timeline.index', $story)
                ->with('success', "عملیات {$action} روی {$successCount} تایم‌لاین انجام شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk timeline action failed', [
                'story_id' => $story->id,
                'action' => $request->action,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.timeline.index', $story)
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

    /**
     * Validate timeline data for gaps and overlaps
     */
    private function validateTimelineData(array $timelineData, int $episodeId): array
    {
        $errors = [];
        $episode = Episode::find($episodeId);
        
        if (!$episode) {
            $errors[] = 'Episode not found';
            return $errors;
        }
        
        $episodeDuration = $episode->duration;
        
        // Sort timeline by start time
        $sortedTimeline = collect($timelineData)->sortBy('start_time')->values();
        
        for ($i = 0; $i < $sortedTimeline->count(); $i++) {
            $current = $sortedTimeline[$i];
            
            // Validate individual entry
            if ($current['start_time'] < 0) {
                $errors[] = "Start time cannot be negative for entry " . ($i + 1);
            }
            
            if ($current['end_time'] > $episodeDuration) {
                $errors[] = "End time cannot exceed episode duration for entry " . ($i + 1);
            }
            
            // Removed: start_time must be less than end_time validation
            
            // Check for overlaps with next entry
            if ($i < $sortedTimeline->count() - 1) {
                $next = $sortedTimeline[$i + 1];
                
                if ($current['end_time'] > $next['start_time']) {
                    $errors[] = "Timeline entries cannot overlap between entry " . ($i + 1) . " and " . ($i + 2);
                }
            }
        }
        
        // Removed: Timeline must start from 0 seconds and cover entire duration
        
        return $errors;
    }
}