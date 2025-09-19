<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class CouponCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'discount_value',
        'minimum_amount',
        'maximum_discount',
        'partner_type',
        'partner_id',
        'created_by',
        'usage_limit',
        'usage_count',
        'user_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'applicable_plans',
        'metadata',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_plans' => 'array',
        'metadata' => 'array',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'partner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function commissionPayments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class, 'coupon_usage_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    });
    }

    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('partner_type', $type);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhereRaw('usage_count < usage_limit');
        });
    }

    // Methods
    public function isValid(): bool
    {
        return $this->is_active &&
               ($this->starts_at === null || $this->starts_at <= now()) &&
               ($this->expires_at === null || $this->expires_at >= now()) &&
               ($this->usage_limit === null || $this->usage_count < $this->usage_limit);
    }

    public function canBeUsedByUser(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->user_limit !== null) {
            $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $this->user_limit) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            $discount = ($amount * $this->discount_value) / 100;
            if ($this->maximum_discount !== null) {
                $discount = min($discount, $this->maximum_discount);
            }
            return round($discount, 2);
        } elseif ($this->type === 'fixed_amount') {
            return min($this->discount_value, $amount);
        } elseif ($this->type === 'free_trial') {
            return $amount; // Full discount for free trial
        }

        return 0;
    }

    public function calculateCommission(float $finalAmount): float
    {
        if (!$this->partner) {
            return 0;
        }

        $commissionRate = $this->getCommissionRate();
        return round(($finalAmount * $commissionRate) / 100, 2);
    }

    private function getCommissionRate(): float
    {
        $rates = [
            'influencer' => 20, // 20% for influencers
            'teacher' => 15,     // 15% for teachers
            'partner' => 10,    // 10% for partners
            'promotional' => 0, // No commission for promotional codes
        ];

        return $rates[$this->partner_type] ?? 0;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getUsagePercentage(): float
    {
        if ($this->usage_limit === null) {
            return 0;
        }

        return round(($this->usage_count / $this->usage_limit) * 100, 2);
    }

    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'discount_value' => $this->discount_value,
            'minimum_amount' => $this->minimum_amount,
            'maximum_discount' => $this->maximum_discount,
            'partner_type' => $this->partner_type,
            'partner_name' => $this->partner?->name,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'user_limit' => $this->user_limit,
            'starts_at' => $this->starts_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => $this->is_active,
            'applicable_plans' => $this->applicable_plans,
            'is_valid' => $this->isValid(),
            'usage_percentage' => $this->getUsagePercentage(),
            'days_until_expiry' => $this->getDaysUntilExpiry(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
