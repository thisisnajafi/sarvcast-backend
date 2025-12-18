<?php

namespace App;

use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;

trait UserAnalytics
{
    /**
     * Update user analytics when they perform actions
     */
    public function updateAnalytics(string $action, array $data = []): void
    {
        $updates = [];

        switch ($action) {
            case 'play':
                $updates['total_sessions'] = $this->total_sessions + 1;
                $updates['total_play_time'] = $this->total_play_time + ($data['duration'] ?? 0);
                $updates['last_activity_at'] = now();
                break;

            case 'favorite':
                $updates['total_favorites'] = $this->total_favorites + ($data['increment'] ? 1 : -1);
                $updates['last_activity_at'] = now();
                break;

            case 'rating':
                $updates['total_ratings'] = $this->total_ratings + 1;
                $updates['last_activity_at'] = now();
                break;

            case 'purchase':
                $updates['total_spent'] = $this->total_spent + ($data['amount'] ?? 0);
                $updates['last_purchase_at'] = now();
                
                if (!$this->first_purchase_at) {
                    $updates['first_purchase_at'] = now();
                }
                break;

            case 'subscription':
                $updates['last_activity_at'] = now();
                break;

            case 'login':
                $updates['last_activity_at'] = now();
                break;
        }

        if (!empty($updates)) {
            $this->update($updates);
        }
    }

    /**
     * Get user engagement score
     */
    public function getEngagementScore(): float
    {
        $score = 0;

        // Session frequency (0-30 points)
        $sessionScore = min(30, ($this->total_sessions / 10) * 30);
        $score += $sessionScore;

        // Play time (0-25 points)
        $playTimeScore = min(25, ($this->total_play_time / 3600) * 25); // Convert to hours
        $score += $playTimeScore;

        // Favorites (0-20 points)
        $favoritesScore = min(20, $this->total_favorites * 2);
        $score += $favoritesScore;

        // Ratings (0-15 points)
        $ratingsScore = min(15, $this->total_ratings * 3);
        $score += $ratingsScore;

        // Spending (0-10 points)
        $spendingScore = min(10, ($this->total_spent / 100000) * 10); // 1000 Toman = 1 point
        $score += $spendingScore;

        return round($score, 2);
    }

    /**
     * Get user lifetime value
     */
    public function getLifetimeValue(): float
    {
        return $this->total_spent;
    }

    /**
     * Get user activity level
     */
    public function getActivityLevel(): string
    {
        $engagementScore = $this->getEngagementScore();

        if ($engagementScore >= 80) {
            return 'very_high';
        } elseif ($engagementScore >= 60) {
            return 'high';
        } elseif ($engagementScore >= 40) {
            return 'medium';
        } elseif ($engagementScore >= 20) {
            return 'low';
        } else {
            return 'very_low';
        }
    }

    /**
     * Get user segment
     */
    public function getUserSegment(): string
    {
        $lifetimeValue = $this->getLifetimeValue();
        $engagementScore = $this->getEngagementScore();

        if ($lifetimeValue >= 500000 && $engagementScore >= 60) { // 5000 Toman
            return 'champion';
        } elseif ($lifetimeValue >= 200000 && $engagementScore >= 40) { // 2000 Toman
            return 'loyal_customer';
        } elseif ($lifetimeValue >= 100000) { // 1000 Toman
            return 'potential_loyalist';
        } elseif ($engagementScore >= 60) {
            return 'new_customer';
        } elseif ($engagementScore >= 40) {
            return 'promising';
        } elseif ($engagementScore >= 20) {
            return 'needs_attention';
        } elseif ($lifetimeValue > 0) {
            return 'at_risk';
        } else {
            return 'cannot_lose_them';
        }
    }

    /**
     * Check if user is active (has activity in last 30 days)
     */
    public function isActive(): bool
    {
        return $this->last_activity_at && $this->last_activity_at->isAfter(now()->subDays(30));
    }

    /**
     * Check if user is churned (no activity in last 90 days)
     */
    public function isChurned(): bool
    {
        return !$this->last_activity_at || $this->last_activity_at->isBefore(now()->subDays(90));
    }

    /**
     * Get days since last activity
     */
    public function getDaysSinceLastActivity(): ?int
    {
        return $this->last_activity_at ? $this->last_activity_at->diffInDays(now()) : null;
    }

    /**
     * Get user acquisition cost (if you track marketing costs)
     */
    public function getAcquisitionCost(): float
    {
        // This would need to be calculated based on your marketing data
        // For now, return 0
        return 0;
    }

    /**
     * Get user retention rate
     */
    public function getRetentionRate(): float
    {
        $daysSinceRegistration = $this->created_at->diffInDays(now());
        
        if ($daysSinceRegistration < 7) {
            return 0; // Too early to calculate
        }

        $activeDays = PlayHistory::where('user_id', $this->id)
            ->distinct('created_at')
            ->count();

        return round(($activeDays / min($daysSinceRegistration, 30)) * 100, 2);
    }

