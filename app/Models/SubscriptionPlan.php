<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'duration_days',
        'price',
        'currency',
        'discount_percentage',
        'is_active',
        'is_featured',
        'sort_order',
        'features'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array'
    ];

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for ordering by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Get the final price after discount
     */
    public function getFinalPriceAttribute()
    {
        if ($this->discount_percentage > 0) {
            return $this->price * (1 - $this->discount_percentage / 100);
        }
        return $this->price;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price) . ' ' . $this->currency;
    }

    /**
     * Get formatted final price
     */
    public function getFormattedFinalPriceAttribute()
    {
        return number_format($this->final_price) . ' ' . $this->currency;
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationTextAttribute()
    {
        if ($this->duration_days >= 365) {
            $years = floor($this->duration_days / 365);
            return $years . ' سال';
        } elseif ($this->duration_days >= 30) {
            $months = floor($this->duration_days / 30);
            return $months . ' ماه';
        } else {
            return $this->duration_days . ' روز';
        }
    }

    /**
     * Relationship with subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'type', 'slug');
    }
}
