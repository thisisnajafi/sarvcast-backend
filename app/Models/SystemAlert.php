<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAlert extends Model
{
    use HasFactory;

    protected $table = 'system_alerts';

    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'metadata',
        'acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'resolved',
        'resolved_at',
        'resolved_by',
        'triggered_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'triggered_at' => 'datetime',
    ];
}
