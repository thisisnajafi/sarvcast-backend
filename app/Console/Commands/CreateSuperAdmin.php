<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\NewUserRegistrationEvent;
use App\Models\User;
use App\Models\Role;
use App\Support\NewUserNotificationLogger;
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
            NewUserNotificationLogger::warning('registration_early_return', [
                'source' => 'admin:create-super-admin',
                'reason' => 'user_already_exists',
                'existing_user_id' => (int) $user->id,
            ]);
            $this->info("User with phone number {$phoneNumber} already exists.");
            
            // Check if user already has super admin role
            if ($user->isSuperAdmin()) {
                $this->info("User already has super admin role.");
                return;
            }
        } else {
            // Create new user
            NewUserNotificationLogger::debug('user_record_creating', [
                'source' => 'admin:create-super-admin',
                'role' => 'super_admin',
                'event' => NewUserRegistrationEvent::class,
                'queue_connection' => (string) config('queue.default'),
            ]);
            $user = User::create([
                'phone_number' => $phoneNumber,
                'email' => 'abolfazl@manji.com',
                'password' => Hash::make($password),
                'first_name' => 'Abolfazl',
                'last_name' => 'Super Admin',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);
            NewUserNotificationLogger::debug('user_record_created', [
                'source' => 'admin:create-super-admin',
                'new_user_id' => (int) $user->id,
                'role' => $user->role,
                'event' => NewUserRegistrationEvent::class,
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