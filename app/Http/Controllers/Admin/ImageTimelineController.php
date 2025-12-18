<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Services\ImageTimelineService;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImageTimelineController extends Controller
{
    protected $imageTimelineService;
    protected $imageProcessingService;

    public function __construct(
        ImageTimelineService $imageTimelineService,
        ImageProcessingService $imageProcessingService
    ) {
        $this->imageTimelineService = $imageTimelineService;
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Show the timeline management interface
     */
    public function show(Episode $episode)
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
     * Show form to edit timeline entry
     */
    public function edit(Episode $episode, ImageTimeline $timeline)
    {
        return view('admin.episodes.timeline.edit', compact('episode', 'timeline'));
    }

    /**
     * Store new timeline entry (Web version)
     */
    public function storeWeb(Request $request, Episode $episode)
    {
        $request->validate([
            'start_time' => 'required|integer|min:0|max:' . $episode->duration,
            'end_time' => 'required|integer|min:0|max:' . $episode->duration,
            'image_file' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'scene_description' => 'nullable|string|max:1000',
            'transition_type' => 'required|in:fade,cut,dissolve,slide',
            'is_key_frame' => 'boolean',
            'image_order' => 'required|integer|min:1',
            'resize_image' => 'boolean',
            'image_width' => 'nullable|integer|min:100|max:2000',
            'image_height' => 'nullable|integer|min:100|max:2000',
        ], [
            'start_time.required' => 'زمان شروع الزامی است',
            'start_time.max' => 'زمان شروع نمی‌تواند بیشتر از مدت اپیزود باشد',
            'end_time.required' => 'زمان پایان الزامی است',
            'end_time.max' => 'زمان پایان نمی‌تواند بیشتر از مدت اپیزود باشد',
            'image_file.required' => 'تصویر الزامی است',
            'image_file.image' => 'فایل باید یک تصویر معتبر باشد',
            'image_file.max' => 'حجم تصویر نمی‌تواند بیشتر از 10 مگابایت باشد',
            'transition_type.required' => 'نوع انتقال الزامی است',
            'image_order.required' => 'ترتیب تصویر الزامی است',
        ]);

        try {
            DB::beginTransaction();

            // Validate timeline doesn't overlap with existing entries
            $overlappingTimeline = $episode->imageTimelines()
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

            // Handle image upload
            $imageFile = $request->file('image_file');
            $imagePath = $this->handleImageUpload($imageFile, $request);

            // Create timeline entry
            $timeline = $episode->imageTimelines()->create([
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'image_url' => $imagePath,
                'image_order' => $request->image_order,
                'scene_description' => $request->scene_description,
                'transition_type' => $request->transition_type,
                'is_key_frame' => $request->boolean('is_key_frame'),
            ]);

            // Update episode to use image timeline
            $episode->update(['use_image_timeline' => true]);

            DB::commit();

            return redirect()->route('admin.episodes.timeline.index', $episode)
                ->with('success', 'تایم‌لاین با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create timeline', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['image_file'])
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
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage)
                ->with('error_details', [
                    'error_code' => 'TIMELINE_CREATION_FAILED',
                    'timestamp' => now()->toISOString(),
                    'episode_id' => $episode->id
                ]);
        }
    }

    /**
     * Update timeline entry (Web version)
     */
    public function updateWeb(Request $request, Episode $episode, ImageTimeline $timeline)
    {
        $request->validate([
            'start_time' => 'required|integer|min:0|max:' . $episode->duration,
            'end_time' => 'required|integer|min:0|max:' . $episode->duration,
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
     * Delete timeline entry (Web version)
     */
    public function destroyWeb(Episode $episode, ImageTimeline $timeline)
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
     * Display a listing of timelines for an episode
     */
    public function index(Request $request, Episode $episode): JsonResponse
    {
        $timeline = $this->imageTimelineService->getTimeline($episode->id);

        return response()->json([
            'success' => true,
            'message' => 'تایم‌لاین تصاویر دریافت شد',
            'data' => [
                'episode' => [
                    'id' => $episode->id,
                    'title' => $episode->title,
                    'duration' => $episode->duration,
                    'use_image_timeline' => $episode->use_image_timeline,
                ],
                'timeline' => $timeline->map(fn($item) => $item->toApiResponse()),
                'statistics' => $this->imageTimelineService->getStatistics($episode->id)
            ]
        ]);
    }

    /**
     * Store a newly created timeline
     */
    public function store(Request $request, Episode $episode): JsonResponse
    {
        try {
            $timelineData = $request->input('image_timeline', []);
            
            // Validate input data structure
            if (empty($timelineData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'داده‌های تایم‌لاین الزامی است',
                    'errors' => ['image_timeline' => ['لطفاً حداقل یک ورودی تایم‌لاین اضافه کنید']]
                ], 422);
            }

            // Use comprehensive validation service
            $validationService = new \App\Services\TimelineValidationService();
            $validationResult = $validationService->validateTimeline($timelineData, $episode->duration);
            
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌های تایم‌لاین',
                    'errors' => $validationResult['errors'],
                    'warnings' => $validationResult['warnings'] ?? [],
                    'statistics' => $validationResult['statistics'] ?? []
                ], 422);
            }

            // Use model validation for additional checks
            $validator = ImageTimeline::validateBulkTimelineData($timelineData);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌های تایم‌لاین',
                    'errors' => $validator->errors()->toArray(),
                    'warnings' => $validationResult['warnings'] ?? []
                ], 422);
            }

            $count = $this->imageTimelineService->saveTimeline($episode->id, $timelineData);

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین تصاویر با موفقیت ایجاد شد',
                'data' => [
                    'episode_id' => $episode->id,
                    'timeline_count' => $count,
                    'statistics' => $validationResult['statistics'] ?? []
                ],
                'warnings' => $validationResult['warnings'] ?? []
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌های تایم‌لاین',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Timeline creation failed', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timeline_data' => $request->input('image_timeline', [])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطایی در ایجاد تایم‌لاین تصاویر رخ داد',
                'error_details' => $e->getMessage(),
                'error_code' => 'TIMELINE_CREATION_FAILED'
            ], 500);
        }
    }

    /**
     * Update the specified timeline
     */
    public function update(Request $request, Episode $episode): JsonResponse
    {
        try {
            $request->validate([
                'image_timeline' => 'required|array',
                'image_timeline.*.start_time' => 'required|integer|min:0',
                'image_timeline.*.end_time' => 'required|integer|min:0',
                'image_timeline.*.image_url' => 'required|url',
            ]);

            $timelineData = $request->input('image_timeline');
            $count = $this->imageTimelineService->saveTimeline($episode->id, $timelineData);

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین تصاویر با موفقیت به‌روزرسانی شد',
                'data' => [
                    'episode_id' => $episode->id,
                    'timeline_count' => $count
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌های تایم‌لاین',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطایی در به‌روزرسانی تایم‌لاین تصاویر رخ داد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified timeline
     */
    public function destroy(Episode $episode): JsonResponse
    {
        try {
            $deleted = $this->imageTimelineService->deleteTimeline($episode->id);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'تایم‌لاین تصاویر با موفقیت حذف شد'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'تایم‌لاینی برای این قسمت یافت نشد'
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطایی در حذف تایم‌لاین تصاویر رخ داد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate timeline data without saving
     */
    public function validateTimeline(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'episode_duration' => 'required|integer|min:0',
                'image_timeline' => 'required|array',
                'image_timeline.*.start_time' => 'required|integer|min:0',
                'image_timeline.*.end_time' => 'required|integer|min:0',
                'image_timeline.*.image_url' => 'required|url',
            ]);

            $episodeDuration = $request->input('episode_duration');
            $timelineData = $request->input('image_timeline');

            $this->imageTimelineService->validateTimeline($episodeDuration, $timelineData);

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین معتبر است'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی تایم‌لاین',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطایی در اعتبارسنجی تایم‌لاین رخ داد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize timeline data
     */
    public function optimize(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image_timeline' => 'required|array',
                'image_timeline.*.start_time' => 'required|integer|min:0',
                'image_timeline.*.end_time' => 'required|integer|min:0',
                'image_timeline.*.image_url' => 'required|url',
            ]);

            $timelineData = $request->input('image_timeline');
            $optimizedTimeline = $this->imageTimelineService->optimizeTimeline($timelineData);

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین با موفقیت بهینه شد',
                'data' => [
                    'original_count' => count($timelineData),
                    'optimized_count' => count($optimizedTimeline),
                    'optimized_timeline' => $optimizedTimeline
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌های تایم‌لاین برای بهینه‌سازی',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطایی در بهینه‌سازی تایم‌لاین رخ داد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline statistics for an episode
     */
    public function statistics(Episode $episode): JsonResponse
    {
        $statistics = $this->imageTimelineService->getStatistics($episode->id);

        return response()->json([
            'success' => true,
            'message' => 'آمار تایم‌لاین تصاویر دریافت شد',
            'data' => [
                'episode_id' => $episode->id,
                'statistics' => $statistics
            ]
        ]);
    }

    /**
     * Bulk operations on timelines
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'action' => 'required|in:delete,validate,optimize',
                'episode_ids' => 'required|array',
                'episode_ids.*' => 'exists:episodes,id'
            ]);

            $action = $request->input('action');
            $episodeIds = $request->input('episode_ids');
            $results = [];

            foreach ($episodeIds as $episodeId) {
                try {
                    switch ($action) {
                        case 'delete':
                            $deleted = $this->imageTimelineService->deleteTimeline($episodeId);
                            $results[] = [
                                'episode_id' => $episodeId,
                                'success' => $deleted,
                                'message' => $deleted ? 'حذف شد' : 'تایم‌لاینی یافت نشد'
                            ];
                            break;
                        case 'validate':
                            $timeline = $this->imageTimelineService->getTimeline($episodeId);
                            $results[] = [
                                'episode_id' => $episodeId,
                                'success' => true,
                                'message' => 'تایم‌لاین معتبر است',
                                'count' => $timeline->count()
                            ];
                            break;
                        case 'optimize':
                            $timeline = $this->imageTimelineService->getTimeline($episodeId);
                            $optimized = $this->imageTimelineService->optimizeTimeline(
                                $timeline->toArray()
                            );
                            $results[] = [
                                'episode_id' => $episodeId,
                                'success' => true,
                                'message' => 'بهینه شد',
                                'original_count' => $timeline->count(),
                                'optimized_count' => count($optimized)
                            ];
                            break;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'episode_id' => $episodeId,
                        'success' => false,
                        'message' => 'خطا: ' . $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'عملیات گروهی انجام شد',
                'data' => [
                    'action' => $action,
                    'results' => $results
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی درخواست',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطایی در عملیات گروهی رخ داد: ' . $e->getMessage()
            ], 500);
        }
    }
}