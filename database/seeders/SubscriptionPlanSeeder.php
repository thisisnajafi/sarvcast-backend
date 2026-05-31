<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'اشتراک یک ماهه',
                'slug' => '1month',
                'cafebazaar_product_id' => '1-month-sub',
                'description' => 'دسترسی کامل به تمام محتوا برای یک ماه',
                'duration_days' => 30,
                'price' => 50000,
                'currency' => 'IRT',
                'discount_percentage' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    'دسترسی به تمام داستان‌ها',
                    'پخش بدون محدودیت',
                    'دانلود آفلاین',
                    'پشتیبانی ۲۴/۷'
                ]
            ],
            [
                'name' => 'اشتراک سه‌ماهه',
                'slug' => '3months',
                'cafebazaar_product_id' => '3-month-sub',
                'description' => 'دسترسی کامل به تمام محتوا برای سه ماه',
                'duration_days' => 90,
                'price' => 135000,
                'currency' => 'IRT',
                'discount_percentage' => 10,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 2,
                'features' => [
                    'دسترسی به تمام داستان‌ها',
                    'پخش بدون محدودیت',
                    'دانلود آفلاین',
                    'پشتیبانی ۲۴/۷',
                    '۱۰٪ تخفیف'
                ]
            ],
            [
                'name' => 'اشتراک شش‌ماهه',
                'slug' => '6months',
                'cafebazaar_product_id' => '6-month-sub',
                'description' => 'دسترسی کامل به تمام محتوا برای شش ماه',
                'duration_days' => 180,
                'price' => 240000,
                'currency' => 'IRT',
                'discount_percentage' => 20,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'features' => [
                    'دسترسی به تمام داستان‌ها',
                    'پخش بدون محدودیت',
                    'دانلود آفلاین',
                    'پشتیبانی ۲۴/۷',
                    '۲۰٪ تخفیف',
                    'دسترسی زودهنگام به داستان‌های جدید'
                ]
            ],
            [
                'name' => 'اشتراک یک ساله',
                'slug' => '1year',
                'cafebazaar_product_id' => '1-year-sub',
                'description' => 'دسترسی کامل به تمام محتوا برای یک سال',
                'duration_days' => 365,
                'price' => 400000,
                'currency' => 'IRT',
                'discount_percentage' => 33,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
                'features' => [
                    'دسترسی به تمام داستان‌ها',
                    'پخش بدون محدودیت',
                    'دانلود آفلاین',
                    'پشتیبانی ۲۴/۷',
                    '۳۳٪ تخفیف',
                    'دسترسی زودهنگام به داستان‌های جدید',
                    'مشاوره شخصی'
                ]
            ]
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::firstOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
