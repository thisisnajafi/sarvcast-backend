<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'status',
        'coins_awarded',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user who made the referral
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the user who was referred
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Get the referral code used
     */
    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code', 'code');
    }

    /**
     * Mark referral as completed
     */
    public function markAsCompleted(int $coinsAwarded = 10): void
    {
        $this->update([
            'status' => 'completed',
            'coins_awarded' => $coinsAwarded,
            'completed_at' => now(),
        ]);

        // Update referral code statistics
        if ($this->referralCode) {
            $this->referralCode->incrementSuccessfulReferrals($coinsAwarded);
        }

        // Award coins to referrer
        $referrerCoins = UserCoin::firstOrCreate(['user_id' => $this->referrer_id]);
        $referrerCoins->addCoins(
            $coinsAwarded,
            'referral',
            $this->id,
            "Referral bonus for referring {$this->referred->name}"
        );
    }

    /**
     * Check if referral requirements are met
     */
    public function checkCompletionRequirements(): bool
    {
        $referredUser = $this->referred;
        
        // Check if user has listened to at least 1 complete story
        $hasListened = PlayHistory::where('user_id', $referredUser->id)
            ->where('completed', true)
            ->exists();

        // Check if user has been active for 7+ days
        $isActive = $referredUser->created_at->diffInDays(now()) >= 7;

        return $hasListened && $isActive;
    }

    /**
     * Scope to get pending referrals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get completed referrals
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get referrals by referrer
     */
    public function scopeByReferrer($query, int $referrerId)
    {
        return $query->where('referrer_id', $referrerId);
    }

    /**
     * Scope to get referrals by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('referral_code', $code);
    }

    /**
     * Get referral statistics for user
     */
    public static function getUserStatistics(int $userId): array
    {
        $referrals = self::byReferrer($userId);
        
        return [
            'total_referrals' => $referrals->count(),
            'completed_referrals' => $referrals->completed()->count(),
            'pending_referrals' => $referrals->pending()->count(),
            'total_coins_earned' => $referrals->sum('coins_awarded'),
            'conversion_rate' => $referrals->count() > 0 ? 
                round(($referrals->completed()->count() / $referrals->count()) * 100, 2) : 0,
        ];
    }

    /**
     * Get global referral statistics
     */
    public static function getGlobalStatistics(): array
    {
        $totalReferrals = self::count();
        $completedReferrals = self::completed()->count();
        $totalCoinsAwarded = self::sum('coins_awarded');

        return [
            'total_referrals' => $totalReferrals,
            'completed_referrals' => $completedReferrals,
            'pending_referrals' => $totalReferrals - $completedReferrals,
            'total_coins_awarded' => $totalCoinsAwarded,
            'conversion_rate' => $totalReferrals > 0 ? 
                round(($completedReferrals / $totalReferrals) * 100, 2) : 0,
        ];
    }
}
