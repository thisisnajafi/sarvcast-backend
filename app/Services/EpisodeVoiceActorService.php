<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\EpisodeVoiceActor;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EpisodeVoiceActorService
{
    /**
     * Add voice actor to episode
     */
    public function addVoiceActor(int $episodeId, array $data): array
    {
        try {
            DB::beginTransaction();

            $episode = Episode::findOrFail($episodeId);
            $person = Person::findOrFail($data['person_id']);

            // Validate voice actor data
            $errors = EpisodeVoiceActor::validateVoiceActorData($data, $episode->duration);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌های صداپیشه',
                    'errors' => $errors
                ];
            }

            // Validate time range for overlaps
            $this->validateTimeRange($episodeId, $data['start_time'], $data['end_time'], $data['person_id']);

            $voiceActor = EpisodeVoiceActor::create([
                'episode_id' => $episodeId,
                'person_id' => $data['person_id'],
                'role' => $data['role'],
                'character_name' => $data['character_name'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'voice_description' => $data['voice_description'] ?? null,
                'is_primary' => $data['is_primary'] ?? false
            ]);

            // Update episode voice actor count
            $this->updateEpisodeVoiceActorCount($episodeId);

            // Clear cache
            $this->clearEpisodeCache($episodeId);

            DB::commit();

            // Send notification if person has a user account
            try {
                $episode->load('story');
                $person = $voiceActor->person;
                
                // Try to find user by email or phone (if person has these fields)
                // Or check if there's a relationship between Person and User
                // For now, we'll check if person has an email that matches a user
                if ($person && isset($person->email)) {
                    $user = \App\Models\User::where('email', $person->email)->first();
                    if ($user) {
                        $notificationService = app(\App\Services\NotificationService::class);
                        $notificationService->sendVoiceActorAssignmentNotification(
                            $user,
                            'episode_voice_actor',
                            [
                                'episode_id' => $episode->id,
                                'episode_title' => $episode->title,
                                'story_id' => $episode->story_id,
                                'story_title' => $episode->story->title ?? 'داستان',
                                'role' => $data['role'],
                                'character_name' => $data['character_name'] ?? null,
                                'start_time' => $data['start_time'],
                                'end_time' => $data['end_time']
                            ]
                        );
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to send episode voice actor assignment notification', [
                    'episode_id' => $episodeId,
                    'person_id' => $data['person_id'],
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => true,
                'message' => 'صداپیشه با موفقیت اضافه شد',
                'data' => $voiceActor->load('person')->toApiResponse()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'خطا در اضافه کردن صداپیشه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update voice actor
     */
    public function updateVoiceActor(int $voiceActorId, array $data): array
    {
        try {
            DB::beginTransaction();

            $voiceActor = EpisodeVoiceActor::with('episode')->findOrFail($voiceActorId);
            $episode = $voiceActor->episode;

            // Validate voice actor data
            $errors = EpisodeVoiceActor::validateVoiceActorData($data, $episode->duration);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌های صداپیشه',
                    'errors' => $errors
                ];
            }

            // Validate time range for overlaps (excluding current voice actor)
            $this->validateTimeRange($episode->id, $data['start_time'], $data['end_time'], $data['person_id'], $voiceActorId);

            $voiceActor->update([
                'person_id' => $data['person_id'],
                'role' => $data['role'],
                'character_name' => $data['character_name'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'voice_description' => $data['voice_description'] ?? null,
                'is_primary' => $data['is_primary'] ?? false
            ]);

            // Clear cache
            $this->clearEpisodeCache($episode->id);

            DB::commit();

            return [
                'success' => true,
                'message' => 'صداپیشه با موفقیت به‌روزرسانی شد',
                'data' => $voiceActor->load('person')->toApiResponse()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی صداپیشه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete voice actor
     */
    public function deleteVoiceActor(int $voiceActorId): array
    {
        try {
            DB::beginTransaction();

            $voiceActor = EpisodeVoiceActor::with('episode')->findOrFail($voiceActorId);
            $episodeId = $voiceActor->episode->id;

            $voiceActor->delete();

            // Update episode voice actor count
            $this->updateEpisodeVoiceActorCount($episodeId);

            // Clear cache
            $this->clearEpisodeCache($episodeId);

            DB::commit();

            return [
                'success' => true,
                'message' => 'صداپیشه با موفقیت حذف شد'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'خطا در حذف صداپیشه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get voice actors for episode
     */
    public function getVoiceActorsForEpisode(int $episodeId): array
    {
        $cacheKey = "episode_voice_actors_{$episodeId}";
        
        $data = Cache::remember($cacheKey, 3600, function () use ($episodeId) {
            $episode = Episode::findOrFail($episodeId);
            $voiceActors = $episode->voiceActors()->with('person')->get();

            return [
                'episode_id' => $episodeId,
                'voice_actors' => $voiceActors->map->toApiResponse(),
                'total_duration' => $episode->duration,
                'has_multiple_voice_actors' => $episode->has_multiple_voice_actors,
                'voice_actor_count' => $episode->voice_actor_count
            ];
        });

        return [
            'success' => true,
            'message' => 'صداپیشگان قسمت دریافت شد',
            'data' => $data
        ];
    }

    /**
     * Get voice actor for specific time
     */
    public function getVoiceActorForTime(int $episodeId, int $timeInSeconds): array
    {
        $episode = Episode::findOrFail($episodeId);
        $voiceActor = $episode->getVoiceActorForTime($timeInSeconds);

        if (!$voiceActor) {
            return [
                'success' => false,
                'message' => 'صداپیشه برای زمان مشخص شده یافت نشد'
            ];
        }

        return [
            'success' => true,
            'message' => 'صداپیشه برای زمان مشخص شده دریافت شد',
            'data' => $voiceActor->load('person')->toApiResponse()
        ];
    }

    /**
     * Get all voice actors at specific time
     */
    public function getVoiceActorsAtTime(int $episodeId, int $timeInSeconds): array
    {
        $episode = Episode::findOrFail($episodeId);
        $voiceActors = $episode->getVoiceActorsAtTime($timeInSeconds);

        return [
            'success' => true,
            'message' => 'صداپیشگان برای زمان مشخص شده دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'time' => $timeInSeconds,
                'time_formatted' => $this->formatTime($timeInSeconds),
                'voice_actors' => $voiceActors->load('person')->map->toApiResponse()
            ]
        ];
    }

    /**
     * Get voice actors by role
     */
    public function getVoiceActorsByRole(int $episodeId, string $role): array
    {
        $episode = Episode::findOrFail($episodeId);
        $voiceActors = $episode->voiceActors()->withRole($role)->with('person')->get();

        return [
            'success' => true,
            'message' => "صداپیشگان با نقش {$role} دریافت شد",
            'data' => [
                'episode_id' => $episodeId,
                'role' => $role,
                'voice_actors' => $voiceActors->map->toApiResponse()
            ]
        ];
    }

    /**
     * Validate time range for voice actor
     */
    private function validateTimeRange(int $episodeId, int $startTime, int $endTime, int $personId, int $excludeId = null): void
    {
        $episode = Episode::findOrFail($episodeId);

        if ($startTime < 0 || $endTime > $episode->duration) {
            throw new \InvalidArgumentException('زمان شروع و پایان باید در محدوده مدت زمان قسمت باشد');
        }

        if ($startTime >= $endTime) {
            throw new \InvalidArgumentException('زمان شروع باید کمتر از زمان پایان باشد');
        }

        // Check for overlaps with existing voice actors
        $query = EpisodeVoiceActor::where('episode_id', $episodeId)
            ->where('person_id', $personId)
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlapping = $query->exists();

        if ($overlapping) {
            throw new \InvalidArgumentException('زمان مشخص شده با صداپیشه دیگری تداخل دارد');
        }
    }

    /**
     * Update episode voice actor count
     */
    private function updateEpisodeVoiceActorCount(int $episodeId): void
    {
        $count = EpisodeVoiceActor::where('episode_id', $episodeId)->count();
        $hasMultiple = $count > 1;

        Episode::where('id', $episodeId)->update([
            'voice_actor_count' => $count,
            'has_multiple_voice_actors' => $hasMultiple
        ]);
    }

    /**
     * Clear episode cache
     */
    private function clearEpisodeCache(int $episodeId): void
    {
        Cache::forget("episode_voice_actors_{$episodeId}");
        Cache::forget("episode_{$episodeId}");
    }

    /**
     * Format time in seconds to MM:SS format
     */
    private function formatTime(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Validate voice actor time range for overlaps
     */
    public function validateVoiceActorTimeRange(int $episodeId, int $startTime, int $endTime, int $personId, int $excludeId = null): array
    {
        $query = EpisodeVoiceActor::where('episode_id', $episodeId)
            ->where('person_id', $personId)
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlaps = $query->get();

        if ($overlaps->count() > 0) {
            return [
                'success' => false,
                'message' => 'بازه زمانی انتخاب شده با صداپیشه‌های موجود تداخل دارد',
                'overlaps' => $overlaps->map->toApiResponse()
            ];
        }

        return [
            'success' => true,
            'message' => 'بازه زمانی معتبر است'
        ];
    }

    /**
     * Get available people for episode voice actors
     */
    public function getAvailablePeopleForEpisode(int $episodeId): array
    {
        $people = Person::where('is_verified', true)
            ->whereHas('roles', function($query) {
                $query->whereIn('name', ['voice_actor', 'narrator', 'actor']);
            })
            ->with(['roles', 'episodeVoiceActors' => function($query) use ($episodeId) {
                $query->where('episode_id', $episodeId);
            }])
            ->get()
            ->map(function($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->name,
                    'image_url' => $person->image_url,
                    'bio' => $person->bio,
                    'roles' => $person->roles->pluck('name'),
                    'is_verified' => $person->is_verified,
                    'episode_voice_actors_count' => $person->episodeVoiceActors->count()
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست افراد در دسترس دریافت شد',
            'data' => $people
        ];
    }

    /**
     * Get voice actor statistics for episode
     */
    public function getVoiceActorStatistics(int $episodeId): array
    {
        $episode = Episode::findOrFail($episodeId);
        $voiceActors = $episode->voiceActors()->with('person')->get();

        $statistics = [
            'total_voice_actors' => $voiceActors->count(),
            'total_duration' => $episode->duration,
            'roles' => $voiceActors->groupBy('role')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total_duration' => $group->sum('duration'),
                    'voice_actors' => $group->map->toApiResponse()
                ];
            }),
            'primary_voice_actor' => $voiceActors->where('is_primary', true)->first()?->toApiResponse(),
            'voice_actor_timeline' => $voiceActors->sortBy('start_time')->map(function($va) {
                return [
                    'person_name' => $va->person->name,
                    'role' => $va->role,
                    'character_name' => $va->character_name,
                    'start_time' => $va->start_time,
                    'end_time' => $va->end_time,
                    'duration' => $va->duration
                ];
            })
        ];

        return [
            'success' => true,
            'message' => 'آمار صداپیشگان قسمت دریافت شد',
            'data' => $statistics
        ];
    }
}
