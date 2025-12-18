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
        'billing_platform',
        'transaction_id',
        'gateway_fee',
        'net_amount',
        'exchange_rate',
        'payment_metadata',
        'processed_at',
        'refunded_at',
        'refund_reason',
        'refund_amount',
        'metadata',
        // In-app purchase fields
        'purchase_token',
        'order_id',
        'package_name',
        'product_id',
        'purchase_state',
        'purchase_time',
        'store_response',
        'is_acknowledged',
        'acknowledged_at',
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
     * Scope a query to only include cancelled payments.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include processing payments.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope a query to only include expired payments.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope a query to only include final payments (completed, failed, cancelled, refunded).
     */
    public function scopeFinal($query)
    {
        return $query->whereIn('status', ['completed', 'failed', 'cancelled', 'refunded']);
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
     * Check if payment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if payment is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if payment is in a final state (completed, failed, cancelled, refunded).
     */
    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled', 'refunded']);
    }

    /**
     * Check if payment can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status === 'completed' && !$this->isRefunded();
    }

    /**
     * Check if payment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Update payment status with proper validation and logging.
     */
    public function updateStatus(string $newStatus, array $additionalData = []): bool
    {
        $validTransitions = [
            'pending' => ['processing', 'completed', 'failed', 'cancelled', 'expired'],
            'processing' => ['completed', 'failed', 'cancelled'],
            'completed' => ['refunded'],
            'failed' => [],
            'cancelled' => [],
            'refunded' => [],
            'expired' => []
        ];

        if (!isset($validTransitions[$this->status])) {
            \Log::error('Invalid current payment status', [
                'payment_id' => $this->id,
                'current_status' => $this->status,
                'new_status' => $newStatus
            ]);
            return false;
        }

        if (!in_array($newStatus, $validTransitions[$this->status])) {
            \Log::warning('Invalid payment status transition', [
                'payment_id' => $this->id,
                'current_status' => $this->status,
                'new_status' => $newStatus,
                'valid_transitions' => $validTransitions[$this->status]
            ]);
            return false;
        }

        $updateData = array_merge([
            'status' => $newStatus,
            'processed_at' => now()
        ], $additionalData);

        $this->update($updateData);

        \Log::info('Payment status updated', [
            'payment_id' => $this->id,
            'old_status' => $this->status,
            'new_status' => $newStatus,
            'additional_data' => $additionalData
        ]);

        return true;
    }

    /**
     * Get payment status history.
     */
    public function getStatusHistory(): array
    {
        $history = [];
        
        if ($this->created_at) {
            $history[] = [
                'status' => 'created',
                'timestamp' => $this->created_at,
                'description' => 'پرداخت ایجاد شد'
            ];
        }

        if ($this->processed_at) {
            $history[] = [
                'status' => $this->status,
                'timestamp' => $this->processed_at,
                'description' => $this->status_in_persian
            ];
        }

        if ($this->refunded_at) {
            $history[] = [
                'status' => 'refunded',
                'timestamp' => $this->refunded_at,
                'description' => 'پرداخت برگشت خورد'
            ];
        }

        return $history;
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
            'processing' => 'در حال پردازش',
            'refunded' => 'برگشت خورده',
            'cancelled' => 'لغو شده',
            'expired' => 'منقضی شده',
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