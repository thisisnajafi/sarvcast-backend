<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MyketPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'price' => 50000.00,
                'duration_days' => 30,
                'features' => json_encode([
                    'دسترسی به تمام داستان‌ها',
                    'پخش بدون محدودیت',
                    'پشتیبانی ۲۴/۷'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium',
                'price' => 135000.00,
                'duration_days' => 90,
                'features' => json_encode([
                    'دسترسی به تمام داستان‌ها',
                    'پخش بدون محدودیت',
                    'دانلود آفلاین',
                    'پشتیبانی ۲۴/۷',
                    'دسترسی زودهنگام به داستان‌های جدید'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Family',
                'price' => 400000.00,
                'duration_days' => 365,
                'features' => json_encode([
                    'دسترسی به تمام داستان‌ها',
                    'پخش بدون محدودیت',
                    'دانلود آفلاین',
                    'پشتیبانی ۲۴/۷',
                    'دسترسی زودهنگام به داستان‌های جدید',
                    'پشتیبانی چند کاربره',
                    'مشاوره شخصی'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('myket_plans')->insertOrIgnore($plan);
        }
    }
}
