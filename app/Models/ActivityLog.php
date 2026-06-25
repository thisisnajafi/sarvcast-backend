<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const CHANNEL_ADMIN = 'admin';

    public const CHANNEL_APP = 'app';

    public const CHANNEL_SYSTEM = 'system';

    public const CHANNEL_SECURITY = 'security';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'channel',
        'actor_user_id',
        'actor_type',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'properties',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
        'event_uuid',
        'device_id',
        'app_version',
        'platform',
        'status',
        'occurred_at',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function toApiArray(): array
    {
        $actor = $this->relationLoaded('actor') ? $this->actor : null;

        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'action' => $this->action,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_label' => $this->subject_label,
            'description' => $this->description,
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) ?: null,
                'phone' => $actor->phone_number ?? null,
                'email' => $actor->email ?? null,
            ] : null,
            'properties' => $this->properties,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'request_id' => $this->request_id,
            'device_id' => $this->device_id,
            'app_version' => $this->app_version,
            'platform' => $this->platform,
            'status' => $this->status,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
