<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class AddCategoryDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:add-descriptions 
                            {--dry-run : Show what would be added without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add descriptions to categories that don\'t have them';

    /**
     * Category descriptions mapping
     */
    protected $descriptions = [
        'آموزشی' => 'داستان‌های آموزشی که به کودکان مفاهیم مختلف علمی، جغرافیایی، تاریخی و طبیعی را می‌آموزند',
        'احساسات' => 'داستان‌هایی که به کودکان کمک می‌کنند تا احساسات خود را بشناسند و مدیریت کنند',
        'انگیزشی' => 'داستان‌های الهام‌بخش که انگیزه و اعتماد به نفس را در کودکان تقویت می‌کنند',
        'بهداشت' => 'داستان‌هایی که به کودکان اهمیت بهداشت و سلامتی را آموزش می‌دهند',
        'حفاظت از جنگل' => 'داستان‌هایی درباره اهمیت حفاظت از محیط زیست و جنگل‌ها',
        'حل مسئله' => 'داستان‌هایی که مهارت حل مسئله و تفکر خلاقانه را در کودکان تقویت می‌کنند',
        'حیوانات' => 'داستان‌های جذاب درباره حیوانات و زندگی آنها',
        'خلاقیت' => 'داستان‌هایی که خلاقیت و نوآوری را در کودکان پرورش می‌دهند',
        'دوستی' => 'داستان‌هایی درباره دوستی، روابط اجتماعی و همکاری با دیگران',
        'رشد شخصی' => 'داستان‌هایی که به رشد شخصی، استقلال و پذیرش خود کمک می‌کنند',
        'شجاعت' => 'داستان‌هایی که شجاعت و جسارت را در کودکان تقویت می‌کنند',
        'فانتزی' => 'داستان‌های تخیلی و فانتزی که دنیای خیالی و رویایی را برای کودکان می‌سازند',
        'ماجراجویی' => 'داستان‌های هیجان‌انگیز و ماجراجویانه که کودکان را به سفر و کشف دعوت می‌کنند',
        'محیط زیست و بازیافت' => 'داستان‌هایی درباره اهمیت حفاظت از محیط زیست و بازیافت',
        'همکاری' => 'داستان‌هایی که اهمیت کار گروهی و همکاری را به کودکان می‌آموزند',
        'هنر نقاشی' => 'داستان‌هایی درباره هنر، نقاشی و خلاقیت هنری',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Finding categories without descriptions...');
        
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }
        
        // Find categories without descriptions
        $categoriesWithoutDescription = Category::whereNull('description')
            ->orWhere('description', '')
            ->get();
        
        if ($categoriesWithoutDescription->isEmpty()) {
            $this->info('✅ All categories already have descriptions.');
            return 0;
        }
        
        $this->info("📊 Found {$categoriesWithoutDescription->count()} categories without descriptions:");
        
        $tableData = [];
        $toUpdate = [];
        
        foreach ($categoriesWithoutDescription as $category) {
            $description = $this->descriptions[$category->name] ?? null;
            
            if ($description) {
                $tableData[] = [
                    'ID' => $category->id,
                    'Name' => $category->name,
                    'Description' => mb_substr($description, 0, 50) . '...',
                ];
                $toUpdate[] = [
                    'category' => $category,
                    'description' => $description,
                ];
            } else {
                $this->warn("   ⚠️  No description defined for: {$category->name}");
            }
        }
        
        if (empty($tableData)) {
            $this->warn('⚠️  No descriptions available for categories without descriptions.');
            return 0;
        }
        
        $this->table(['ID', 'Name', 'Description'], $tableData);
        
        if ($dryRun) {
            $this->warn("\n⚠️  Would add descriptions to " . count($toUpdate) . " categories (dry run)");
            return 0;
        }
        
        // Confirm before updating
        if (!$this->confirm("\n❓ Do you want to add descriptions to these " . count($toUpdate) . " categories?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $this->info("\n📝 Adding descriptions to categories...");
        
        DB::beginTransaction();
        
        try {
            $updated = 0;
            
            foreach ($toUpdate as $item) {
                $item['category']->update([
                    'description' => $item['description'],
                ]);
                
                $this->info("   ✅ Added description to: {$item['category']->name}");
                $updated++;
            }
            
            DB::commit();
            
            $this->info("\n✅ Category descriptions added successfully!");
            $this->info("   - Updated {$updated} categories");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error adding descriptions: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

