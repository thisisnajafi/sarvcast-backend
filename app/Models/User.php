<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\UserAnalytics;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, UserAnalytics;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'phone_number',
        'first_name',
        'last_name',
        'profile_image_url',
        'role',
        'status',
        'email_verified_at',
        'phone_verified_at',
        'parent_id',
        'timezone',
        'preferences',
        'last_login_at',
        'password',
        'registration_source',
        'referral_code',
        'referred_by',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'total_sessions',
        'total_play_time',
        'total_favorites',
        'total_ratings',
        'total_spent',
        'last_activity_at',
        'first_purchase_at',
        'last_purchase_at',
        'analytics_data',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'first_purchase_at' => 'datetime',
            'last_purchase_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'analytics_data' => 'array',
            'total_spent' => 'decimal:2',
        ];
    }

    /**
     * Get the child profiles for this user.
     */
    public function profiles()
    {
        return $this->hasMany(UserProfile::class);
    }

    /**
     * Get the parent user for child users.
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get the child users for parent users.
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Get the user's subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's payments.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user's play histories.
     */
    public function playHistories()
    {
        return $this->hasMany(PlayHistory::class);
    }

    /**
     * Get the user's favorite stories.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the user's ratings.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the user's notifications.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's current active subscription.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is parent.
     */
    public function isParent()
    {
        return $this->role === 'parent';
    }

    /**
     * Check if user is child.
     */
    public function isChild()
    {
        return $this->role === 'child';
    }

    /**
     * Check if user has active subscription.
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Check if user's phone is verified.
     */
    public function isPhoneVerified()
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Mark phone as verified.
     */
    public function markPhoneAsVerified()
    {
        $this->update(['phone_verified_at' => now()]);
    }

    /**
     * Find user by phone number.
     */
    public static function findByPhoneNumber(string $phoneNumber)
    {
        return static::where('phone_number', $phoneNumber)->first();
    }

    /**
     * Check if phone number exists.
     */
    public static function phoneNumberExists(string $phoneNumber)
    {
        return static::where('phone_number', $phoneNumber)->exists();
    }

    /**
     * Get user's full name.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get user's display name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if user can login.
     */
    public function canLogin()
    {
        return $this->status === 'active' && $this->isPhoneVerified();
    }

    /**
     * Get user's preferred timezone.
     */
    public function getTimezoneAttribute()
    {
        return $this->attributes['timezone'] ?? 'Asia/Tehran';
    }

    /**
     * Scope for active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('phone_verified_at');
    }

    /**
     * Scope for admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for parent users.
     */
    public function scopeParents($query)
    {
        return $query->where('role', 'parent');
    }

    /**
     * Scope for child users.
     */
    public function scopeChildren($query)
    {
        return $query->where('role', 'child');
    }
}
