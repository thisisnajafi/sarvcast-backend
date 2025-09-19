<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

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
                'description' => 'داستان‌های کلاسیک و قدیمی که نسل‌هاست خوانده می‌شوند',
                'icon_path' => '/icons/classic-stories.svg',
                'color' => '#8B5CF6',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'داستان‌های ماجراجویی',
                'description' => 'داستان‌های هیجان‌انگیز و ماجراجویانه برای کودکان',
                'icon_path' => '/icons/adventure-stories.svg',
                'color' => '#F59E0B',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'داستان‌های آموزشی',
                'description' => 'داستان‌هایی که مفاهیم آموزشی را به کودکان می‌آموزند',
                'icon_path' => '/icons/educational-stories.svg',
                'color' => '#10B981',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'داستان‌های فانتزی',
                'description' => 'داستان‌های تخیلی و فانتزی با موجودات خیالی',
                'icon_path' => '/icons/fantasy-stories.svg',
                'color' => '#EC4899',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'داستان‌های حیوانات',
                'description' => 'داستان‌هایی که شخصیت‌های اصلی آن‌ها حیوانات هستند',
                'icon_path' => '/icons/animal-stories.svg',
                'color' => '#F97316',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'داستان‌های تاریخی',
                'description' => 'داستان‌هایی که بر اساس وقایع تاریخی نوشته شده‌اند',
                'icon_path' => '/icons/historical-stories.svg',
                'color' => '#6B7280',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'داستان‌های علمی',
                'description' => 'داستان‌هایی که مفاهیم علمی را به کودکان معرفی می‌کنند',
                'icon_path' => '/icons/scientific-stories.svg',
                'color' => '#3B82F6',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'داستان‌های اخلاقی',
                'description' => 'داستان‌هایی که ارزش‌های اخلاقی و انسانی را آموزش می‌دهند',
                'icon_path' => '/icons/moral-stories.svg',
                'color' => '#059669',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'داستان‌های طنز',
                'description' => 'داستان‌های خنده‌دار و طنزآمیز برای سرگرمی کودکان',
                'icon_path' => '/icons/comedy-stories.svg',
                'color' => '#DC2626',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'داستان‌های شب',
                'description' => 'داستان‌های آرام و آرامش‌بخش برای زمان خواب',
                'icon_path' => '/icons/bedtime-stories.svg',
                'color' => '#7C3AED',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'داستان‌های ایرانی',
                'description' => 'داستان‌های اصیل ایرانی و فرهنگ بومی',
                'icon_path' => '/icons/persian-stories.svg',
                'color' => '#B91C1C',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'داستان‌های بین‌المللی',
                'description' => 'داستان‌های ترجمه شده از فرهنگ‌های مختلف جهان',
                'icon_path' => '/icons/international-stories.svg',
                'color' => '#1D4ED8',
                'story_count' => 0,
                'total_episodes' => 0,
                'total_duration' => 0,
                'average_rating' => 0,
                'is_active' => true,
                'sort_order' => 12,
            ],
        ];

        foreach ($categories as $categoryData) {
            $existingCategory = Category::where('name', $categoryData['name'])->first();

            if (!$existingCategory) {
                Category::create($categoryData);
                $this->command->info("Category created: {$categoryData['name']}");
            } else {
                $this->command->warn("Category already exists: {$categoryData['name']}");
            }
        }

        $this->command->info('Category seeder completed successfully!');
    }
}