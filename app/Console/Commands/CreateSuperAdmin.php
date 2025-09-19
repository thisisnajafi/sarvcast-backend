<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super-admin {phone_number} {password}';
    protected $description = 'Create a super admin user';

    public function handle()
    {
        $phoneNumber = $this->argument('phone_number');
        $password = $this->argument('password');

        // Check if user already exists
        $user = User::where('phone_number', $phoneNumber)->first();
        
        if ($user) {
            $this->info("User with phone number {$phoneNumber} already exists.");
            
            // Check if user already has super admin role
            if ($user->isSuperAdmin()) {
                $this->info("User already has super admin role.");
                return;
            }
        } else {
            // Create new user
            $user = User::create([
                'phone_number' => $phoneNumber,
                'email' => 'abolfazl@sarvcast.com',
                'password' => Hash::make($password),
                'first_name' => 'Abolfazl',
                'last_name' => 'Super Admin',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);
            
            $this->info("User created successfully with phone number: {$phoneNumber}");
        }

        // Assign super admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        
        if (!$superAdminRole) {
            $this->error('Super admin role not found. Please run the RolePermissionSeeder first.');
            return;
        }

        $user->assignRole($superAdminRole);
        $this->info("Super admin role assigned to user: {$phoneNumber}");
    }
}