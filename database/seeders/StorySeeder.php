<?php

namespace Database\Seeders;

use App\Models\Story;
use App\Models\Category;
use App\Models\Person;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $directors = Person::where('role', 'director')->get();
        $authors = Person::where('role', 'author')->get();
        $writers = Person::where('role', 'writer')->get();

        $stories = [
            [
                'title' => 'سفیدبرفی و هفت کوتوله',
                'subtitle' => 'داستان کلاسیک محبوب کودکان',
                'description' => 'داستان زیبای سفیدبرفی و هفت کوتوله که یکی از محبوب‌ترین داستان‌های کلاسیک کودکان است.',
                'category_id' => $categories->where('slug', 'classic-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '3-6',
                'duration' => 25,
                'status' => 'published',
                'is_premium' => false,
                'is_completely_free' => true,
                'play_count' => rand(100, 1000),
                'rating' => 4.5,
                'rating_count' => rand(50, 200),
            ],
            [
                'title' => 'ماجراهای آلیس در سرزمین عجایب',
                'subtitle' => 'سفر به دنیای خیال',
                'description' => 'داستان شگفت‌انگیز آلیس که به سرزمین عجایب سفر می‌کند و ماجراهای هیجان‌انگیزی را تجربه می‌کند.',
                'category_id' => $categories->where('slug', 'fantasy-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '6-9',
                'duration' => 35,
                'status' => 'published',
                'is_premium' => true,
                'is_completely_free' => false,
                'play_count' => rand(200, 1500),
                'rating' => 4.8,
                'rating_count' => rand(100, 300),
            ],
            [
                'title' => 'سفر به فضا',
                'subtitle' => 'ماجراجویی علمی',
                'description' => 'داستان هیجان‌انگیز سفر به فضا و کشف سیارات جدید که کودکان را با علوم آشنا می‌کند.',
                'category_id' => $categories->where('slug', 'science-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '7-10',
                'duration' => 30,
                'status' => 'published',
                'is_premium' => false,
                'is_completely_free' => true,
                'play_count' => rand(150, 800),
                'rating' => 4.3,
                'rating_count' => rand(75, 250),
            ],
            [
                'title' => 'دوستی حیوانات جنگل',
                'subtitle' => 'داستان دوستی و همکاری',
                'description' => 'داستان زیبای دوستی بین حیوانات جنگل که ارزش‌های دوستی و همکاری را به کودکان می‌آموزد.',
                'category_id' => $categories->where('slug', 'animal-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '3-7',
                'duration' => 20,
                'status' => 'published',
                'is_premium' => false,
                'is_completely_free' => true,
                'play_count' => rand(300, 1200),
                'rating' => 4.6,
                'rating_count' => rand(100, 400),
            ],
            [
                'title' => 'شاهزاده و قورباغه',
                'subtitle' => 'داستان کلاسیک عاشقانه',
                'description' => 'داستان زیبای شاهزاده و قورباغه که یکی از محبوب‌ترین داستان‌های کلاسیک است.',
                'category_id' => $categories->where('slug', 'classic-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '4-8',
                'duration' => 22,
                'status' => 'published',
                'is_premium' => true,
                'is_completely_free' => false,
                'play_count' => rand(180, 900),
                'rating' => 4.4,
                'rating_count' => rand(80, 280),
            ],
            [
                'title' => 'ماجراهای رابین هود',
                'subtitle' => 'قهرمان جنگل',
                'description' => 'داستان هیجان‌انگیز رابین هود و ماجراهایش در جنگل که کودکان را با تاریخ آشنا می‌کند.',
                'category_id' => $categories->where('slug', 'historical-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '8-12',
                'duration' => 40,
                'status' => 'published',
                'is_premium' => true,
                'is_completely_free' => false,
                'play_count' => rand(120, 600),
                'rating' => 4.7,
                'rating_count' => rand(60, 200),
            ],
            [
                'title' => 'کودک و دریا',
                'subtitle' => 'داستان شجاعت و امید',
                'description' => 'داستان زیبای کودکی که با شجاعت و امید بر مشکلات غلبه می‌کند.',
                'category_id' => $categories->where('slug', 'moral-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '5-9',
                'duration' => 28,
                'status' => 'published',
                'is_premium' => false,
                'is_completely_free' => true,
                'play_count' => rand(200, 1000),
                'rating' => 4.5,
                'rating_count' => rand(90, 350),
            ],
            [
                'title' => 'جادوگر شهر اوز',
                'subtitle' => 'سفر به سرزمین جادو',
                'description' => 'داستان شگفت‌انگیز دوروتی و سفرش به شهر اوز که یکی از محبوب‌ترین داستان‌های فانتزی است.',
                'category_id' => $categories->where('slug', 'fantasy-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '6-10',
                'duration' => 45,
                'status' => 'published',
                'is_premium' => true,
                'is_completely_free' => false,
                'play_count' => rand(250, 1100),
                'rating' => 4.9,
                'rating_count' => rand(120, 400),
            ],
            [
                'title' => 'یادگیری اعداد',
                'subtitle' => 'آموزش ریاضی به کودکان',
                'description' => 'داستان آموزشی که کودکان را با اعداد و ریاضی آشنا می‌کند.',
                'category_id' => $categories->where('slug', 'educational-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '3-6',
                'duration' => 15,
                'status' => 'published',
                'is_premium' => false,
                'is_completely_free' => true,
                'play_count' => rand(400, 1500),
                'rating' => 4.2,
                'rating_count' => rand(150, 500),
            ],
            [
                'title' => 'ماجراهای تام سایر',
                'subtitle' => 'داستان ماجراجویی',
                'description' => 'داستان هیجان‌انگیز تام سایر و ماجراهایش که کودکان را سرگرم می‌کند.',
                'category_id' => $categories->where('slug', 'adventure-stories')->first()->id,
                'director_id' => $directors->random()->id,
                'author_id' => $authors->random()->id,
                'writer_id' => $writers->random()->id,
                'age_group' => '7-11',
                'duration' => 38,
                'status' => 'published',
                'is_premium' => true,
                'is_completely_free' => false,
                'play_count' => rand(160, 700),
                'rating' => 4.6,
                'rating_count' => rand(70, 250),
            ],
        ];

        foreach ($stories as $storyData) {
            Story::create($storyData);
        }
    }
}