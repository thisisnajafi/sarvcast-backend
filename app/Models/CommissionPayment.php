<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_partner_id',
        'coupon_usage_id',
        'amount',
        'currency',
        'payment_type',
        'status',
        'payment_method',
        'payment_reference',
        'payment_details',
        'notes',
        'processed_at',
        'paid_at',
        'processed_by',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'payment_details' => 'array',
        'metadata' => 'array',
    ];

    public function affiliatePartner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class);
    }

    public function couponUsage(): BelongsTo
    {
        return $this->belongsTo(CouponUsage::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('affiliate_partner_id', $partnerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    public function markAsPaid(string $paymentReference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'paid' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            'pending' => 'در انتظار',
            'processing' => 'در حال پردازش',
            'paid' => 'پرداخت شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
            default => 'نامشخص',
        };
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'partner_name' => $this->affiliatePartner->name,
            'partner_email' => $this->affiliatePartner->email,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted_amount' => $this->getFormattedAmount(),
            'payment_type' => $this->payment_type,
            'status' => $this->status,
            'status_text' => $this->getStatusText(),
            'status_color' => $this->getStatusColor(),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'notes' => $this->notes,
            'processed_at' => $this->processed_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'processor_name' => $this->processor?->name,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
