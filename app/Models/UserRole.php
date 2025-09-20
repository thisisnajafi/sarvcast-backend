<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    protected $table = 'user_role';

    protected static function boot()
    {
        parent::boot();

        // Update 2FA requirement when admin roles are attached
        static::created(function ($userRole) {
            $role = Role::find($userRole->role_id);
            $user = User::find($userRole->user_id);
            
            if ($role && $user && in_array($role->name, ['admin', 'super_admin'])) {
                $user->update(['requires_2fa' => true]);
            }
        });

        // Update 2FA requirement when admin roles are detached
        static::deleted(function ($userRole) {
            $user = User::find($userRole->user_id);
            
            if ($user) {
                // Check if user still has any admin roles
                $hasAdminRole = $user->hasAnyRole(['admin', 'super_admin']) || 
                               in_array($user->role, ['admin', 'super_admin']);
                
                if (!$hasAdminRole) {
                    $user->update(['requires_2fa' => false]);
                }
            }
        });
    }
}