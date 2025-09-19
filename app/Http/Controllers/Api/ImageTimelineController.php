<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageTimelineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImageTimelineController extends Controller
{
    protected $timelineService;

    public function __construct(ImageTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    /**
     * Get image timeline for episode
     */
    public function getTimeline(Request $request, int $episodeId): JsonResponse
    {
        try {
            $includeVoiceActors = $request->boolean('include_voice_actors', false);
            $timeline = $this->timelineService->getTimelineForEpisode($episodeId, $includeVoiceActors);

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین تصاویر دریافت شد',
                'data' => [
                    'episode_id' => $episodeId,
                    'image_timeline' => $timeline
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or update image timeline for episode
     */
    public function saveTimeline(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'image_timeline' => 'required|array|min:1',
            'image_timeline.*.start_time' => 'required|integer|min:0',
            'image_timeline.*.end_time' => 'required|integer|min:1',
            'image_timeline.*.image_url' => 'required|url|max:500',
            'image_timeline.*.voice_actor_id' => 'nullable|exists:episode_voice_actors,id',
            'image_timeline.*.scene_description' => 'nullable|string|max:1000',
            'image_timeline.*.transition_type' => 'nullable|string|in:fade,slide,cut,dissolve,wipe',
            'image_timeline.*.is_key_frame' => 'boolean'
        ], [
            'image_timeline.required' => 'تایم‌لاین تصاویر الزامی است',
            'image_timeline.array' => 'تایم‌لاین باید آرایه باشد',
            'image_timeline.min' => 'تایم‌لاین باید حداقل یک ورودی داشته باشد',
            'image_timeline.*.start_time.required' => 'زمان شروع الزامی است',
            'image_timeline.*.start_time.integer' => 'زمان شروع باید عدد باشد',
            'image_timeline.*.start_time.min' => 'زمان شروع نمی‌تواند منفی باشد',
            'image_timeline.*.end_time.required' => 'زمان پایان الزامی است',
            'image_timeline.*.end_time.integer' => 'زمان پایان باید عدد باشد',
            'image_timeline.*.end_time.min' => 'زمان پایان باید حداقل 1 باشد',
            'image_timeline.*.image_url.required' => 'آدرس تصویر الزامی است',
            'image_timeline.*.image_url.url' => 'آدرس تصویر نامعتبر است',
            'image_timeline.*.image_url.max' => 'آدرس تصویر نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'image_timeline.*.voice_actor_id.exists' => 'صداپیشه انتخاب شده وجود ندارد',
            'image_timeline.*.scene_description.max' => 'توضیحات صحنه نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'image_timeline.*.transition_type.in' => 'نوع انتقال نامعتبر است'
        ]);

        try {
            $result = $this->timelineService->saveTimelineForEpisode($episodeId, $request->get('image_timeline'));

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ذخیره تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete image timeline for episode
     */
    public function deleteTimeline(int $episodeId): JsonResponse
    {
        try {
            $result = $this->timelineService->deleteTimelineForEpisode($episodeId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image for specific time in episode
     */
    public function getImageForTime(int $episodeId, Request $request): JsonResponse
    {
        $request->validate([
            'time' => 'required|integer|min:0'
        ], [
            'time.required' => 'زمان الزامی است',
            'time.integer' => 'زمان باید عدد باشد',
            'time.min' => 'زمان نمی‌تواند منفی باشد'
        ]);

        try {
            $timeInSeconds = $request->get('time');
            $imageUrl = $this->timelineService->getImageForTime($episodeId, $timeInSeconds);

            if ($imageUrl) {
                return response()->json([
                    'success' => true,
                    'message' => 'تصویر برای زمان مشخص شده یافت شد',
                    'data' => [
                        'episode_id' => $episodeId,
                        'time' => $timeInSeconds,
                        'image_url' => $imageUrl
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'تصویری برای زمان مشخص شده یافت نشد'
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تصویر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate timeline data
     */
    public function validateTimeline(Request $request): JsonResponse
    {
        $request->validate([
            'image_timeline' => 'required|array|min:1',
            'episode_duration' => 'required|integer|min:1',
            'image_timeline.*.start_time' => 'required|integer|min:0',
            'image_timeline.*.end_time' => 'required|integer|min:1',
            'image_timeline.*.image_url' => 'required|url|max:500'
        ], [
            'image_timeline.required' => 'تایم‌لاین تصاویر الزامی است',
            'episode_duration.required' => 'مدت اپیزود الزامی است',
            'episode_duration.integer' => 'مدت اپیزود باید عدد باشد',
            'episode_duration.min' => 'مدت اپیزود باید حداقل 1 باشد'
        ]);

        try {
            $errors = $this->timelineService->validateTimelineData(
                $request->get('image_timeline'),
                $request->get('episode_duration')
            );

            if (empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => 'تایم‌لاین معتبر است',
                    'data' => [
                        'is_valid' => true,
                        'errors' => []
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'تایم‌لاین نامعتبر است',
                    'data' => [
                        'is_valid' => false,
                        'errors' => $errors
                    ]
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize timeline data
     */
    public function optimizeTimeline(Request $request): JsonResponse
    {
        $request->validate([
            'image_timeline' => 'required|array|min:1',
            'image_timeline.*.start_time' => 'required|integer|min:0',
            'image_timeline.*.end_time' => 'required|integer|min:1',
            'image_timeline.*.image_url' => 'required|url|max:500'
        ], [
            'image_timeline.required' => 'تایم‌لاین تصاویر الزامی است'
        ]);

        try {
            $optimizedTimeline = $this->timelineService->optimizeTimeline($request->get('image_timeline'));

            return response()->json([
                'success' => true,
                'message' => 'تایم‌لاین بهینه‌سازی شد',
                'data' => [
                    'original_count' => count($request->get('image_timeline')),
                    'optimized_count' => count($optimizedTimeline),
                    'optimized_timeline' => $optimizedTimeline
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بهینه‌سازی تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline statistics
     */
    public function getStatistics(int $episodeId): JsonResponse
    {
        try {
            $statistics = $this->timelineService->getTimelineStatistics($episodeId);

            return response()->json([
                'success' => true,
                'message' => 'آمار تایم‌لاین دریافت شد',
                'data' => [
                    'episode_id' => $episodeId,
                    'statistics' => $statistics
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار تایم‌لاین: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline with voice actor information
     */
    public function getTimelineWithVoiceActors(int $episodeId): JsonResponse
    {
        try {
            $result = $this->timelineService->getTimelineWithVoiceActors($episodeId);
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تایم‌لاین با اطلاعات صداپیشه: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline for specific voice actor
     */
    public function getTimelineForVoiceActor(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'voice_actor_id' => 'required|exists:episode_voice_actors,id'
        ], [
            'voice_actor_id.required' => 'شناسه صداپیشه الزامی است',
            'voice_actor_id.exists' => 'صداپیشه انتخاب شده وجود ندارد'
        ]);

        try {
            $result = $this->timelineService->getTimelineForVoiceActor($episodeId, $request->voice_actor_id);
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تایم‌لاین صداپیشه: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get key frames for episode
     */
    public function getKeyFrames(int $episodeId): JsonResponse
    {
        try {
            $result = $this->timelineService->getKeyFrames($episodeId);
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فریم‌های کلیدی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline by transition type
     */
    public function getTimelineByTransitionType(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'transition_type' => 'required|string|in:fade,slide,cut,dissolve,wipe'
        ], [
            'transition_type.required' => 'نوع انتقال الزامی است',
            'transition_type.in' => 'نوع انتقال نامعتبر است'
        ]);

        try {
            $result = $this->timelineService->getTimelineByTransitionType($episodeId, $request->transition_type);
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تایم‌لاین بر اساس نوع انتقال: ' . $e->getMessage()
            ], 500);
        }
    }
}