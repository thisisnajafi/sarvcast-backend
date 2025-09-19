<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount',
        'source_type',
        'source_id',
        'description',
        'metadata',
        'transacted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'transacted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source model based on source_type and source_id
     */
    public function source()
    {
        if (!$this->source_type || !$this->source_id) {
            return null;
        }

        $modelClass = match($this->source_type) {
            'episode' => Episode::class,
            'referral' => Referral::class,
            'quiz' => EpisodeQuestion::class,
            'achievement' => Achievement::class,
            'subscription' => Subscription::class,
            default => null,
        };

        return $modelClass ? $modelClass::find($this->source_id) : null;
    }

    /**
     * Scope to get earned transactions
     */
    public function scopeEarned($query)
    {
        return $query->where('transaction_type', 'earned');
    }

    /**
     * Scope to get spent transactions
     */
    public function scopeSpent($query)
    {
        return $query->where('transaction_type', 'spent');
    }

    /**
     * Scope to get transactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope to get transactions by source
     */
    public function scopeBySource($query, string $sourceType, int $sourceId = null)
    {
        $query = $query->where('source_type', $sourceType);
        
        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }
        
        return $query;
    }

    /**
     * Scope to get recent transactions
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('transacted_at', '>=', now()->subDays($days));
    }

    /**
     * Get transaction summary for user
     */
    public static function getSummaryForUser(int $userId, int $days = 30): array
    {
        $transactions = self::where('user_id', $userId)
            ->where('transacted_at', '>=', now()->subDays($days))
            ->get();

        $earned = $transactions->where('transaction_type', 'earned')->sum('amount');
        $spent = $transactions->where('transaction_type', 'spent')->sum('amount');

        return [
            'total_earned' => $earned,
            'total_spent' => $spent,
            'net_gain' => $earned - $spent,
            'transaction_count' => $transactions->count(),
            'by_type' => $transactions->groupBy('transaction_type')->map->sum('amount'),
        ];
    }
}
