<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class VerifySuperAdmin extends Command
{
    protected $signature = 'admin:verify-super-admin';
    protected $description = 'Verify super admin setup';

    public function handle()
    {
        $this->info('=== Super Admin Verification ===');
        
        // Check if super admin user exists
        $user = User::where('phone_number', '09136708883')->first();
        
        if (!$user) {
            $this->error('Super admin user not found!');
            return;
        }
        
        $this->info("User: {$user->first_name} {$user->last_name} ({$user->phone_number})");
        
        // Check roles
        $roles = $user->roles->pluck('name')->toArray();
        $this->info('Roles: ' . implode(', ', $roles));
        
        // Check if super admin
        $isSuperAdmin = $user->isSuperAdmin();
        $this->info('Is Super Admin: ' . ($isSuperAdmin ? 'Yes' : 'No'));
        
        // Check permissions count
        $permissionCount = $user->permissions()->count();
        $this->info("Total Permissions: {$permissionCount}");
        
        // Check role and permission counts
        $roleCount = Role::count();
        $permissionCount = Permission::count();
        $this->info("Total Roles in System: {$roleCount}");
        $this->info("Total Permissions in System: {$permissionCount}");
        
        $this->info('=== Verification Complete ===');
    }
}