<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;

class CheckCategoryStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:check-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check category statistics and display discrepancies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking category statistics...');
        
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->info('No categories found.');
            return Command::SUCCESS;
        }
        
        $this->table(
            ['Category', 'Stored Count', 'Real-time Count', 'Published Count', 'Discrepancy'],
            $categories->map(function ($category) {
                $realTimeCount = $category->stories()->count();
                $publishedCount = $category->stories()->where('status', 'published')->count();
                $storedCount = $category->story_count ?? 0;
                $discrepancy = $storedCount !== $realTimeCount ? 'YES' : 'NO';
                
                return [
                    $category->name,
                    $storedCount,
                    $realTimeCount,
                    $publishedCount,
                    $discrepancy
                ];
            })
        );
        
        return Command::SUCCESS;
    }
}