<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_partner_id',
        'user_id',
        'subscription_id',
        'subscription_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'commission_period_start',
        'commission_period_end',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'subscription_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_period_start' => 'date',
        'commission_period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the affiliate partner that owns the commission
     */
    public function affiliatePartner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_partner_id');
    }

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription that generated the commission
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Approve the commission
     */
    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Cancel the commission
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
        ]);
    }

    /**
     * Calculate commission amount
     */
    public static function calculateCommissionAmount(float $subscriptionAmount, float $commissionRate): float
    {
        return round($subscriptionAmount * ($commissionRate / 100), 2);
    }

    /**
     * Scope to get pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get commissions by partner
     */
    public function scopeByPartner($query, int $partnerId)
    {
        return $query->where('affiliate_partner_id', $partnerId);
    }

    /**
     * Scope to get commissions by period
     */
    public function scopeByPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('commission_period_start', [$startDate, $endDate]);
    }

    /**
     * Get commission statistics for partner
     */
    public static function getPartnerStatistics(int $partnerId, int $months = 12): array
    {
        $commissions = self::byPartner($partnerId)
            ->where('commission_period_start', '>=', now()->subMonths($months))
            ->get();

        return [
            'total_commissions' => $commissions->count(),
            'total_amount' => $commissions->sum('commission_amount'),
            'paid_amount' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'pending_amount' => $commissions->where('status', 'pending')->sum('commission_amount'),
            'average_commission' => $commissions->avg('commission_amount'),
            'conversion_rate' => $commissions->count() > 0 ? 
                round(($commissions->where('status', 'paid')->count() / $commissions->count()) * 100, 2) : 0,
        ];
    }

    /**
     * Get global commission statistics
     */
    public static function getGlobalStatistics(int $months = 12): array
    {
        $commissions = self::where('commission_period_start', '>=', now()->subMonths($months))->get();

        return [
            'total_commissions' => $commissions->count(),
            'total_amount' => $commissions->sum('commission_amount'),
            'paid_amount' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'pending_amount' => $commissions->where('status', 'pending')->sum('commission_amount'),
            'average_commission' => $commissions->avg('commission_amount'),
            'by_type' => $commissions->groupBy('affiliatePartner.type')->map->sum('commission_amount'),
            'by_tier' => $commissions->groupBy('affiliatePartner.tier')->map->sum('commission_amount'),
        ];
    }
}
