<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPremiumGrant extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'school_premium_window_id',
        'school_manager_profile_id',
        'school_membership_id',
        'user_id',
        'subscription_id',
        'duration_months',
        'premium_ends_at',
        'previous_end_date',
        'status',
        'notification_id',
        'granted_by',
        'revoked_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'premium_ends_at' => 'datetime',
            'previous_end_date' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function window(): BelongsTo
    {
        return $this->belongsTo(SchoolPremiumWindow::class, 'school_premium_window_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SchoolManagerProfile::class, 'school_manager_profile_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(SchoolMembership::class, 'school_membership_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
