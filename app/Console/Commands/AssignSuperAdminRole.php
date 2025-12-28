<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AssignSuperAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-super-admin {phone_number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign super_admin role to a user by phone number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phoneNumber = $this->argument('phone_number');

        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            $this->error("User with phone number {$phoneNumber} not found.");
            return 1;
        }

        $user->role = User::ROLE_SUPER_ADMIN;
        $user->save();

        $this->info("Successfully assigned super_admin role to user: {$user->first_name} {$user->last_name} ({$phoneNumber})");

        return 0;
    }
}

