<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'price',
        'currency',
        'auto_renew',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price' => 'decimal:2',
        'auto_renew' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payments for this subscription
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope to get active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '>', now());
    }

    /**
     * Scope to get expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<=', now());
    }

    /**
     * Scope to get subscriptions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get subscriptions by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date > now();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date <= now();
    }

    /**
     * Check if subscription is trial
     */
    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute(): int
    {
        return max(0, Carbon::parse($this->end_date)->diffInDays(now(), false));
    }

    /**
     * Get plan name
     */
    public function getPlanNameAttribute(): string
    {
        $plans = [
            '1month' => 'اشتراک یک ماهه',
            '3months' => 'اشتراک سه ماهه',
            '6months' => 'اشتراک شش ماهه',
            '1year' => 'اشتراک یک ساله'
        ];

        return $plans[$this->type] ?? $this->type;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price) . ' ' . $this->currency;
    }

    /**
     * Get subscription duration in days
     */
    public function getDurationDaysAttribute(): int
    {
        return Carbon::parse($this->start_date)->diffInDays($this->end_date);
    }

    /**
     * Check if subscription can be renewed
     */
    public function canRenew(): bool
    {
        return $this->status === 'active' && $this->auto_renew;
    }

    /**
     * Check if subscription can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    /**
     * Get subscription status text
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'active' => 'فعال',
            'expired' => 'منقضی شده',
            'cancelled' => 'لغو شده',
            'pending' => 'در انتظار',
            'trial' => 'آزمایشی'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get subscription type text
     */
    public function getTypeTextAttribute(): string
    {
        $types = [
            '1month' => 'یک ماهه',
            '3months' => 'سه ماهه',
            '6months' => 'شش ماهه',
            '1year' => 'یک ساله'
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Get next renewal date
     */
    public function getNextRenewalDateAttribute(): ?Carbon
    {
        if (!$this->auto_renew || $this->status !== 'active') {
            return null;
        }

        return $this->end_date;
    }

    /**
     * Check if subscription is expiring soon (within 7 days)
     */
    public function isExpiringSoon(): bool
    {
        return $this->days_remaining <= 7 && $this->days_remaining > 0;
    }

    /**
     * Get subscription summary
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'plan_name' => $this->plan_name,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'currency' => $this->currency,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'days_remaining' => $this->days_remaining,
            'duration_days' => $this->duration_days,
            'auto_renew' => $this->auto_renew,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_trial' => $this->isTrial(),
            'can_renew' => $this->canRenew(),
            'can_cancel' => $this->canCancel(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'next_renewal_date' => $this->next_renewal_date
        ];
    }
}
