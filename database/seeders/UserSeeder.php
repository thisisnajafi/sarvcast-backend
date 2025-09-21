<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'phone_number' => '09123456789',
            'first_name' => 'مدیر',
            'last_name' => 'سیستم',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('admin123'),
            'phone_verified_at' => now(),
            'preferences' => [
                'language' => 'fa',
                'notifications' => [
                    'push' => true,
                    'email' => true,
                    'sms' => false,
                ],
            ],
        ]);

        // Create sample parent users
        $parents = [
            [
                'phone_number' => '09123456790',
                'first_name' => 'علی',
                'last_name' => 'احمدی',
                'role' => 'parent',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'phone_verified_at' => now(),
                'preferences' => [
                    'language' => 'fa',
                    'notifications' => [
                        'push' => true,
                        'email' => true,
                        'sms' => false,
                    ],
                    'parental_controls' => [
                        'enabled' => true,
                        'age_limit' => 8,
                        'content_filter' => 'moderate',
                    ],
                ],
            ],
            [
                'phone_number' => '09123456791',
                'first_name' => 'فاطمه',
                'last_name' => 'محمدی',
                'role' => 'parent',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'phone_verified_at' => now(),
                'preferences' => [
                    'language' => 'fa',
                    'notifications' => [
                        'push' => true,
                        'email' => false,
                        'sms' => true,
                    ],
                    'parental_controls' => [
                        'enabled' => false,
                        'age_limit' => null,
                        'content_filter' => 'lenient',
                    ],
                ],
            ],
            [
                'phone_number' => '09123456792',
                'first_name' => 'حسن',
                'last_name' => 'کریمی',
                'role' => 'parent',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'phone_verified_at' => now(),
                'preferences' => [
                    'language' => 'fa',
                    'notifications' => [
                        'push' => false,
                        'email' => true,
                        'sms' => false,
                    ],
                    'parental_controls' => [
                        'enabled' => true,
                        'age_limit' => 12,
                        'content_filter' => 'strict',
                    ],
                ],
            ],
        ];

        foreach ($parents as $parentData) {
            User::create($parentData);
        }

        // Create sample child users
        $children = [
            [
                'phone_number' => null,
                'first_name' => 'سارا',
                'last_name' => 'احمدی',
                'role' => 'child',
                'status' => 'active',
                'password' => null,
                'phone_verified_at' => null,
                'parent_id' => User::where('phone_number', '09123456790')->first()->id,
                'preferences' => [
                    'language' => 'fa',
                    'favorite_categories' => [1, 2, 3], // Category IDs
                    'audio' => [
                        'quality' => 'high',
                        'auto_play' => true,
                    ],
                ],
            ],
            [
                'phone_number' => null,
                'first_name' => 'محمد',
                'last_name' => 'احمدی',
                'role' => 'child',
                'status' => 'active',
                'password' => null,
                'phone_verified_at' => null,
                'parent_id' => User::where('phone_number', '09123456790')->first()->id,
                'preferences' => [
                    'language' => 'fa',
                    'favorite_categories' => [4, 5, 6], // Category IDs
                    'audio' => [
                        'quality' => 'medium',
                        'auto_play' => false,
                    ],
                ],
            ],
            [
                'phone_number' => null,
                'first_name' => 'زهرا',
                'last_name' => 'محمدی',
                'role' => 'child',
                'status' => 'active',
                'password' => null,
                'phone_verified_at' => null,
                'parent_id' => User::where('phone_number', '09123456791')->first()->id,
                'preferences' => [
                    'language' => 'fa',
                    'favorite_categories' => [1, 7, 8], // Category IDs
                    'audio' => [
                        'quality' => 'high',
                        'auto_play' => true,
                    ],
                ],
            ],
        ];

        foreach ($children as $childData) {
            User::create($childData);
        }
    }
}