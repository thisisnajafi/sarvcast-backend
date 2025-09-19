<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SponsoredContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'sponsorship_id',
        'story_id',
        'episode_id',
        'content_type',
        'content_title',
        'content_description',
        'sponsor_message',
        'brand_logo_url',
        'brand_website_url',
        'content_media',
        'placement_type',
        'display_duration',
        'display_frequency',
        'start_date',
        'end_date',
        'status',
        'rejection_reason',
        'approved_at',
        'published_at',
        'impressions',
        'clicks',
        'conversions',
        'ctr',
        'conversion_rate',
    ];

    protected $casts = [
        'content_media' => 'array',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'ctr' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    /**
     * Get the sponsorship that owns the content
     */
    public function sponsorship(): BelongsTo
    {
        return $this->belongsTo(CorporateSponsorship::class, 'sponsorship_id');
    }

    /**
     * Get the story that owns the content
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the episode that owns the content
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Get the analytics for this content
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(SponsorshipAnalytics::class, 'sponsored_content_id');
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
     * Publish the content
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'active',
            'published_at' => now(),
        ]);
    }

    /**
     * Pause the content
     */
    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Complete the content
     */
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Check if content is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    /**
     * Check if content is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Get content duration in days
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
     * Update performance metrics
     */
    public function updateMetrics(array $metrics): void
    {
        $this->update([
            'impressions' => $metrics['impressions'] ?? $this->impressions,
            'clicks' => $metrics['clicks'] ?? $this->clicks,
            'conversions' => $metrics['conversions'] ?? $this->conversions,
            'ctr' => $this->calculateCTR(),
            'conversion_rate' => $this->calculateConversionRate(),
        ]);
    }

    /**
     * Calculate click-through rate
     */
    private function calculateCTR(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }
        
        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }
        
        return round(($this->conversions / $this->clicks) * 100, 2);
    }

    /**
     * Record analytics event
     */
    public function recordEvent(string $eventType, int $userId = null, array $metadata = []): void
    {
        $this->analytics()->create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'event_timestamp' => now(),
            'user_agent' => request()->header('User-Agent'),
            'ip_address' => request()->ip(),
            'device_type' => $this->getDeviceType(),
            'platform' => $this->getPlatform(),
            'metadata' => $metadata,
        ]);

        // Update metrics based on event type
        switch ($eventType) {
            case 'impression':
                $this->increment('impressions');
                break;
            case 'click':
                $this->increment('clicks');
                break;
            case 'conversion':
                $this->increment('conversions');
                break;
        }

        // Recalculate rates
        $this->update([
            'ctr' => $this->calculateCTR(),
            'conversion_rate' => $this->calculateConversionRate(),
        ]);
    }

    /**
     * Get device type from user agent
     */
    private function getDeviceType(): string
    {
        $userAgent = request()->header('User-Agent', '');
        
        if (preg_match('/mobile/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet/i', $userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Get platform from user agent
     */
    private function getPlatform(): string
    {
        $userAgent = request()->header('User-Agent', '');
        
        if (preg_match('/android/i', $userAgent)) {
            return 'android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'ios';
        } else {
            return 'web';
        }
    }

    /**
     * Scope to get active content
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Scope to get expired content
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope to get content by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Scope to get content by placement
     */
    public function scopeByPlacement($query, string $placement)
    {
        return $query->where('placement_type', $placement);
    }

    /**
     * Scope to get content by sponsorship
     */
    public function scopeBySponsorship($query, int $sponsorshipId)
    {
        return $query->where('sponsorship_id', $sponsorshipId);
    }

    /**
     * Scope to get high performing content
     */
    public function scopeHighPerforming($query, float $minCTR = 2.0)
    {
        return $query->where('ctr', '>=', $minCTR);
    }

    /**
     * Get content types
     */
    public static function getContentTypes(): array
    {
        return [
            'story' => 'داستان',
            'episode' => 'قسمت',
            'banner' => 'بنر',
            'popup' => 'پاپ‌آپ',
            'notification' => 'اعلان',
            'dedicated_content' => 'محتوای اختصاصی',
        ];
    }

    /**
     * Get placement types
     */
    public static function getPlacementTypes(): array
    {
        return [
            'pre_roll' => 'قبل از شروع',
            'mid_roll' => 'وسط محتوا',
            'post_roll' => 'بعد از پایان',
            'banner' => 'بنر',
            'popup' => 'پاپ‌آپ',
            'notification' => 'اعلان',
            'dedicated_content' => 'محتوای اختصاصی',
        ];
    }

    /**
     * Get content statistics for sponsorship
     */
    public static function getSponsorshipStatistics(int $sponsorshipId): array
    {
        $content = self::bySponsorship($sponsorshipId);
        
        return [
            'total_content' => $content->count(),
            'active_content' => $content->active()->count(),
            'expired_content' => $content->expired()->count(),
            'pending_content' => $content->where('status', 'pending_approval')->count(),
            'approved_content' => $content->where('status', 'approved')->count(),
            'rejected_content' => $content->where('status', 'rejected')->count(),
            'total_impressions' => $content->sum('impressions'),
            'total_clicks' => $content->sum('clicks'),
            'total_conversions' => $content->sum('conversions'),
            'average_ctr' => $content->avg('ctr'),
            'average_conversion_rate' => $content->avg('conversion_rate'),
            'high_performing_content' => $content->highPerforming()->count(),
        ];
    }

    /**
     * Get global content statistics
     */
    public static function getGlobalStatistics(): array
    {
        return [
            'total_content' => self::count(),
            'active_content' => self::active()->count(),
            'expired_content' => self::expired()->count(),
            'total_impressions' => self::sum('impressions'),
            'total_clicks' => self::sum('clicks'),
            'total_conversions' => self::sum('conversions'),
            'average_ctr' => self::avg('ctr'),
            'average_conversion_rate' => self::avg('conversion_rate'),
            'by_content_type' => self::groupBy('content_type')->count(),
            'by_placement_type' => self::groupBy('placement_type')->count(),
            'high_performing_content' => self::highPerforming()->count(),
        ];
    }
}
