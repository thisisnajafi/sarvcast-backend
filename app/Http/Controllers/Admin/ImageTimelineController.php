<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Services\ImageTimelineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ImageTimelineController extends Controller
{
    protected $imageTimelineService;

    public function __construct(ImageTimelineService $imageTimelineService)
    {
        $this->imageTimelineService = $imageTimelineService;
    }

    /**
     * Show the timeline management interface
     */
    public function show(Episode $episode)
    {
        return view('admin.timeline.index', compact('episode'));
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
                'message' => 'تایم‌لاین تصاویر با موفقیت ایجاد شد',
                'data' => [
                    'episode_id' => $episode->id,
                    'timeline_count' => $count
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌های تایم‌لاین',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطایی در ایجاد تایم‌لاین تصاویر رخ داد: ' . $e->getMessage()
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