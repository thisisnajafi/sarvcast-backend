<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'gateway_fee',
        'net_amount',
        'exchange_rate',
        'payment_metadata',
        'processed_at',
        'refunded_at',
        'refund_reason',
        'refund_amount',
        'description',
        'metadata'
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'refund_amount' => 'decimal:2',
            'payment_metadata' => 'array',
            'metadata' => 'array',
            'processed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription that owns the payment.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Scope a query to only include completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include refunded payments.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope a query to only include payments by method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope a query to only include payments by gateway.
     */
    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    /**
     * Scope a query to only include payments in date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Get the net amount after fees.
     */
    public function getNetAmountAttribute($value): float
    {
        if ($value) {
            return $value;
        }
        
        return $this->amount - $this->gateway_fee;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, '.', ',') . ' ' . $this->currency;
    }

    /**
     * Get formatted net amount.
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 0, '.', ',') . ' ' . $this->currency;
    }

    /**
     * Get payment status in Persian.
     */
    public function getStatusInPersianAttribute(): string
    {
        return match($this->status) {
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            'pending' => 'در انتظار',
            'refunded' => 'برگشت خورده',
            'cancelled' => 'لغو شده',
            default => 'نامشخص'
        };
    }

    /**
     * Get payment method in Persian.
     */
    public function getPaymentMethodInPersianAttribute(): string
    {
        return match($this->payment_method) {
            'zarinpal' => 'زرین‌پال',
            'bank_transfer' => 'انتقال بانکی',
            'credit_card' => 'کارت اعتباری',
            'wallet' => 'کیف پول',
            default => $this->payment_method ?? 'نامشخص'
        };
    }

    /**
     * Calculate total revenue for a period.
     */
    public static function calculateTotalRevenue($startDate, $endDate, $currency = 'IRR'): float
    {
        return static::completed()
            ->inDateRange($startDate, $endDate)
            ->where('currency', $currency)
            ->sum('net_amount');
    }

    /**
     * Calculate average payment amount for a period.
     */
    public static function calculateAveragePaymentAmount($startDate, $endDate, $currency = 'IRR'): float
    {
        return static::completed()
            ->inDateRange($startDate, $endDate)
            ->where('currency', $currency)
            ->avg('net_amount') ?? 0;
    }

    /**
     * Get payment success rate for a period.
     */
    public static function getSuccessRate($startDate, $endDate): float
    {
        $totalPayments = static::inDateRange($startDate, $endDate)->count();
        
        if ($totalPayments === 0) {
            return 0;
        }

        $successfulPayments = static::completed()
            ->inDateRange($startDate, $endDate)
            ->count();

        return round(($successfulPayments / $totalPayments) * 100, 2);
    }

    /**
     * Get refund rate for a period.
     */
    public static function getRefundRate($startDate, $endDate): float
    {
        $completedPayments = static::completed()
            ->inDateRange($startDate, $endDate)
            ->count();

        if ($completedPayments === 0) {
            return 0;
        }

        $refundedPayments = static::refunded()
            ->inDateRange($startDate, $endDate)
            ->count();

        return round(($refundedPayments / $completedPayments) * 100, 2);
    }

    /**
     * Get payment distribution by method.
     */
    public static function getDistributionByMethod($startDate, $endDate)
    {
        return static::completed()
            ->inDateRange($startDate, $endDate)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(net_amount) as total_amount')
            ->groupBy('payment_method')
            ->get();
    }

    /**
     * Get payment distribution by gateway.
     */
    public static function getDistributionByGateway($startDate, $endDate)
    {
        return static::completed()
            ->inDateRange($startDate, $endDate)
            ->selectRaw('payment_gateway, COUNT(*) as count, SUM(net_amount) as total_amount')
            ->groupBy('payment_gateway')
            ->get();
    }

    /**
     * Get daily revenue trends.
     */
    public static function getDailyRevenueTrends($startDate, $endDate, $currency = 'IRR')
    {
        return static::completed()
            ->inDateRange($startDate, $endDate)
            ->where('currency', $currency)
            ->selectRaw('DATE(created_at) as date, SUM(net_amount) as revenue, COUNT(*) as payments')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get monthly revenue trends.
     */
    public static function getMonthlyRevenueTrends($startDate, $endDate, $currency = 'IRR')
    {
        return static::completed()
            ->inDateRange($startDate, $endDate)
            ->where('currency', $currency)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(net_amount) as revenue, COUNT(*) as payments')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}