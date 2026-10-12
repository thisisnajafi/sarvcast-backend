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
        'label',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'member_limit' => 'integer',
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
     * @return list<int>
     */
    public function activeMemberIds(): array
    {
        return $this->activeMemberships()->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
    }
}
