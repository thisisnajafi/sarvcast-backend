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
        'price', // Website price (default)
        'currency',
        'discount_percentage',
        'is_active',
        'is_featured',
        'sort_order',
        'features',
        // Flavor-specific fields
        'myket_price',
        'myket_product_id',
        'cafebazaar_price',
        'cafebazaar_product_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'myket_price' => 'decimal:2',
        'cafebazaar_price' => 'decimal:2',
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
        // Order plans from highest price to lowest so premium plans appear first
        return $query->orderByDesc('price');
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

    /**
     * Get price for a specific flavor
     * 
     * @param string $flavor 'website'|'myket'|'cafebazaar'
     * @return float|null
     */
    public function getPriceForFlavor(string $flavor): ?float
    {
        return match($flavor) {
            'website' => $this->price,
            'myket' => $this->myket_price ?? $this->price, // Fallback to website price
            'cafebazaar' => $this->cafebazaar_price ?? $this->price, // Fallback to website price
            default => $this->price
        };
    }

    /**
     * Get product ID for a specific flavor
     * 
     * @param string $flavor 'website'|'myket'|'cafebazaar'
     * @return string|null
     */
    public function getProductIdForFlavor(string $flavor): ?string
    {
        return match($flavor) {
            'website' => $this->slug, // Use slug as product ID for website
            'myket' => $this->myket_product_id,
            'cafebazaar' => $this->cafebazaar_product_id,
            default => null
        };
    }

    /**
     * Get final price (after discount) for a specific flavor
     * 
     * @param string $flavor 'website'|'myket'|'cafebazaar'
     * @return float|null
     */
    public function getFinalPriceForFlavor(string $flavor): ?float
    {
        $price = $this->getPriceForFlavor($flavor);
        if ($price === null) {
            return null;
        }
        
        if ($this->discount_percentage > 0) {
            return $price * (1 - $this->discount_percentage / 100);
        }
        
        return $price;
    }

    /**
     * Check if plan is available for a specific flavor
     * 
     * @param string $flavor 'website'|'myket'|'cafebazaar'
     * @return bool
     */
    public function isAvailableForFlavor(string $flavor): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $price = $this->getPriceForFlavor($flavor);
        return $price !== null && $price > 0;
    }

    /**
     * Get all flavor data as array
     * 
     * @return array
     */
    public function getFlavorData(): array
    {
        return [
            'website' => [
                'price' => $this->price,
                'product_id' => $this->slug,
                'final_price' => $this->final_price,
                'available' => $this->is_active && $this->price > 0
            ],
            'myket' => [
                'price' => $this->myket_price ?? $this->price,
                'product_id' => $this->myket_product_id,
                'final_price' => $this->getFinalPriceForFlavor('myket'),
                'available' => $this->isAvailableForFlavor('myket')
            ],
            'cafebazaar' => [
                'price' => $this->cafebazaar_price ?? $this->price,
                'product_id' => $this->cafebazaar_product_id,
                'final_price' => $this->getFinalPriceForFlavor('cafebazaar'),
                'available' => $this->isAvailableForFlavor('cafebazaar')
            ]
        ];
    }
}
