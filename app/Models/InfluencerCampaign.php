<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfluencerCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_partner_id',
        'campaign_name',
        'campaign_description',
        'campaign_type',
        'content_type',
        'required_posts',
        'required_stories',
        'compensation_per_post',
        'commission_rate',
        'start_date',
        'end_date',
        'status',
        'content_guidelines',
        'hashtags',
        'target_audience',
        'requires_approval',
    ];

    protected $casts = [
        'content_guidelines' => 'array',
        'hashtags' => 'array',
        'target_audience' => 'array',
        'requires_approval' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'compensation_per_post' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    /**
     * Get the affiliate partner that owns the campaign
     */
    public function affiliatePartner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_partner_id');
    }

    /**
     * Get the content created for this campaign
     */
    public function content(): HasMany
    {
        return $this->hasMany(InfluencerContent::class, 'campaign_id');
    }

    /**
     * Activate the campaign
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Pause the campaign
     */
    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Complete the campaign
     */
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Cancel the campaign
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if campaign is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    /**
     * Check if campaign is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Get campaign duration in days
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->end_date));
    }

    /**
     * Get campaign statistics
     */
    public function getStatistics(): array
    {
        $content = $this->content();
        
        return [
            'total_content' => $content->count(),
            'published_content' => $content->where('status', 'published')->count(),
            'pending_content' => $content->where('status', 'pending')->count(),
            'approved_content' => $content->where('status', 'approved')->count(),
            'rejected_content' => $content->where('status', 'rejected')->count(),
            'total_views' => $content->sum('views'),
            'total_likes' => $content->sum('likes'),
            'total_comments' => $content->sum('comments'),
            'total_shares' => $content->sum('shares'),
            'total_clicks' => $content->sum('clicks'),
            'total_conversions' => $content->sum('conversions'),
            'average_engagement_rate' => $content->avg('engagement_rate'),
            'total_compensation' => $content->where('status', 'published')->count() * $this->compensation_per_post,
        ];
    }

    /**
     * Scope to get active campaigns
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Scope to get expired campaigns
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope to get campaigns by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('campaign_type', $type);
    }

    /**
     * Scope to get campaigns by partner
     */
    public function scopeByPartner($query, int $partnerId)
    {
        return $query->where('affiliate_partner_id', $partnerId);
    }

    /**
     * Get campaign types
     */
    public static function getCampaignTypes(): array
    {
        return [
            'story_review' => 'بررسی داستان',
            'educational_content' => 'محتوای آموزشی',
            'cultural_preservation' => 'حفظ فرهنگ',
            'brand_partnership' => 'همکاری برند',
        ];
    }

    /**
     * Get content types
     */
    public static function getContentTypes(): array
    {
        return [
            'post' => 'پست',
            'story' => 'استوری',
            'reel' => 'ریل',
            'video' => 'ویدیو',
            'live' => 'لایو',
        ];
    }

    /**
     * Get platforms
     */
    public static function getPlatforms(): array
    {
        return [
            'instagram' => 'اینستاگرام',
            'telegram' => 'تلگرام',
            'youtube' => 'یوتیوب',
            'tiktok' => 'تیک‌تاک',
            'twitter' => 'توییتر',
            'linkedin' => 'لینکدین',
        ];
    }

    /**
     * Get tier-specific compensation rates
     */
    public static function getTierCompensationRates(): array
    {
        return [
            'micro' => [
                'per_post' => 15000, // IRR
                'per_story' => 5000,
                'commission_rate' => 20.00,
            ],
            'mid' => [
                'per_post' => 50000, // IRR
                'per_story' => 15000,
                'commission_rate' => 25.00,
            ],
            'macro' => [
                'per_post' => 200000, // IRR
                'per_story' => 50000,
                'commission_rate' => 30.00,
            ],
        ];
    }
}
