<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'affiliate_partner_id',
        'content_type',
        'platform',
        'content_url',
        'content_text',
        'media_urls',
        'hashtags',
        'views',
        'likes',
        'comments',
        'shares',
        'clicks',
        'conversions',
        'engagement_rate',
        'status',
        'rejection_reason',
        'published_at',
        'approved_at',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'hashtags' => 'array',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'engagement_rate' => 'decimal:2',
    ];

    /**
     * Get the campaign that owns the content
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(InfluencerCampaign::class, 'campaign_id');
    }

    /**
     * Get the affiliate partner that created the content
     */
    public function affiliatePartner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_partner_id');
    }

    /**
     * Approve the content
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the content
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark content as published
     */
    public function markAsPublished(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Update engagement metrics
     */
    public function updateEngagementMetrics(array $metrics): void
    {
        $this->update([
            'views' => $metrics['views'] ?? $this->views,
            'likes' => $metrics['likes'] ?? $this->likes,
            'comments' => $metrics['comments'] ?? $this->comments,
            'shares' => $metrics['shares'] ?? $this->shares,
            'clicks' => $metrics['clicks'] ?? $this->clicks,
            'conversions' => $metrics['conversions'] ?? $this->conversions,
            'engagement_rate' => $this->calculateEngagementRate(),
        ]);
    }

    /**
     * Calculate engagement rate
     */
    private function calculateEngagementRate(): float
    {
        if ($this->views === 0) {
            return 0;
        }

        $totalEngagement = $this->likes + $this->comments + $this->shares;
        return round(($totalEngagement / $this->views) * 100, 2);
    }

    /**
     * Check if content is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if content is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if content is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope to get published content
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get approved content
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get pending content
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get content by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get content by campaign
     */
    public function scopeByCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope to get content by partner
     */
    public function scopeByPartner($query, int $partnerId)
    {
        return $query->where('affiliate_partner_id', $partnerId);
    }

    /**
     * Scope to get high engagement content
     */
    public function scopeHighEngagement($query, float $minRate = 5.0)
    {
        return $query->where('engagement_rate', '>=', $minRate);
    }

    /**
     * Get content performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'views' => $this->views,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'shares' => $this->shares,
            'clicks' => $this->clicks,
            'conversions' => $this->conversions,
            'engagement_rate' => $this->engagement_rate,
            'click_through_rate' => $this->views > 0 ? round(($this->clicks / $this->views) * 100, 2) : 0,
            'conversion_rate' => $this->clicks > 0 ? round(($this->conversions / $this->clicks) * 100, 2) : 0,
        ];
    }

    /**
     * Get content statistics for campaign
     */
    public static function getCampaignStatistics(int $campaignId): array
    {
        $content = self::byCampaign($campaignId);
        
        return [
            'total_content' => $content->count(),
            'published_content' => $content->published()->count(),
            'pending_content' => $content->pending()->count(),
            'approved_content' => $content->approved()->count(),
            'rejected_content' => $content->where('status', 'rejected')->count(),
            'total_views' => $content->sum('views'),
            'total_likes' => $content->sum('likes'),
            'total_comments' => $content->sum('comments'),
            'total_shares' => $content->sum('shares'),
            'total_clicks' => $content->sum('clicks'),
            'total_conversions' => $content->sum('conversions'),
            'average_engagement_rate' => $content->avg('engagement_rate'),
            'high_engagement_content' => $content->highEngagement()->count(),
        ];
    }

    /**
     * Get content statistics for partner
     */
    public static function getPartnerStatistics(int $partnerId): array
    {
        $content = self::byPartner($partnerId);
        
        return [
            'total_content' => $content->count(),
            'published_content' => $content->published()->count(),
            'pending_content' => $content->pending()->count(),
            'approved_content' => $content->approved()->count(),
            'rejected_content' => $content->where('status', 'rejected')->count(),
            'total_views' => $content->sum('views'),
            'total_likes' => $content->sum('likes'),
            'total_comments' => $content->sum('comments'),
            'total_shares' => $content->sum('shares'),
            'total_clicks' => $content->sum('clicks'),
            'total_conversions' => $content->sum('conversions'),
            'average_engagement_rate' => $content->avg('engagement_rate'),
            'by_platform' => $content->groupBy('platform')->selectRaw('platform, count(*) as count')->get(),
        ];
    }

    /**
     * Get global content statistics
     */
    public static function getGlobalStatistics(): array
    {
        return [
            'total_content' => self::count(),
            'published_content' => self::published()->count(),
            'pending_content' => self::pending()->count(),
            'approved_content' => self::approved()->count(),
            'rejected_content' => self::where('status', 'rejected')->count(),
            'total_views' => self::sum('views'),
            'total_likes' => self::sum('likes'),
            'total_comments' => self::sum('comments'),
            'total_shares' => self::sum('shares'),
            'total_clicks' => self::sum('clicks'),
            'total_conversions' => self::sum('conversions'),
            'average_engagement_rate' => self::avg('engagement_rate'),
            'by_platform' => self::groupBy('platform')->selectRaw('platform, count(*) as count')->get(),
            'by_content_type' => self::groupBy('content_type')->selectRaw('content_type, count(*) as count')->get(),
        ];
    }
}
