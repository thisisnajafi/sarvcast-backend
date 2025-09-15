<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'داستان‌های کلاسیک',
                'slug' => 'classic-stories',
                'description' => 'داستان‌های کلاسیک و قدیمی که نسل‌هاست کودکان را سرگرم می‌کنند',
                'color' => '#FF6B6B',
                'status' => 'active',
                'order' => 1,
                'meta_title' => 'داستان‌های کلاسیک برای کودکان',
                'meta_description' => 'مجموعه‌ای از بهترین داستان‌های کلاسیک برای کودکان',
            ],
            [
                'name' => 'داستان‌های ماجراجویی',
                'slug' => 'adventure-stories',
                'description' => 'داستان‌های هیجان‌انگیز و ماجراجویی که کودکان را به دنیای خیال می‌برند',
                'color' => '#4ECDC4',
                'status' => 'active',
                'order' => 2,
                'meta_title' => 'داستان‌های ماجراجویی کودکان',
                'meta_description' => 'داستان‌های هیجان‌انگیز و ماجراجویی برای کودکان',
            ],
            [
                'name' => 'داستان‌های آموزشی',
                'slug' => 'educational-stories',
                'description' => 'داستان‌هایی که در کنار سرگرمی، آموزش‌های مفیدی به کودکان می‌دهند',
                'color' => '#45B7D1',
                'status' => 'active',
                'order' => 3,
                'meta_title' => 'داستان‌های آموزشی کودکان',
                'meta_description' => 'داستان‌های آموزشی و آموزنده برای کودکان',
            ],
            [
                'name' => 'داستان‌های فانتزی',
                'slug' => 'fantasy-stories',
                'description' => 'داستان‌های فانتزی و تخیلی با شخصیت‌های جادویی و دنیاهای خیالی',
                'color' => '#96CEB4',
                'status' => 'active',
                'order' => 4,
                'meta_title' => 'داستان‌های فانتزی کودکان',
                'meta_description' => 'داستان‌های فانتزی و تخیلی برای کودکان',
            ],
            [
                'name' => 'داستان‌های حیوانات',
                'slug' => 'animal-stories',
                'description' => 'داستان‌هایی با شخصیت‌های حیوانی که کودکان را با طبیعت آشنا می‌کنند',
                'color' => '#FFEAA7',
                'status' => 'active',
                'order' => 5,
                'meta_title' => 'داستان‌های حیوانات کودکان',
                'meta_description' => 'داستان‌های جذاب با شخصیت‌های حیوانی',
            ],
            [
                'name' => 'داستان‌های تاریخی',
                'slug' => 'historical-stories',
                'description' => 'داستان‌هایی که کودکان را با تاریخ و فرهنگ آشنا می‌کنند',
                'color' => '#DDA0DD',
                'status' => 'active',
                'order' => 6,
                'meta_title' => 'داستان‌های تاریخی کودکان',
                'meta_description' => 'داستان‌های تاریخی و فرهنگی برای کودکان',
            ],
            [
                'name' => 'داستان‌های علمی',
                'slug' => 'science-stories',
                'description' => 'داستان‌هایی که کودکان را با علوم و تکنولوژی آشنا می‌کنند',
                'color' => '#98D8C8',
                'status' => 'active',
                'order' => 7,
                'meta_title' => 'داستان‌های علمی کودکان',
                'meta_description' => 'داستان‌های علمی و تکنولوژیکی برای کودکان',
            ],
            [
                'name' => 'داستان‌های اخلاقی',
                'slug' => 'moral-stories',
                'description' => 'داستان‌هایی که ارزش‌های اخلاقی و انسانی را به کودکان می‌آموزند',
                'color' => '#F7DC6F',
                'status' => 'active',
                'order' => 8,
                'meta_title' => 'داستان‌های اخلاقی کودکان',
                'meta_description' => 'داستان‌های اخلاقی و آموزنده برای کودکان',
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
    }
}