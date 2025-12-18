<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MyketPlan extends Model
{
    use HasFactory;

    protected $table = 'myket_plans';

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relationship with subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(MyketSubscription::class, 'plan_id');
    }
}
