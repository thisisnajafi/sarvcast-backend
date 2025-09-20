<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckAdmin2FA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:check-2fa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check 2FA status for admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking 2FA status for admin users...');
        
        // Check users with admin role field
        $adminUsers = User::whereIn('role', ['admin', 'super_admin'])->get();
        
        if ($adminUsers->count() > 0) {
            $this->info('Users with admin role field:');
            foreach ($adminUsers as $user) {
                $status = $user->requires_2fa ? 'Enabled' : 'Disabled';
                $this->line("ID: {$user->id} - {$user->first_name} {$user->last_name} ({$user->role}) - 2FA: {$status}");
            }
        } else {
            $this->warn('No users found with admin role field');
        }
        
        // Check users with admin roles
        $roleAdminUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->get();
        
        if ($roleAdminUsers->count() > 0) {
            $this->info('Users with admin roles:');
            foreach ($roleAdminUsers as $user) {
                $status = $user->requires_2fa ? 'Enabled' : 'Disabled';
                $roles = $user->roles->pluck('name')->join(', ');
                $this->line("ID: {$user->id} - {$user->first_name} {$user->last_name} (Roles: {$roles}) - 2FA: {$status}");
            }
        } else {
            $this->warn('No users found with admin roles');
        }
        
        $this->info('2FA check completed!');
    }
}