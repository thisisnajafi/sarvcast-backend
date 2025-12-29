<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class DeleteEmptyCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:delete-empty 
                            {--dry-run : Show what would be deleted without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete categories that have no stories assigned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Finding categories with no stories...');
        
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }
        
        // Find categories with no stories
        $emptyCategories = Category::doesntHave('stories')->get();
        
        if ($emptyCategories->isEmpty()) {
            $this->info('✅ No empty categories found. All categories have stories.');
            return 0;
        }
        
        $this->info("📊 Found {$emptyCategories->count()} empty categories:");
        
        $tableData = [];
        foreach ($emptyCategories as $category) {
            $tableData[] = [
                'ID' => $category->id,
                'Name' => $category->name,
                'Created' => $category->created_at->format('Y-m-d'),
            ];
        }
        
        $this->table(['ID', 'Name', 'Created'], $tableData);
        
        if ($dryRun) {
            $this->warn("\n⚠️  Would delete {$emptyCategories->count()} categories (dry run)");
            return 0;
        }
        
        // Confirm before deleting
        if (!$this->confirm("\n❓ Do you want to delete these {$emptyCategories->count()} empty categories?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $this->info("\n🗑️  Deleting empty categories...");
        
        DB::beginTransaction();
        
        try {
            $deleted = 0;
            
            foreach ($emptyCategories as $category) {
                $category->delete();
                $this->info("   ✅ Deleted: {$category->name}");
                $deleted++;
            }
            
            DB::commit();
            
            $this->info("\n✅ Empty categories deletion completed!");
            $this->info("   - Deleted {$deleted} categories");
            $this->info("   - Remaining categories: " . Category::count());
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error deleting categories: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

