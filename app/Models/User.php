<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Events\NewUserRegistrationEvent;
use App\UserAnalytics;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\TeacherAccount;
use App\Traits\HasImageUrl;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, UserAnalytics, HasImageUrl;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'password',
        'first_name',
        'last_name',
        'profile_image_url',
        'role',
        'status',
        'requires_2fa',
        'phone_verified_at',
        'parent_id',
        'timezone',
        'preferences',
        'last_login_at',
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
            'phone_verified_at' => 'datetime',
            'requires_2fa' => 'boolean',
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
        return $this->hasOne(Subscription::class)->active();
    }

    /**
     * Get the user's teacher account.
     */
    public function teacherAccount()
    {
        return $this->hasOne(TeacherAccount::class);
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

    /**
     * Get Jalali formatted created_at date
     */
    public function getJalaliCreatedAtAttribute()
    {
        return \App\Helpers\JalaliHelper::formatForDisplay($this->created_at, 'Y/m/d');
    }

    /**
     * Get Jalali formatted created_at date with Persian month
     */
    public function getJalaliCreatedAtWithMonthAttribute()
    {
        return \App\Helpers\JalaliHelper::formatWithPersianMonth($this->created_at);
    }

    /**
     * Get Jalali formatted created_at date with Persian month and time
     */
    public function getJalaliCreatedAtWithMonthAndTimeAttribute()
    {
        return \App\Helpers\JalaliHelper::formatWithPersianMonthAndTime($this->created_at);
    }

    /**
     * Get Jalali formatted last_login_at date
     */
    public function getJalaliLastLoginAtAttribute()
    {
        return $this->last_login_at ? \App\Helpers\JalaliHelper::formatForDisplay($this->last_login_at, 'Y/m/d H:i') : null;
    }

    /**
     * Get Jalali formatted last_login_at date with Persian month
     */
    public function getJalaliLastLoginAtWithMonthAttribute()
    {
        return $this->last_login_at ? \App\Helpers\JalaliHelper::formatWithPersianMonth($this->last_login_at) : null;
    }

    /**
     * Get Jalali formatted last_login_at date with Persian month and time
     */
    public function getJalaliLastLoginAtWithMonthAndTimeAttribute()
    {
        return $this->last_login_at ? \App\Helpers\JalaliHelper::formatWithPersianMonthAndTime($this->last_login_at) : null;
    }

    /**
     * Get Jalali relative time for created_at
     */
    public function getJalaliCreatedAtRelativeAttribute()
    {
        return \App\Helpers\JalaliHelper::getRelativeTime($this->created_at);
    }

    /**
     * Get Jalali relative time for last_login_at
     */
    public function getJalaliLastLoginAtRelativeAttribute()
    {
        return $this->last_login_at ? \App\Helpers\JalaliHelper::getRelativeTime($this->last_login_at) : null;
    }

    /**
     * Role and Permission Management
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')->using(UserRole::class);
    }

    public function permissions()
    {
        return Permission::whereHas('roles', function ($query) {
            $query->whereHas('users', function ($userQuery) {
                $userQuery->where('user_id', $this->id);
            });
        });
    }

    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Check role-based permissions
        $hasRolePermission = $this->permissions()->where('name', $permission)->exists();
        
        // Check direct permissions
        $hasDirectPermission = $this->directPermissions()->where('name', $permission)->exists();
        
        return $hasRolePermission || $hasDirectPermission;
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    public function assignRole(Role $role): void
    {
        if (!$this->hasRole($role->name)) {
            $this->roles()->attach($role);
        }
    }

    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role);
    }

    public function syncRoles(array $roles): void
    {
        $this->roles()->sync($roles);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin']) || in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * Update 2FA requirement based on current roles
     */
    public function update2FARequirement(): void
    {
        $isAdmin = $this->isAdmin();
        
        if ($isAdmin && !$this->requires_2fa) {
            $this->update(['requires_2fa' => true]);
        } elseif (!$isAdmin && $this->requires_2fa) {
            $this->update(['requires_2fa' => false]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->status)) {
                $user->status = 'active';
            }
            if (empty($user->role)) {
                $user->role = 'basic';
            }
            if (empty($user->timezone)) {
                $user->timezone = 'Asia/Tehran';
            }
            
            // Enable 2FA for admin users
            if (in_array($user->role, ['admin', 'super_admin'])) {
                $user->requires_2fa = true;
            }
        });

        static::created(function ($user) {
            // Fire new user registration event
            event(new NewUserRegistrationEvent($user));
        });

        // Update 2FA requirement when role changes
        static::updating(function ($user) {
            if ($user->isDirty('role')) {
                if (in_array($user->role, ['admin', 'super_admin'])) {
                    $user->requires_2fa = true;
                } else {
                    $user->requires_2fa = false;
                }
            }
        });
    }

    /**
     * Get the profile image URL for the user
     */
    public function getProfileImageUrlAttribute()
    {
        return $this->getImageUrlFromPath($this->attributes['profile_image_url'] ?? null);
    }
}
