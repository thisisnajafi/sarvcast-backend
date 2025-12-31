<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileView extends Model
{
    use HasFactory;

    protected $fillable = [
        'viewed_user_id',
        'viewer_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user whose profile was viewed
     */
    public function viewedUser()
    {
        return $this->belongsTo(User::class, 'viewed_user_id');
    }

    /**
     * Get the user who viewed the profile
     */
    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}

