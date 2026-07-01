<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Notification;
use App\Models\PlayHistory;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StoryCompletionService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function checkAndNotify(int $userId, int $storyId, int $episodeId): void
    {
        try {
            $episode = Episode::find($episodeId);
            if (!$episode || (int) $episode->story_id !== $storyId) {
                return;
            }

            $hasCompletedEpisode = PlayHistory::where('user_id', $userId)
                ->where('episode_id', $episodeId)
                ->where('completed', true)
                ->exists();

            if (!$hasCompletedEpisode) {
                return;
            }

            if (!$this->hasCompletedAllPublishedEpisodes($userId, $storyId)) {
                return;
            }

            if ($this->alreadyNotified($userId, $storyId)) {
                return;
            }

            $story = Story::find($storyId);
            $user = User::find($userId);

            if (!$story || !$user) {
                return;
            }

            $this->notificationService->sendContentNotification($user, 'story_completed', [
                'story_id' => (string) $storyId,
                'story_title' => $story->title,
                'type' => 'content',
            ]);

            Log::info('Story completion notification sent', [
                'user_id' => $userId,
                'story_id' => $storyId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check story completion notification: ' . $e->getMessage(), [
                'user_id' => $userId,
                'story_id' => $storyId,
                'episode_id' => $episodeId,
            ]);
        }
    }

    protected function hasCompletedAllPublishedEpisodes(int $userId, int $storyId): bool
    {
        $publishedEpisodeIds = Episode::where('story_id', $storyId)
            ->published()
            ->pluck('id');

        if ($publishedEpisodeIds->isEmpty()) {
            return false;
        }

        $completedEpisodeIds = PlayHistory::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->where('completed', true)
            ->distinct()
            ->pluck('episode_id');

        return $publishedEpisodeIds->diff($completedEpisodeIds)->isEmpty();
    }

    protected function alreadyNotified(int $userId, int $storyId): bool
    {
        return Notification::where('user_id', $userId)
            ->where('title', 'داستان تکمیل شد')
            ->where(function ($query) use ($storyId) {
                $query->where('data->story_id', $storyId)
                    ->orWhere('data->story_id', (string) $storyId);
            })
            ->exists();
    }
}
