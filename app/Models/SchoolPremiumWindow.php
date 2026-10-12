<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolPremiumWindow extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'school_manager_profile_id',
        'duration_months',
        'seat_limit',
        'expires_at',
        'status',
        'active_profile_id',
        'granted_by',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'seat_limit' => 'integer',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SchoolManagerProfile::class, 'school_manager_profile_id');
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(SchoolPremiumGrant::class);
    }
}
