<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\ImageTimeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImageTimelineService
{
    /**
     * Get image timeline for episode
     */
    public function getTimelineForEpisode(int $episodeId, bool $includeVoiceActors = false): array
    {
        $cacheKey = "episode_timeline_{$episodeId}" . ($includeVoiceActors ? '_with_voice_actors' : '');
        
        return Cache::remember($cacheKey, 3600, function() use ($episodeId, $includeVoiceActors) {
            $query = ImageTimeline::forEpisode($episodeId)->ordered();
            
            if ($includeVoiceActors) {
                $query->with('voiceActor.person');
            }
            
            $timelines = $query->get();

            return $timelines->map(function($timeline) {
                return $timeline->toApiResponse();
            })->toArray();
        });
    }

    /**
     * Create or update image timeline for episode
     */
    public function saveTimelineForEpisode(int $episodeId, array $timelineData): array
    {
        try {
            DB::beginTransaction();

            $episode = Episode::findOrFail($episodeId);
            
            // Validate timeline data
            $errors = ImageTimeline::validateTimeline($timelineData, $episode->duration);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی تایم‌لاین',
                    'errors' => $errors
                ];
            }

            // Delete existing timeline
            ImageTimeline::forEpisode($episodeId)->delete();

            // Create new timeline entries
            foreach ($timelineData as $index => $timeline) {
                ImageTimeline::create([
                    'episode_id' => $episodeId,
                    'voice_actor_id' => $timeline['voice_actor_id'] ?? null,
                    'start_time' => $timeline['start_time'],
                    'end_time' => $timeline['end_time'],
                    'image_url' => $timeline['image_url'],
                    'image_order' => $index + 1,
                    'scene_description' => $timeline['scene_description'] ?? null,
                    'transition_type' => $timeline['transition_type'] ?? 'fade',
                    'is_key_frame' => $timeline['is_key_frame'] ?? false
                ]);
            }

            // Update episode to use timeline
            $episode->update(['use_image_timeline' => true]);

            DB::commit();

            // Clear cache
            Cache::forget("episode_timeline_{$episodeId}");
            Cache::forget("episode_timeline_{$episodeId}_with_voice_actors");

            return [
                'success' => true,
                'message' => 'تایم‌لاین تصاویر با موفقیت ذخیره شد',
                'data' => [
                    'episode_id' => $episodeId,
                    'timeline_count' => count($timelineData)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saving timeline for episode {$episodeId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در ذخیره تایم‌لاین: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete image timeline for episode
     */
    public function deleteTimelineForEpisode(int $episodeId): array
    {
        try {
            DB::beginTransaction();

            $episode = Episode::findOrFail($episodeId);
            
            // Delete timeline entries
            ImageTimeline::forEpisode($episodeId)->delete();

            // Update episode to not use timeline
            $episode->update(['use_image_timeline' => false]);

            DB::commit();

            // Clear cache
            Cache::forget("episode_timeline_{$episodeId}");
            Cache::forget("episode_timeline_{$episodeId}_with_voice_actors");

            return [
                'success' => true,
                'message' => 'تایم‌لاین تصاویر با موفقیت حذف شد'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting timeline for episode {$episodeId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در حذف تایم‌لاین: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get image for specific time in episode
     */
    public function getImageForTime(int $episodeId, int $timeInSeconds): ?string
    {
        $timeline = ImageTimeline::forEpisode($episodeId)
            ->forTime($timeInSeconds)
            ->first();

        return $timeline ? $timeline->image_url : null;
    }

    /**
     * Validate timeline data using the dedicated validation service
     */
    public function validateTimelineData(array $timelineData, int $episodeDuration): array
    {
        $validationService = new \App\Services\TimelineValidationService();
        $result = $validationService->validateTimeline($timelineData, $episodeDuration);
        
        return $result['errors'];
    }


    /**
     * Optimize timeline by merging adjacent identical images
     */
    public function optimizeTimeline(array $timelineData): array
    {
        if (empty($timelineData)) {
            return $timelineData;
        }

        $sortedTimeline = collect($timelineData)->sortBy('start_time')->values();
        $optimizedTimeline = [];
        $currentEntry = $sortedTimeline->first();

        for ($i = 1; $i < $sortedTimeline->count(); $i++) {
            $nextEntry = $sortedTimeline[$i];

            // If images are the same and times are adjacent, merge them
            if ($currentEntry['image_url'] === $nextEntry['image_url'] && 
                $currentEntry['end_time'] === $nextEntry['start_time']) {
                $currentEntry['end_time'] = $nextEntry['end_time'];
            } else {
                $optimizedTimeline[] = $currentEntry;
                $currentEntry = $nextEntry;
            }
        }

        $optimizedTimeline[] = $currentEntry;

        return $optimizedTimeline;
    }

    /**
     * Get timeline statistics
     */
    public function getTimelineStatistics(int $episodeId): array
    {
        $timeline = ImageTimeline::forEpisode($episodeId)->with('voiceActor.person')->get();
        
        return [
            'total_segments' => $timeline->count(),
            'total_duration' => $timeline->sum('end_time') - $timeline->min('start_time'),
            'unique_images' => $timeline->pluck('image_url')->unique()->count(),
            'average_segment_duration' => $timeline->count() > 0 ? 
                ($timeline->sum('end_time') - $timeline->sum('start_time')) / $timeline->count() : 0,
            'key_frames_count' => $timeline->where('is_key_frame', true)->count(),
            'transition_types' => $timeline->groupBy('transition_type')->map->count(),
            'voice_actor_segments' => $timeline->whereNotNull('voice_actor_id')->count(),
            'voice_actors_used' => $timeline->whereNotNull('voice_actor_id')->pluck('voiceActor.person.name')->unique()->values()
        ];
    }

    /**
     * Get timeline with voice actor information
     */
    public function getTimelineWithVoiceActors(int $episodeId): array
    {
        $timeline = $this->getTimelineForEpisode($episodeId, true);
        
        return [
            'success' => true,
            'message' => 'تایم‌لاین تصاویر با اطلاعات صداپیشه دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'image_timeline' => $timeline
            ]
        ];
    }

    /**
     * Get image timeline for specific voice actor
     */
    public function getTimelineForVoiceActor(int $episodeId, int $voiceActorId): array
    {
        $timelines = ImageTimeline::forEpisode($episodeId)
            ->where('voice_actor_id', $voiceActorId)
            ->with('voiceActor.person')
            ->ordered()
            ->get();

        return [
            'success' => true,
            'message' => 'تایم‌لاین تصاویر برای صداپیشه مشخص شده دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'voice_actor_id' => $voiceActorId,
                'image_timeline' => $timelines->map->toApiResponse()
            ]
        ];
    }

    /**
     * Get key frames for episode
     */
    public function getKeyFrames(int $episodeId): array
    {
        $keyFrames = ImageTimeline::forEpisode($episodeId)
            ->keyFrames()
            ->with('voiceActor.person')
            ->ordered()
            ->get();

        return [
            'success' => true,
            'message' => 'فریم‌های کلیدی قسمت دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'key_frames' => $keyFrames->map->toApiResponse()
            ]
        ];
    }

    /**
     * Get timeline segments by transition type
     */
    public function getTimelineByTransitionType(int $episodeId, string $transitionType): array
    {
        $timelines = ImageTimeline::forEpisode($episodeId)
            ->withTransitionType($transitionType)
            ->with('voiceActor.person')
            ->ordered()
            ->get();

        return [
            'success' => true,
            'message' => "تایم‌لاین با نوع انتقال {$transitionType} دریافت شد",
            'data' => [
                'episode_id' => $episodeId,
                'transition_type' => $transitionType,
                'image_timeline' => $timelines->map->toApiResponse()
            ]
        ];
    }
}
