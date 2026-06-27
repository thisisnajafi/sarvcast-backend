<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsTemplate extends Model
{
    use HasFactory;

    public const CATEGORY_MARKETING = 'marketing';

    public const CATEGORY_TRANSACTIONAL = 'transactional';

    public const CATEGORY_SYSTEM = 'system';

    protected $fillable = [
        'name',
        'slug',
        'melipayamak_body_id',
        'preview_text',
        'parameters',
        'category',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'melipayamak_body_id' => 'integer',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getParameterCountAttribute(): int
    {
        return count($this->parameters ?? []);
    }
}
