<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    public function givePermission(Permission $permission): void
    {
        if (!$this->hasPermission($permission->name)) {
            $this->permissions()->attach($permission);
        }
    }

    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission);
    }

    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->sync($permissions);
    }

    protected static function boot()
    {
        parent::boot();

        // Update 2FA requirements when roles are attached
        static::created(function ($role) {
            if (in_array($role->name, ['admin', 'super_admin'])) {
                // Enable 2FA for users who get admin roles
                $role->users()->update(['requires_2fa' => true]);
            }
        });

        // Update 2FA requirements when roles are attached/detached
        static::saved(function ($role) {
            if (in_array($role->name, ['admin', 'super_admin'])) {
                $role->users()->update(['requires_2fa' => true]);
            }
        });
    }
}