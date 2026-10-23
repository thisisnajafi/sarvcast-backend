<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SchoolManagerProfile extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'user_id',
        'member_limit',
        'premium_grant_expires_at',
        'allowed_grant_plan_ids',
        'label',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'member_limit' => 'integer',
            'premium_grant_expires_at' => 'datetime',
            'allowed_grant_plan_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->active();
    }

    public function windows(): HasMany
    {
        return $this->hasMany(SchoolPremiumWindow::class);
    }

    public function latestWindow(): HasOne
    {
        return $this->hasOne(SchoolPremiumWindow::class)->latestOfMany();
    }

    public function grants(): HasMany
    {
        return $this->hasMany(SchoolPremiumGrant::class);
    }

    /**
     * Plan ids the manager may grant for free; empty means "use the premium window duration".
     *
     * @return list<int>
     */
    public function allowedGrantPlanIds(): array
    {
        return collect($this->allowed_grant_plan_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function premiumGrantPeriodEnded(): bool
    {
        return $this->premium_grant_expires_at !== null && $this->premium_grant_expires_at->lessThanOrEqualTo(now());
    }

    /**
     * @return list<int>
     */
    public function activeMemberIds(): array
    {
        return $this->activeMemberships()->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
    }
}
