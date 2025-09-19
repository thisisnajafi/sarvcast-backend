<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnableAdmin2FA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:enable-2fa {--phone= : Phone number of admin user} {--all : Enable 2FA for all admin users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable 2FA for admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->enable2FAForAllAdmins();
        } elseif ($phone = $this->option('phone')) {
            $this->enable2FAForUser($phone);
        } else {
            $this->error('Please specify --phone or --all option');
            return 1;
        }

        return 0;
    }

    private function enable2FAForAllAdmins()
    {
        $admins = User::where('role', 'admin')->get();
        
        if ($admins->isEmpty()) {
            $this->warn('No admin users found');
            return;
        }

        $this->info("Found {$admins->count()} admin users");

        foreach ($admins as $admin) {
            $admin->update(['requires_2fa' => true]);
            $this->line("✓ Enabled 2FA for: {$admin->phone_number} ({$admin->first_name} {$admin->last_name})");
        }

        $this->info('2FA enabled for all admin users');
    }

    private function enable2FAForUser(string $phone)
    {
        $user = User::where('phone_number', $phone)->where('role', 'admin')->first();
        
        if (!$user) {
            $this->error("Admin user with phone number {$phone} not found");
            return;
        }

        $user->update(['requires_2fa' => true]);
        $this->info("✓ Enabled 2FA for: {$user->phone_number} ({$user->first_name} {$user->last_name})");
    }
}
