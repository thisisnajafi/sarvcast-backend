<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorshipAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'sponsored_content_id',
        'user_id',
        'event_type',
        'event_timestamp',
        'user_agent',
        'ip_address',
        'device_type',
        'platform',
        'metadata',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the sponsored content that owns the analytics
     */
    public function sponsoredContent(): BelongsTo
    {
        return $this->belongsTo(SponsoredContent::class, 'sponsored_content_id');
    }

    /**
     * Get the user that triggered the event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get events by type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to get events by content
     */
    public function scopeByContent($query, int $contentId)
    {
        return $query->where('sponsored_content_id', $contentId);
    }

    /**
     * Scope to get events by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get events by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope to get events by device type
     */
    public function scopeByDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope to get events by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Get event types
     */
    public static function getEventTypes(): array
    {
        return [
            'impression' => 'نمایش',
            'click' => 'کلیک',
            'conversion' => 'تبدیل',
            'view_complete' => 'مشاهده کامل',
            'skip' => 'رد کردن',
            'hover' => 'هاور',
            'focus' => 'فوکوس',
        ];
    }

    /**
     * Get device types
     */
    public static function getDeviceTypes(): array
    {
        return [
            'mobile' => 'موبایل',
            'tablet' => 'تبلت',
            'desktop' => 'دسکتاپ',
        ];
    }

    /**
     * Get platforms
     */
    public static function getPlatforms(): array
    {
        return [
            'web' => 'وب',
            'android' => 'اندروید',
            'ios' => 'آی‌اواس',
        ];
    }

    /**
     * Get analytics summary for content
     */
    public static function getContentAnalytics(int $contentId, $startDate = null, $endDate = null): array
    {
        $query = self::byContent($contentId);
        
        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        $totalEvents = $query->count();
        $impressions = $query->byEventType('impression')->count();
        $clicks = $query->byEventType('click')->count();
        $conversions = $query->byEventType('conversion')->count();
        $viewCompletes = $query->byEventType('view_complete')->count();
        $skips = $query->byEventType('skip')->count();

        return [
            'total_events' => $totalEvents,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'view_completes' => $viewCompletes,
            'skips' => $skips,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            'completion_rate' => $impressions > 0 ? round(($viewCompletes / $impressions) * 100, 2) : 0,
            'skip_rate' => $impressions > 0 ? round(($skips / $impressions) * 100, 2) : 0,
            'by_device_type' => $query->groupBy('device_type')->selectRaw('device_type, count(*) as count')->get(),
            'by_platform' => $query->groupBy('platform')->selectRaw('platform, count(*) as count')->get(),
            'by_hour' => $query->selectRaw('HOUR(event_timestamp) as hour, count(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),
            'by_day' => $query->selectRaw('DATE(event_timestamp) as day, count(*) as count')
                ->groupBy('day')
                ->orderBy('day')
                ->get(),
        ];
    }

    /**
     * Get analytics summary for sponsorship
     */
    public static function getSponsorshipAnalytics(int $sponsorshipId, $startDate = null, $endDate = null): array
    {
        $query = self::join('sponsored_content', 'sponsorship_analytics.sponsored_content_id', '=', 'sponsored_content.id')
            ->where('sponsored_content.sponsorship_id', $sponsorshipId);
        
        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        $totalEvents = $query->count();
        $impressions = $query->byEventType('impression')->count();
        $clicks = $query->byEventType('click')->count();
        $conversions = $query->byEventType('conversion')->count();
        $viewCompletes = $query->byEventType('view_complete')->count();
        $skips = $query->byEventType('skip')->count();

        return [
            'total_events' => $totalEvents,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'view_completes' => $viewCompletes,
            'skips' => $skips,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            'completion_rate' => $impressions > 0 ? round(($viewCompletes / $impressions) * 100, 2) : 0,
            'skip_rate' => $impressions > 0 ? round(($skips / $impressions) * 100, 2) : 0,
            'by_device_type' => $query->groupBy('sponsorship_analytics.device_type')->selectRaw('sponsorship_analytics.device_type, count(*) as count')->get(),
            'by_platform' => $query->groupBy('sponsorship_analytics.platform')->selectRaw('sponsorship_analytics.platform, count(*) as count')->get(),
            'by_content_type' => $query->join('sponsored_content', 'sponsorship_analytics.sponsored_content_id', '=', 'sponsored_content.id')
                ->groupBy('sponsored_content.content_type')
                ->selectRaw('sponsored_content.content_type, count(*) as count')
                ->get(),
        ];
    }

    /**
     * Get global analytics summary
     */
    public static function getGlobalAnalytics($startDate = null, $endDate = null): array
    {
        $query = self::query();
        
        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        $totalEvents = $query->count();
        $impressions = $query->byEventType('impression')->count();
        $clicks = $query->byEventType('click')->count();
        $conversions = $query->byEventType('conversion')->count();
        $viewCompletes = $query->byEventType('view_complete')->count();
        $skips = $query->byEventType('skip')->count();

        return [
            'total_events' => $totalEvents,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'view_completes' => $viewCompletes,
            'skips' => $skips,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            'completion_rate' => $impressions > 0 ? round(($viewCompletes / $impressions) * 100, 2) : 0,
            'skip_rate' => $impressions > 0 ? round(($skips / $impressions) * 100, 2) : 0,
            'by_device_type' => $query->groupBy('device_type')->selectRaw('device_type, count(*) as count')->get(),
            'by_platform' => $query->groupBy('platform')->selectRaw('platform, count(*) as count')->get(),
            'by_event_type' => $query->groupBy('event_type')->selectRaw('event_type, count(*) as count')->get(),
            'by_hour' => $query->selectRaw('HOUR(event_timestamp) as hour, count(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),
            'by_day' => $query->selectRaw('DATE(event_timestamp) as day, count(*) as count')
                ->groupBy('day')
                ->orderBy('day')
                ->get(),
        ];
    }
}
