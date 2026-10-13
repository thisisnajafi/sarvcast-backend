<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'subject',
        'status',
        'priority',
        'channel',
        'assigned_to',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    // Statuses
    const STATUS_OPEN     = 'open';
    const STATUS_PENDING  = 'pending';
    const STATUS_CLOSED   = 'closed';
    const STATUS_RESOLVED = 'resolved';

    // Priorities
    const PRIORITY_LOW    = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'support_ticket_id');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'support_ticket_id')->latestOfMany();
    }
}
