<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolMembership extends Model
{
    protected $fillable = [
        'school_manager_profile_id',
        'user_id',
        'active_user_id',
        'attached_by',
        'detached_at',
        'detached_by',
    ];

    protected function casts(): array
    {
        return [
            'detached_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SchoolManagerProfile::class, 'school_manager_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attached_by');
    }

    public function detacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detached_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('detached_at')->whereNotNull('active_user_id');
    }
}
