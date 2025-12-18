<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'favorite_category_id',
        'preferences',
        'settings',
    ];

    protected $casts = [
        'preferences' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the favorite category for the user.
     */
    public function favoriteCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'favorite_category_id');
    }
}
