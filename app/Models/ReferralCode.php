<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'successful_referrals',
        'total_coins_earned',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the referral code
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referrals made with this code
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referral_code', 'code');
    }

    /**
     * Generate a unique referral code
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create a referral code for user
     */
    public static function createForUser(int $userId): self
    {
        return self::create([
            'user_id' => $userId,
            'code' => self::generateUniqueCode(),
            'is_active' => true,
        ]);
    }

    /**
     * Check if referral code is valid
     */
    public function isValid(): bool
    {
        return $this->is_active && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Increment successful referrals
     */
    public function incrementSuccessfulReferrals(int $coinsEarned = 0): void
    {
        $this->increment('successful_referrals');
        $this->increment('total_coins_earned', $coinsEarned);
        $this->save();
    }

    /**
     * Scope to get active codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope to get top referrers
     */
    public function scopeTopReferrers($query, int $limit = 10)
    {
        return $query->orderBy('successful_referrals', 'desc')
                    ->limit($limit);
    }

    /**
     * Get referral code statistics
     */
    public function getStatistics(): array
    {
        $referrals = $this->referrals();
        
        return [
            'total_referrals' => $referrals->count(),
            'successful_referrals' => $this->successful_referrals,
            'pending_referrals' => $referrals->where('status', 'pending')->count(),
            'completed_referrals' => $referrals->where('status', 'completed')->count(),
            'total_coins_earned' => $this->total_coins_earned,
            'conversion_rate' => $referrals->count() > 0 ? 
                round(($this->successful_referrals / $referrals->count()) * 100, 2) : 0,
        ];
    }
}
