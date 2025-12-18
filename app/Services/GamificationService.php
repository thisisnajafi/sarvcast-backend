<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\UserPoints;
use App\Models\PointTransaction;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\UserStreak;
use App\Models\Challenge;
use App\Models\UserChallenge;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    /**
     * Gamification system disabled flag
     */
    private bool $disabled = true;

    /**
     * Award points to user
     */
    public function awardPoints(int $userId, int $points, string $sourceType, int $sourceId = null, string $description = '', array $metadata = []): array
    {
        if ($this->disabled) {
            return ['success' => false, 'message' => 'Gamification system is disabled'];
        }
        
        try {
            DB::beginTransaction();

            $userPoints = UserPoints::firstOrCreate(['user_id' => $userId]);
            
            $userPoints->increment('total_points', $points);
            $userPoints->increment('available_points', $points);
            $userPoints->increment('experience', $points);
            $userPoints->last_activity_at = now();
            $userPoints->save();

            // Create point transaction
            PointTransaction::create([
                'user_id' => $userId,
                'transaction_type' => 'earned',
                'points' => $points,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'metadata' => $metadata,
                'transacted_at' => now()
            ]);

            // Check for level up
            $levelUp = $this->checkLevelUp($userId);

            // Check for achievements
            $achievements = $this->checkAchievements($userId, $sourceType, $sourceId);

            DB::commit();

            // Clear cache
            $this->clearUserCache($userId);

            return [
                'success' => true,
                'points_awarded' => $points,
                'total_points' => $userPoints->total_points,
                'level_up' => $levelUp,
                'achievements' => $achievements
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error awarding points to user {$userId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در اعطای امتیاز: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check and unlock achievements for user
     */
    public function checkAchievements(int $userId, string $sourceType, int $sourceId = null): array
    {
        $unlockedAchievements = [];

        try {
            $achievements = Achievement::where('is_active', true)
                ->where('type', '!=', 'special') // Skip special achievements for now
                ->get();

            foreach ($achievements as $achievement) {
                if ($this->isAchievementEligible($userId, $achievement, $sourceType, $sourceId)) {
                    $unlocked = $this->unlockAchievement($userId, $achievement);
                    if ($unlocked) {
                        $unlockedAchievements[] = $achievement;
                    }
                }
            }

            return $unlockedAchievements;

        } catch (\Exception $e) {
            Log::error("Error checking achievements for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Unlock achievement for user
     */
    public function unlockAchievement(int $userId, Achievement $achievement): bool
    {
        if ($this->disabled) {
            return false;
        }
        
        try {
            // Check if already unlocked
            $existing = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->first();

            if ($existing) {
                return false;
            }

            // Unlock achievement
            UserAchievement::create([
                'user_id' => $userId,
                'achievement_id' => $achievement->id,
                'unlocked_at' => now(),
                'progress_data' => $this->getAchievementProgressData($userId, $achievement)
            ]);

            // Award points if achievement has points
            if ($achievement->points > 0) {
                $this->awardPoints(
                    $userId,
                    $achievement->points,
                    'achievement',
                    $achievement->id,
                    "دستاورد '{$achievement->name}' باز شد",
                    ['achievement' => $achievement->toArray()]
                );
            }

            // Check for badge eligibility
            $this->checkBadges($userId);

            Log::info("Achievement '{$achievement->name}' unlocked for user {$userId}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error unlocking achievement for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user streak
     */
    public function updateStreak(int $userId, string $streakType, bool $increment = true): array
    {
        if ($this->disabled) {
            return ['success' => false, 'message' => 'Gamification system is disabled'];
        }
        
        try {
            $streak = UserStreak::firstOrCreate([
                'user_id' => $userId,
                'streak_type' => $streakType
            ]);

            $today = now()->toDateString();
            $yesterday = now()->subDay()->toDateString();

            if ($increment) {
                // Check if streak should continue
                if ($streak->last_activity_date === $today) {
                    // Already updated today
                    return [
                        'success' => true,
                        'current_streak' => $streak->current_streak,
                        'streak_continued' => false
                    ];
                } elseif ($streak->last_activity_date === $yesterday) {
                    // Continue streak
                    $streak->increment('current_streak');
                    $streak->last_activity_date = $today;
                    
                    if ($streak->current_streak > $streak->longest_streak) {
                        $streak->longest_streak = $streak->current_streak;
                    }
                    
                    $streak->save();
                    
                    return [
                        'success' => true,
                        'current_streak' => $streak->current_streak,
                        'streak_continued' => true,
                        'new_record' => $streak->current_streak > $streak->longest_streak
                    ];
                } else {
                    // Start new streak
                    $streak->current_streak = 1;
                    $streak->last_activity_date = $today;
                    $streak->streak_start_date = $today;
                    $streak->save();
                    
                    return [
                        'success' => true,
                        'current_streak' => 1,
                        'streak_continued' => false,
                        'new_streak' => true
                    ];
                }
            } else {
                // Reset streak
                $streak->current_streak = 0;
                $streak->last_activity_date = $today;
                $streak->save();
                
                return [
                    'success' => true,
                    'current_streak' => 0,
                    'streak_reset' => true
                ];
            }

        } catch (\Exception $e) {
            Log::error("Error updating streak for user {$userId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی استریک: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(string $slug, int $limit = 50): array
    {
        if ($this->disabled) {
            return ['success' => false, 'message' => 'Gamification system is disabled'];
        }
        
        return Cache::remember("leaderboard_{$slug}_{$limit}", 1800, function() use ($slug, $limit) {
            $leaderboard = Leaderboard::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$leaderboard) {
                return [
                    'success' => false,
                    'message' => 'جدول امتیازات یافت نشد'
                ];
            }

            $today = now()->toDateString();
            
            $entries = LeaderboardEntry::where('leaderboard_id', $leaderboard->id)
                ->where('period_date', $today)
                ->with(['user:id,name,avatar'])
                ->orderBy('rank')
                ->limit($limit)
                ->get();

            return [
                'success' => true,
                'leaderboard' => $leaderboard,
                'entries' => $entries,
                'total' => $entries->count(),
                'period_date' => $today
            ];
        });
    }

    /**
     * Update leaderboard
     */
    public function updateLeaderboard(string $slug): array
    {
        try {
            $leaderboard = Leaderboard::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$leaderboard) {
                return [
                    'success' => false,
                    'message' => 'جدول امتیازات یافت نشد'
                ];
            }

            $today = now()->toDateString();
            
            // Clear existing entries for today
            LeaderboardEntry::where('leaderboard_id', $leaderboard->id)
                ->where('period_date', $today)
                ->delete();

            // Get users and their scores based on leaderboard criteria
            $scores = $this->calculateLeaderboardScores($leaderboard);
            
            // Sort by score and assign ranks
            arsort($scores);
            $rank = 1;
            
            foreach ($scores as $userId => $score) {
                LeaderboardEntry::create([
                    'leaderboard_id' => $leaderboard->id,
                    'user_id' => $userId,
                    'rank' => $rank,
                    'score' => $score,
                    'period_date' => $today,
                    'updated_at' => now()
                ]);
                
                $rank++;
            }

            // Clear cache
            Cache::forget("leaderboard_{$slug}_*");

            return [
                'success' => true,
                'message' => 'جدول امتیازات به‌روزرسانی شد',
                'total_entries' => count($scores)
            ];

        } catch (\Exception $e) {
            Log::error("Error updating leaderboard {$slug}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی جدول امتیازات: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's gamification profile
     */
    public function getUserProfile(int $userId): array
    {
        return Cache::remember("user_gamification_profile_{$userId}", 3600, function() use ($userId) {
            $userPoints = UserPoints::where('user_id', $userId)->first();
            $achievements = UserAchievement::where('user_id', $userId)
                ->with(['achievement'])
                ->orderBy('unlocked_at', 'desc')
                ->get();
            
            $streaks = UserStreak::where('user_id', $userId)->get();
            $badges = UserBadge::where('user_id', $userId)
                ->with(['badge'])
                ->orderBy('earned_at', 'desc')
                ->get();

            return [
                'user_points' => $userPoints,
                'achievements' => $achievements,
                'streaks' => $streaks,
                'badges' => $badges,
                'level_info' => $this->getLevelInfo($userPoints->level ?? 1),
                'recent_activity' => $this->getRecentActivity($userId)
            ];
        });
    }

    /**
     * Get available challenges
     */
    public function getAvailableChallenges(int $userId, int $limit = 10): array
    {
        return Cache::remember("available_challenges_{$userId}_{$limit}", 1800, function() use ($userId, $limit) {
            $today = now()->toDateString();
            
            $challenges = Challenge::where('is_active', true)
                ->where('start_date', '<=', $today)
                ->where(function($query) use ($today) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', $today);
                })
                ->whereDoesntHave('userChallenges', function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->whereIn('status', ['active', 'completed']);
                })
                ->orderBy('start_date', 'desc')
                ->limit($limit)
                ->get();

            return $challenges->map(function($challenge) use ($userId) {
                return [
                    'challenge' => $challenge,
                    'progress' => $this->getChallengeProgress($userId, $challenge),
                    'can_join' => $this->canJoinChallenge($userId, $challenge)
                ];
            })->toArray();
        });
    }

    /**
     * Join challenge
     */
    public function joinChallenge(int $userId, int $challengeId): array
    {
        try {
            $challenge = Challenge::find($challengeId);
            if (!$challenge) {
                return [
                    'success' => false,
                    'message' => 'چالش یافت نشد'
                ];
            }

            if (!$this->canJoinChallenge($userId, $challenge)) {
                return [
                    'success' => false,
                    'message' => 'نمی‌توانید در این چالش شرکت کنید'
                ];
            }

            UserChallenge::create([
                'user_id' => $userId,
                'challenge_id' => $challengeId,
                'status' => 'active',
                'progress' => [],
                'started_at' => now()
            ]);

            // Clear cache
            Cache::forget("available_challenges_{$userId}_*");
            Cache::forget("user_challenges_{$userId}_*");

            return [
                'success' => true,
                'message' => 'با موفقیت در چالش شرکت کردید'
            ];

        } catch (\Exception $e) {
            Log::error("Error joining challenge for user {$userId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در شرکت در چالش: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if achievement is eligible for user
     */
    private function isAchievementEligible(int $userId, Achievement $achievement, string $sourceType, int $sourceId = null): bool
    {
        $criteria = $achievement->criteria;
        
        switch ($achievement->type) {
            case 'single':
                return $this->checkSingleAchievement($userId, $criteria, $sourceType, $sourceId);
            case 'cumulative':
                return $this->checkCumulativeAchievement($userId, $criteria);
            case 'streak':
                return $this->checkStreakAchievement($userId, $criteria);
            default:
                return false;
        }
    }

    /**
     * Check single achievement
     */
    private function checkSingleAchievement(int $userId, array $criteria, string $sourceType, int $sourceId = null): bool
    {
        if ($criteria['action'] !== $sourceType) {
            return false;
        }

        // Check if this specific action qualifies
        return $this->evaluateCriteria($userId, $criteria, $sourceId);
    }

    /**
     * Check cumulative achievement
     */
    private function checkCumulativeAchievement(int $userId, array $criteria): bool
    {
        $count = $this->getUserActionCount($userId, $criteria['action']);
        return $count >= $criteria['count'];
    }

    /**
     * Check streak achievement
     */
    private function checkStreakAchievement(int $userId, array $criteria): bool
    {
        $streak = UserStreak::where('user_id', $userId)
            ->where('streak_type', $criteria['streak_type'])
            ->first();

        if (!$streak) {
            return false;
        }

        return $streak->current_streak >= $criteria['required_streak'];
    }

    /**
     * Evaluate criteria
     */
    private function evaluateCriteria(int $userId, array $criteria, int $sourceId = null): bool
    {
        // This would contain specific logic for different criteria types
        // For now, return true as a placeholder
        return true;
    }

    /**
     * Get user action count
     */
    private function getUserActionCount(int $userId, string $action): int
    {
        return match($action) {
            'play' => \App\Models\PlayHistory::where('user_id', $userId)->count(),
            'favorite' => \App\Models\Favorite::where('user_id', $userId)->count(),
            'share' => \App\Models\ContentShare::where('user_id', $userId)->count(),
            'comment' => \App\Models\UserComment::where('user_id', $userId)->count(),
            'follow' => \App\Models\UserFollow::where('follower_id', $userId)->count(),
            default => 0
        };
    }

    /**
     * Check level up
     */
    private function checkLevelUp(int $userId): ?array
    {
        $userPoints = UserPoints::where('user_id', $userId)->first();
        if (!$userPoints) {
            return null;
        }

        $currentLevel = $userPoints->level;
        $newLevel = $this->calculateLevel($userPoints->experience);
        
        if ($newLevel > $currentLevel) {
            $userPoints->level = $newLevel;
            $userPoints->save();
            
            return [
                'level_up' => true,
                'old_level' => $currentLevel,
                'new_level' => $newLevel,
                'level_info' => $this->getLevelInfo($newLevel)
            ];
        }

        return null;
    }

    /**
     * Calculate level from experience
     */
    private function calculateLevel(int $experience): int
    {
        // Simple level calculation: 100 XP per level
        return max(1, floor($experience / 100) + 1);
    }

    /**
     * Get level information
     */
    private function getLevelInfo(int $level): array
    {
        $currentLevelXP = ($level - 1) * 100;
        $nextLevelXP = $level * 100;
        
        return [
            'level' => $level,
            'current_level_xp' => $currentLevelXP,
            'next_level_xp' => $nextLevelXP,
            'xp_needed' => $nextLevelXP - $currentLevelXP
        ];
    }

    /**
     * Check badges
     */
    private function checkBadges(int $userId): void
    {
        $badges = Badge::where('is_active', true)->get();
        
        foreach ($badges as $badge) {
            if ($this->isBadgeEligible($userId, $badge)) {
                $this->awardBadge($userId, $badge);
            }
        }
    }

    /**
     * Check if user is eligible for badge
     */
    private function isBadgeEligible(int $userId, Badge $badge): bool
    {
        // Check if already has badge
        $existing = UserBadge::where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->exists();
            
        if ($existing) {
            return false;
        }

        // Check requirements
        $requirements = $badge->requirements ?? [];
        
        foreach ($requirements as $requirement) {
            if (!$this->checkRequirement($userId, $requirement)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check requirement
     */
    private function checkRequirement(int $userId, array $requirement): bool
    {
        // This would contain specific logic for different requirement types
        return true;
    }

    /**
     * Award badge
     */
    private function awardBadge(int $userId, Badge $badge): void
    {
        UserBadge::create([
            'user_id' => $userId,
            'badge_id' => $badge->id,
            'earned_at' => now()
        ]);
    }

    /**
     * Calculate leaderboard scores
     */
    private function calculateLeaderboardScores(Leaderboard $leaderboard): array
    {
        $scores = [];
        
        switch ($leaderboard->type) {
            case 'points':
                $userPoints = UserPoints::all();
                foreach ($userPoints as $up) {
                    $scores[$up->user_id] = $up->total_points;
                }
                break;
            case 'listening_time':
                $listeningTimes = DB::table('play_histories')
                    ->select('user_id', DB::raw('SUM(duration) as total_duration'))
                    ->groupBy('user_id')
                    ->get();
                foreach ($listeningTimes as $lt) {
                    $scores[$lt->user_id] = $lt->total_duration;
                }
                break;
            case 'achievements':
                $achievementCounts = UserAchievement::select('user_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('user_id')
                    ->get();
                foreach ($achievementCounts as $ac) {
                    $scores[$ac->user_id] = $ac->count;
                }
                break;
        }
        
        return $scores;
    }

    /**
     * Get achievement progress data
     */
    private function getAchievementProgressData(int $userId, Achievement $achievement): array
    {
        return [
            'unlocked_at' => now()->toISOString(),
            'user_id' => $userId,
            'achievement_id' => $achievement->id
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity(int $userId): array
    {
        return PointTransaction::where('user_id', $userId)
            ->orderBy('transacted_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get challenge progress
     */
    private function getChallengeProgress(int $userId, Challenge $challenge): array
    {
        $userChallenge = UserChallenge::where('user_id', $userId)
            ->where('challenge_id', $challenge->id)
            ->first();

        if (!$userChallenge) {
            return ['progress' => 0, 'status' => 'not_started'];
        }

        return [
            'progress' => $userChallenge->progress,
            'status' => $userChallenge->status,
            'started_at' => $userChallenge->started_at,
            'completed_at' => $userChallenge->completed_at
        ];
    }

    /**
     * Check if user can join challenge
     */
    private function canJoinChallenge(int $userId, Challenge $challenge): bool
    {
        // Check if challenge is active
        if (!$challenge->is_active) {
            return false;
        }

        // Check date range
        $today = now()->toDateString();
        if ($challenge->start_date > $today) {
            return false;
        }

        if ($challenge->end_date && $challenge->end_date < $today) {
            return false;
        }

        // Check if already participating
        $existing = UserChallenge::where('user_id', $userId)
            ->where('challenge_id', $challenge->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if ($existing) {
            return false;
        }

        // Check max participants
        if ($challenge->max_participants) {
            $currentParticipants = UserChallenge::where('challenge_id', $challenge->id)
                ->whereIn('status', ['active', 'completed'])
                ->count();

            if ($currentParticipants >= $challenge->max_participants) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear user cache
     */
    private function clearUserCache(int $userId): void
    {
        $patterns = [
            "user_gamification_profile_{$userId}",
            "available_challenges_{$userId}_*",
            "user_challenges_{$userId}_*",
            "leaderboard_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
