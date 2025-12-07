<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MyketUserSubscription extends Model
{
    use HasFactory;

    protected $table = 'myket_user_subscriptions';

    public $incrementing = false;
    
    // Note: Laravel doesn't fully support composite primary keys
    // We'll use a workaround by not setting primaryKey and handling uniqueness in validation

    protected $fillable = [
        'user_id',
        'subscription_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MyketSubscription::class, 'subscription_id');
    }
}
