<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_code_id',
        'user_id',
        'subscription_id',
        'original_amount',
        'discount_amount',
        'final_amount',
        'commission_amount',
        'status',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function couponCode(): BelongsTo
    {
        return $this->belongsTo(CouponCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function commissionPayment(): BelongsTo
    {
        return $this->hasOne(CommissionPayment::class, 'coupon_usage_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForPartner($query, $partnerId)
    {
        return $query->whereHas('couponCode', function ($q) use ($partnerId) {
            $q->where('partner_id', $partnerId);
        });
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('used_at', '>=', now()->subDays($days));
    }

    // Methods
    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markAsRefunded(): void
    {
        $this->update(['status' => 'refunded']);
    }

    public function getSavingsPercentage(): float
    {
        if ($this->original_amount == 0) {
            return 0;
        }

        return round(($this->discount_amount / $this->original_amount) * 100, 2);
    }

    public function getCommissionPercentage(): float
    {
        if ($this->final_amount == 0) {
            return 0;
        }

        return round(($this->commission_amount / $this->final_amount) * 100, 2);
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'coupon_code' => $this->couponCode->code,
            'coupon_name' => $this->couponCode->name,
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'original_amount' => $this->original_amount,
            'discount_amount' => $this->discount_amount,
            'final_amount' => $this->final_amount,
            'commission_amount' => $this->commission_amount,
            'status' => $this->status,
            'used_at' => $this->used_at->toISOString(),
            'savings_percentage' => $this->getSavingsPercentage(),
            'commission_percentage' => $this->getCommissionPercentage(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
