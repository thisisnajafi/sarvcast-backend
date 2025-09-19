<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'phone_number' => '09138333293',
                'first_name' => 'Armiti',
                'last_name' => 'Admin',
                'email' => 'armiti@sarvcast.com',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('admin123'),
            ],
            [
                'phone_number' => '09025472668',
                'first_name' => 'Sogand',
                'last_name' => 'Admin',
                'email' => 'sogand@sarvcast.com',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('admin123'),
            ],
            [
                'phone_number' => '09136708883',
                'first_name' => 'Abolfazl',
                'last_name' => 'Admin',
                'email' => 'abolfazl@sarvcast.com',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('admin123'),
            ],
            [
                'phone_number' => '09393676109',
                'first_name' => 'Mahmood',
                'last_name' => 'Admin',
                'email' => 'mahmood@sarvcast.com',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('admin123'),
            ],
            [
                'phone_number' => '09131397003',
                'first_name' => 'Masood',
                'last_name' => 'Admin',
                'email' => 'masood@sarvcast.com',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('admin123'),
            ],
        ];

        foreach ($admins as $adminData) {
            // Check if admin already exists
            $existingAdmin = User::where('phone_number', $adminData['phone_number'])
                ->orWhere('email', $adminData['email'])
                ->first();

            if (!$existingAdmin) {
                User::create($adminData);
                $this->command->info("Admin user created: {$adminData['first_name']} ({$adminData['phone_number']})");
            } else {
                $this->command->warn("Admin user already exists: {$adminData['first_name']} ({$adminData['phone_number']})");
            }
        }

        $this->command->info('Admin seeder completed successfully!');
    }
}
