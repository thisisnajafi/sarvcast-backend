<?php

namespace App\Services;

use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AccessControlService
{
    /**
     * Check if user has access to premium content
     */
    public function hasPremiumAccess(int $userId): bool
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return false;
            }

            // Check for active subscription
            $activeSubscription = Subscription::where('user_id', $userId)
                                             ->where('status', 'active')
                                             ->where('end_date', '>', now())
                                             ->first();

            if ($activeSubscription) {
                return true;
            }

            // Check for trial subscription
            $trialSubscription = Subscription::where('user_id', $userId)
                                           ->where('status', 'trial')
                                           ->where('end_date', '>', now())
                                           ->first();

            return $trialSubscription !== null;

        } catch (\Exception $e) {
            Log::error('Failed to check premium access', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user can access specific story
     */
    public function canAccessStory(int $userId, int $storyId): array
    {
        try {
            $story = Story::find($storyId);
            if (!$story) {
                return [
                    'has_access' => false,
                    'reason' => 'story_not_found',
                    'message' => 'داستان یافت نشد'
                ];
            }

            // Free stories are accessible to everyone
            if ($story->is_completely_free) {
                return [
                    'has_access' => true,
                    'reason' => 'free_content',
                    'message' => 'داستان رایگان است'
                ];
            }

            // Check if story is premium
            if (!$story->is_premium) {
                return [
                    'has_access' => true,
                    'reason' => 'free_content',
                    'message' => 'داستان رایگان است'
                ];
            }

            // Check premium access
            if ($this->hasPremiumAccess($userId)) {
                return [
                    'has_access' => true,
                    'reason' => 'premium_subscription',
                    'message' => 'دسترسی با اشتراک فعال'
                ];
            }

            // Check if user has access to free episodes
            $freeEpisodesCount = $story->free_episodes ?? 0;
            if ($freeEpisodesCount > 0) {
                return [
                    'has_access' => true,
                    'reason' => 'limited_free_access',
                    'message' => "دسترسی محدود به {$freeEpisodesCount} قسمت اول",
                    'free_episodes_count' => $freeEpisodesCount
                ];
            }

            return [
                'has_access' => false,
                'reason' => 'premium_required',
                'message' => 'برای دسترسی به این داستان اشتراک فعال نیاز است'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check story access', [
                'user_id' => $userId,
                'story_id' => $storyId,
                'error' => $e->getMessage()
            ]);

            return [
                'has_access' => false,
                'reason' => 'error',
                'message' => 'خطا در بررسی دسترسی'
            ];
        }
    }

    /**
     * Check if user can access specific episode
     */
    public function canAccessEpisode(int $userId, int $episodeId): array
    {
        try {
            $episode = Episode::with('story')->find($episodeId);
            if (!$episode) {
                return [
                    'has_access' => false,
                    'reason' => 'episode_not_found',
                    'message' => 'قسمت یافت نشد'
                ];
            }

            $story = $episode->story;

            // Free episodes are accessible to everyone
            if (!$episode->is_premium) {
                return [
                    'has_access' => true,
                    'reason' => 'free_content',
                    'message' => 'قسمت رایگان است'
                ];
            }

            // Check premium access
            if ($this->hasPremiumAccess($userId)) {
                return [
                    'has_access' => true,
                    'reason' => 'premium_subscription',
                    'message' => 'دسترسی با اشتراک فعال'
                ];
            }

            // Check if episode is within free episodes limit
            $freeEpisodesCount = $story->free_episodes ?? 0;
            if ($episode->episode_number <= $freeEpisodesCount) {
                return [
                    'has_access' => true,
                    'reason' => 'free_episode_limit',
                    'message' => 'قسمت در محدوده رایگان است'
                ];
            }

            return [
                'has_access' => false,
                'reason' => 'premium_required',
                'message' => 'برای دسترسی به این قسمت اشتراک فعال نیاز است'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check episode access', [
                'user_id' => $userId,
                'episode_id' => $episodeId,
                'error' => $e->getMessage()
            ]);

            return [
                'has_access' => false,
                'reason' => 'error',
                'message' => 'خطا در بررسی دسترسی'
            ];
        }
    }

    /**
     * Get user's access level
     */
    public function getUserAccessLevel(int $userId): array
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return [
                    'level' => 'none',
                    'has_premium' => false,
                    'subscription' => null,
                    'expires_at' => null,
                    'days_remaining' => 0
                ];
            }

            $activeSubscription = Subscription::where('user_id', $userId)
                                             ->where('status', 'active')
                                             ->where('end_date', '>', now())
                                             ->first();

            $trialSubscription = Subscription::where('user_id', $userId)
                                           ->where('status', 'trial')
                                           ->where('end_date', '>', now())
                                           ->first();

            if ($activeSubscription) {
                $daysRemaining = Carbon::parse($activeSubscription->end_date)->diffInDays(now(), false);
                return [
                    'level' => 'premium',
                    'has_premium' => true,
                    'subscription' => $activeSubscription->summary,
                    'expires_at' => $activeSubscription->end_date,
                    'days_remaining' => max(0, $daysRemaining),
                    'type' => $activeSubscription->type
                ];
            }

            if ($trialSubscription) {
                $daysRemaining = Carbon::parse($trialSubscription->end_date)->diffInDays(now(), false);
                return [
                    'level' => 'trial',
                    'has_premium' => true,
                    'subscription' => $trialSubscription->summary,
                    'expires_at' => $trialSubscription->end_date,
                    'days_remaining' => max(0, $daysRemaining),
                    'type' => 'trial'
                ];
            }

            return [
                'level' => 'free',
                'has_premium' => false,
                'subscription' => null,
                'expires_at' => null,
                'days_remaining' => 0
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get user access level', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'level' => 'none',
                'has_premium' => false,
                'subscription' => null,
                'expires_at' => null,
                'days_remaining' => 0
            ];
        }
    }

    /**
     * Filter stories based on user access
     */
    public function filterStoriesByAccess(int $userId, $stories): array
    {
        try {
            $userAccessLevel = $this->getUserAccessLevel($userId);
            $filteredStories = [];

            foreach ($stories as $story) {
                $accessInfo = $this->canAccessStory($userId, $story->id);
                
                $storyData = $story->toArray();
                $storyData['access_info'] = $accessInfo;
                
                // Add access-specific metadata
                if ($accessInfo['has_access']) {
                    if ($accessInfo['reason'] === 'limited_free_access') {
                        $storyData['accessible_episodes'] = $accessInfo['free_episodes_count'];
                        $storyData['total_episodes'] = $story->total_episodes;
                    } else {
                        $storyData['accessible_episodes'] = $story->total_episodes;
                        $storyData['total_episodes'] = $story->total_episodes;
                    }
                } else {
                    $storyData['accessible_episodes'] = 0;
                    $storyData['total_episodes'] = $story->total_episodes;
                }

                $filteredStories[] = $storyData;
            }

            return $filteredStories;

        } catch (\Exception $e) {
            Log::error('Failed to filter stories by access', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Filter episodes based on user access
     */
    public function filterEpisodesByAccess(int $userId, $episodes): array
    {
        try {
            $filteredEpisodes = [];

            foreach ($episodes as $episode) {
                $accessInfo = $this->canAccessEpisode($userId, $episode->id);
                
                $episodeData = $episode->toArray();
                $episodeData['access_info'] = $accessInfo;
                
                $filteredEpisodes[] = $episodeData;
            }

            return $filteredEpisodes;

        } catch (\Exception $e) {
            Log::error('Failed to filter episodes by access', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if user can download content
     */
    public function canDownloadContent(int $userId, string $contentType, int $contentId): array
    {
        try {
            // Only premium users can download content
            if (!$this->hasPremiumAccess($userId)) {
                return [
                    'can_download' => false,
                    'reason' => 'premium_required',
                    'message' => 'برای دانلود محتوا اشتراک فعال نیاز است'
                ];
            }

            // Check specific content access
            if ($contentType === 'story') {
                $accessInfo = $this->canAccessStory($userId, $contentId);
            } elseif ($contentType === 'episode') {
                $accessInfo = $this->canAccessEpisode($userId, $contentId);
            } else {
                return [
                    'can_download' => false,
                    'reason' => 'invalid_content_type',
                    'message' => 'نوع محتوا نامعتبر است'
                ];
            }

            if (!$accessInfo['has_access']) {
                return [
                    'can_download' => false,
                    'reason' => $accessInfo['reason'],
                    'message' => $accessInfo['message']
                ];
            }

            return [
                'can_download' => true,
                'reason' => 'authorized',
                'message' => 'دسترسی به دانلود تایید شد'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check download access', [
                'user_id' => $userId,
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'can_download' => false,
                'reason' => 'error',
                'message' => 'خطا در بررسی دسترسی دانلود'
            ];
        }
    }

    /**
     * Get content access statistics
     */
    public function getAccessStatistics(): array
    {
        try {
            $totalUsers = User::count();
            $premiumUsers = Subscription::where('status', 'active')
                                       ->where('end_date', '>', now())
                                       ->distinct('user_id')
                                       ->count('user_id');
            
            $trialUsers = Subscription::where('status', 'trial')
                                    ->where('end_date', '>', now())
                                    ->distinct('user_id')
                                    ->count('user_id');

            $freeUsers = $totalUsers - $premiumUsers - $trialUsers;

            $totalStories = Story::count();
            $premiumStories = Story::where('is_premium', true)->count();
            $freeStories = $totalStories - $premiumStories;

            $totalEpisodes = Episode::count();
            $premiumEpisodes = Episode::where('is_premium', true)->count();
            $freeEpisodes = $totalEpisodes - $premiumEpisodes;

            return [
                'users' => [
                    'total' => $totalUsers,
                    'premium' => $premiumUsers,
                    'trial' => $trialUsers,
                    'free' => $freeUsers,
                    'premium_percentage' => $totalUsers > 0 ? round(($premiumUsers / $totalUsers) * 100, 2) : 0
                ],
                'content' => [
                    'stories' => [
                        'total' => $totalStories,
                        'premium' => $premiumStories,
                        'free' => $freeStories,
                        'premium_percentage' => $totalStories > 0 ? round(($premiumStories / $totalStories) * 100, 2) : 0
                    ],
                    'episodes' => [
                        'total' => $totalEpisodes,
                        'premium' => $premiumEpisodes,
                        'free' => $freeEpisodes,
                        'premium_percentage' => $totalEpisodes > 0 ? round(($premiumEpisodes / $totalEpisodes) * 100, 2) : 0
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get access statistics', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if user can access premium features
     */
    public function canAccessPremiumFeatures(int $userId): array
    {
        try {
            $accessLevel = $this->getUserAccessLevel($userId);
            
            $features = [
                'unlimited_access' => $accessLevel['has_premium'],
                'download_content' => $accessLevel['has_premium'],
                'offline_access' => $accessLevel['has_premium'],
                'ad_free' => $accessLevel['has_premium'],
                'priority_support' => $accessLevel['has_premium'],
                'early_access' => $accessLevel['has_premium'],
                'exclusive_content' => $accessLevel['has_premium']
            ];

            return [
                'has_premium' => $accessLevel['has_premium'],
                'access_level' => $accessLevel['level'],
                'features' => $features,
                'subscription' => $accessLevel['subscription']
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check premium features access', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'has_premium' => false,
                'access_level' => 'none',
                'features' => [
                    'unlimited_access' => false,
                    'download_content' => false,
                    'offline_access' => false,
                    'ad_free' => false,
                    'priority_support' => false,
                    'early_access' => false,
                    'exclusive_content' => false
                ],
                'subscription' => null
            ];
        }
    }

    /**
     * Validate content access for API requests
     */
    public function validateContentAccess(int $userId, string $contentType, int $contentId): array
    {
        try {
            if ($contentType === 'story') {
                return $this->canAccessStory($userId, $contentId);
            } elseif ($contentType === 'episode') {
                return $this->canAccessEpisode($userId, $contentId);
            } else {
                return [
                    'has_access' => false,
                    'reason' => 'invalid_content_type',
                    'message' => 'نوع محتوا نامعتبر است'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to validate content access', [
                'user_id' => $userId,
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'has_access' => false,
                'reason' => 'error',
                'message' => 'خطا در اعتبارسنجی دسترسی'
            ];
        }
    }
}
