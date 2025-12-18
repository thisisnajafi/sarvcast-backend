<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCoin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_coins',
        'available_coins',
        'earned_coins',
        'spent_coins',
        'last_earned_at',
        'last_spent_at',
    ];

    protected $casts = [
        'last_earned_at' => 'datetime',
        'last_spent_at' => 'datetime',
    ];

    /**
     * Get the user that owns the coins
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the coin transactions for this user
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Check if user has enough coins
     */
    public function hasEnoughCoins(int $amount): bool
    {
        return $this->available_coins >= $amount;
    }

    /**
     * Add coins to user's balance
     */
    public function addCoins(int $amount, string $sourceType = null, int $sourceId = null, string $description = null): void
    {
        $this->increment('total_coins', $amount);
        $this->increment('available_coins', $amount);
        $this->increment('earned_coins', $amount);
        $this->last_earned_at = now();
        $this->save();

        // Create transaction record
        CoinTransaction::create([
            'user_id' => $this->user_id,
            'transaction_type' => 'earned',
            'amount' => $amount,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
            'transacted_at' => now(),
        ]);
    }

    /**
     * Spend coins from user's balance
     */
    public function spendCoins(int $amount, string $sourceType = null, int $sourceId = null, string $description = null): bool
    {
        if (!$this->hasEnoughCoins($amount)) {
            return false;
        }

        $this->decrement('available_coins', $amount);
        $this->increment('spent_coins', $amount);
        $this->last_spent_at = now();
        $this->save();

        // Create transaction record
        CoinTransaction::create([
            'user_id' => $this->user_id,
            'transaction_type' => 'spent',
            'amount' => $amount,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
            'transacted_at' => now(),
        ]);

        return true;
    }

    /**
     * Get coin balance summary
     */
    public function getBalanceSummary(): array
    {
        return [
            'total_coins' => $this->total_coins,
            'available_coins' => $this->available_coins,
            'earned_coins' => $this->earned_coins,
            'spent_coins' => $this->spent_coins,
            'last_earned_at' => $this->last_earned_at,
            'last_spent_at' => $this->last_spent_at,
        ];
    }

    /**
     * Scope to get users with coins
     */
    public function scopeWithCoins($query)
    {
        return $query->where('available_coins', '>', 0);
    }

    /**
     * Scope to get top coin earners
     */
    public function scopeTopEarners($query, int $limit = 10)
    {
        return $query->orderBy('earned_coins', 'desc')->limit($limit);
    }
}
