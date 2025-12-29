<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;

class ListCategories extends Command
{
    protected $signature = 'categories:list';
    protected $description = 'List all categories with story counts';

    public function handle()
    {
        $categories = Category::withCount('stories')->orderBy('name')->get();
        
        $this->info("Total categories: {$categories->count()}\n");
        
        $tableData = [];
        foreach ($categories as $category) {
            $tableData[] = [
                'ID' => $category->id,
                'Name' => $category->name,
                'Stories' => $category->stories_count,
            ];
        }
        
        $this->table(['ID', 'Name', 'Stories'], $tableData);
        
        return 0;
    }
}