    /**
     * Get average session duration
     */
    public function getAverageSessionDuration(): float
    {
        if ($this->total_sessions == 0) {
            return 0;
        }

        return round($this->total_play_time / $this->total_sessions, 2);
    }

    /**
     * Get user preferences based on activity
     */
    public function getUserPreferences(): array
    {
        $preferences = [];

        // Favorite categories
        $favoriteCategories = Favorite::where('user_id', $this->id)
            ->join('stories', 'favorites.story_id', '=', 'stories.id')
            ->join('categories', 'stories.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, COUNT(*) as count')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $preferences['favorite_categories'] = $favoriteCategories;

        // Preferred age groups
        $preferredAgeGroups = PlayHistory::where('user_id', $this->id)
            ->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
            ->join('stories', 'episodes.story_id', '=', 'stories.id')
            ->selectRaw('stories.age_group, COUNT(*) as count')
            ->groupBy('stories.age_group')
            ->orderBy('count', 'desc')
            ->get();

        $preferences['preferred_age_groups'] = $preferredAgeGroups;

        // Preferred content length
        $preferredLengths = PlayHistory::where('user_id', $this->id)
            ->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
            ->selectRaw('
                CASE 
                    WHEN episodes.duration < 300 THEN "short"
                    WHEN episodes.duration BETWEEN 300 AND 900 THEN "medium"
                    WHEN episodes.duration > 900 THEN "long"
                END as length_category,
                COUNT(*) as count
            ')
            ->groupBy('length_category')
            ->orderBy('count', 'desc')
            ->get();

        $preferences['preferred_lengths'] = $preferredLengths;

        return $preferences;
    }

    /**
     * Get user behavior patterns
     */
    public function getBehaviorPatterns(): array
    {
        $patterns = [];

        // Peak usage hours
        $peakHours = PlayHistory::where('user_id', $this->id)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get();

        $patterns['peak_hours'] = $peakHours;

        // Peak usage days
        $peakDays = PlayHistory::where('user_id', $this->id)
            ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('count', 'desc')
            ->get();

        $patterns['peak_days'] = $peakDays;

        // Content completion rate
        $totalPlays = PlayHistory::where('user_id', $this->id)->count();
        $completedPlays = PlayHistory::where('user_id', $this->id)
            ->where('progress', '>=', 90)
            ->count();

        $patterns['completion_rate'] = $totalPlays > 0 ? round(($completedPlays / $totalPlays) * 100, 2) : 0;

        return $patterns;
    }

    /**
     * Get user growth metrics
     */
    public function getGrowthMetrics(): array
    {
        $metrics = [];

        // Monthly activity
        $monthlyActivity = PlayHistory::where('user_id', $this->id)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $metrics['monthly_activity'] = $monthlyActivity;

        // Spending trend
        $spendingTrend = Payment::where('user_id', $this->id)
            ->where('status', 'completed')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $metrics['spending_trend'] = $spendingTrend;

        return $metrics;
    }

    /**
     * Get user risk score (likelihood to churn)
     */
    public function getRiskScore(): float
    {
        $score = 0;

        // Days since last activity (0-40 points)
        $daysSinceActivity = $this->getDaysSinceLastActivity();
        if ($daysSinceActivity) {
            $score += min(40, $daysSinceActivity);
        }

        // Low engagement (0-30 points)
        $engagementScore = $this->getEngagementScore();
        if ($engagementScore < 20) {
            $score += 30;
        } elseif ($engagementScore < 40) {
            $score += 20;
        } elseif ($engagementScore < 60) {
            $score += 10;
        }

        // No recent purchases (0-20 points)
        if (!$this->last_purchase_at || $this->last_purchase_at->isBefore(now()->subDays(60))) {
            $score += 20;
        }

        // Low session frequency (0-10 points)
        if ($this->total_sessions < 5) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Get user value score
     */
    public function getValueScore(): float
    {
        $score = 0;

        // Lifetime value (0-40 points)
        $lifetimeValue = $this->getLifetimeValue();
        $score += min(40, ($lifetimeValue / 100000) * 40); // 1000 Toman = 4 points

        // Engagement (0-30 points)
        $engagementScore = $this->getEngagementScore();
        $score += ($engagementScore / 100) * 30;

        // Retention (0-20 points)
        $retentionRate = $this->getRetentionRate();
        $score += ($retentionRate / 100) * 20;

        // Activity frequency (0-10 points)
        $activityFrequency = min(10, ($this->total_sessions / 20) * 10);
        $score += $activityFrequency;

        return round($score, 2);
    }
}