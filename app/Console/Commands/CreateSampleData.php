<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Person;

class CreateSampleData extends Command
{
    protected $signature = 'create:sample-data';
    protected $description = 'Create sample data for testing story creation';

    public function handle()
    {
        $this->info('Creating sample data...');
        
        // Create sample categories
        $categories = [
            ['name' => 'داستان‌های کودکانه', 'description' => 'داستان‌های مناسب کودکان'],
            ['name' => 'ماجراجویی', 'description' => 'داستان‌های ماجراجویی'],
            ['name' => 'آموزشی', 'description' => 'داستان‌های آموزشی'],
            ['name' => 'فانتزی', 'description' => 'داستان‌های فانتزی'],
        ];
        
        foreach ($categories as $categoryData) {
            Category::firstOrCreate(['name' => $categoryData['name']], $categoryData);
            $this->info("Created category: {$categoryData['name']}");
        }
        
        // Create sample people
        $people = [
            [
                'name' => 'احمد رضایی',
                'bio' => 'راوی و گوینده با تجربه',
                'roles' => ['narrator', 'voice_actor'],
                'is_verified' => true
            ],
            [
                'name' => 'مریم احمدی',
                'bio' => 'کارگردان و نویسنده',
                'roles' => ['director', 'writer'],
                'is_verified' => true
            ],
            [
                'name' => 'علی محمدی',
                'bio' => 'نویسنده و مؤلف',
                'roles' => ['writer', 'author'],
                'is_verified' => true
            ],
        ];
        
        foreach ($people as $personData) {
            Person::firstOrCreate(['name' => $personData['name']], $personData);
            $this->info("Created person: {$personData['name']}");
        }
        
        $this->info('Sample data created successfully!');
        $this->info('You can now create stories with these categories and people.');
    }
}