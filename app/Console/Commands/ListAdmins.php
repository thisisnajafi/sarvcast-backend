<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListAdmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admins = User::where('role', 'admin')->get(['phone_number', 'first_name', 'last_name', 'email', 'status', 'created_at']);

        if ($admins->isEmpty()) {
            $this->info('No admin users found.');
            return;
        }

        $this->info('Admin Users:');
        $this->newLine();

        $headers = ['Phone Number', 'Name', 'Email', 'Status', 'Created At'];
        $rows = [];

        foreach ($admins as $admin) {
            $rows[] = [
                $admin->phone_number,
                $admin->first_name . ' ' . $admin->last_name,
                $admin->email,
                $admin->status,
                $admin->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->info('Total admin users: ' . $admins->count());
    }
}
