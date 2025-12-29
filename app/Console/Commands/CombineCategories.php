<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CombineCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:combine
                            {--dry-run : Show what would be combined without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and combine similar categories, updating stories accordingly';

    /**
     * Category combination mapping
     */
    protected $categoryMappings = [
        // Educational categories -> "آموزشی"
        'آموزشی (جغرافیا)' => 'آموزشی',
        'آموزشی (حیوانات)' => 'آموزشی',
        'آموزشی (طبیعت)' => 'آموزشی',
        'آموزشی (علوم)' => 'آموزشی',
        'آموزشی و انگیزشی' => 'آموزشی',
        'آموزشی و خلاقانه' => 'آموزشی',
        'آموزشی و عاطفی' => 'آموزشی',
        'خلاقانه و آموزشی' => 'آموزشی',
        'یادگیری و کشف' => 'آموزشی',
        'اعتماد به نفس در یادگیری' => 'آموزشی',
        'رشد شخصی و یادگیری' => 'آموزشی',
        'رشد شخصی و یادگیری مرزها' => 'آموزشی',
        'کشف علم' => 'آموزشی',
        'کشف تاریخ' => 'آموزشی',
        'کشف موسیقی' => 'آموزشی',
        'کشف اقیانوس' => 'آموزشی',

        // Health/Hygiene categories -> "بهداشت"
        'بهداشت خواب' => 'بهداشت',
        'بهداشت دست' => 'بهداشت',
        'بهداشت ورزش' => 'بهداشت',

        // Friendship categories -> "دوستی"
        'دوستی با حیوانات' => 'دوستی',
        'دوستی خانوادگی' => 'دوستی',
        'دوستی مجازی' => 'دوستی',

        // Courage categories -> "شجاعت"
        'شجاعت در سخنرانی' => 'شجاعت',
        'شجاعت در کمک' => 'شجاعت',

        // Creativity categories -> "خلاقیت"
        'خلاقیت در داستان‌سرایی' => 'خلاقیت',
        'خلاقیت در ساخت' => 'خلاقیت',
        'خلاقیت و رشد شخصی' => 'خلاقیت',

        // Adventure categories -> "ماجراجویی"
        'ماجراجویی تاریخی' => 'ماجراجویی',
        'ماجراجویی فضایی' => 'ماجراجویی',
        'ماجراجویی کوهستانی' => 'ماجراجویی',

        // Cooperation categories -> "همکاری"
        'همکاری در سفر' => 'همکاری',
        'همکاری در مزرعه' => 'همکاری',
        'کار گروهی در آشپزی' => 'همکاری',

        // Fantasy categories -> "فانتزی"
        'فانتزی حیوانات' => 'فانتزی',
        'فانتزی رویاها' => 'فانتزی',

        // Problem solving categories -> "حل مسئله"
        'حل مسئله خلاقانه' => 'حل مسئله',
        'حل مسئله فناوری' => 'حل مسئله',
        'حل معما جادویی' => 'حل مسئله',

        // Personal growth categories -> "رشد شخصی"
        'احساسات و رشد شخصی' => 'رشد شخصی',
        'رشد شخصی و استقلال' => 'رشد شخصی',
        'رشد شخصی و پذیرش خود' => 'رشد شخصی',

        // Emotions categories -> "احساسات"
        'احساسات و روابط' => 'احساسات',
        'احساسات و شجاعت' => 'احساسات',
        'مدیریت احساسات' => 'احساسات',
        'مدیریت ترس' => 'احساسات',
        'مدیریت حسادت' => 'احساسات',
        'مدیریت شادی' => 'احساسات',

        // Motivation categories -> "انگیزشی"
        'انگیزشی و الهام‌بخش' => 'انگیزشی',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analyzing categories for combination...');

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        // Get all categories
        $allCategories = Category::all();
        $this->info("📊 Found {$allCategories->count()} categories in database");

        // Analyze which categories will be combined
        $toCombine = [];
        $categoriesToCreate = [];

        foreach ($this->categoryMappings as $sourceName => $targetName) {
            $sourceCategory = Category::where('name', $sourceName)->first();
            if ($sourceCategory) {
                $targetCategory = Category::where('name', $targetName)->first();

                if (!$targetCategory) {
                    // Target category doesn't exist - we'll rename the first source to target
                    // and use it as the target for others
                    if (!isset($categoriesToCreate[$targetName])) {
                        $categoriesToCreate[$targetName] = $sourceCategory;
                    } else {
                        // We already have a target, so this source will be combined into it
                        $toCombine[] = [
                            'source' => $sourceCategory,
                            'target' => $categoriesToCreate[$targetName],
                            'source_name' => $sourceName,
                            'target_name' => $targetName,
                        ];
                    }
                } else {
                    $toCombine[] = [
                        'source' => $sourceCategory,
                        'target' => $targetCategory,
                        'source_name' => $sourceName,
                        'target_name' => $targetName,
                    ];
                }
            }
        }

        if (empty($toCombine) && empty($targetCategories)) {
            $this->info('✅ No categories need to be combined');
            return 0;
        }

        // Show what will be combined
        $this->info("\n📋 Categories to be combined:");
        $this->table(
            ['Source Category', 'Target Category', 'Stories Affected'],
            array_map(function($item) {
                $storyCount = Story::where('category_id', $item['source']->id)->count();
                return [
                    $item['source_name'],
                    $item['target_name'],
                    $storyCount
                ];
            }, $toCombine)
        );

        // Show categories that need to be created/renamed
        if (!empty($categoriesToCreate)) {
            $this->info("\n📝 Categories to be created/renamed:");
            foreach ($categoriesToCreate as $targetName => $sourceCategory) {
                $storyCount = Story::where('category_id', $sourceCategory->id)->count();
                $this->line("   - '{$sourceCategory->name}' → '{$targetName}' ({$storyCount} stories)");
            }
        }

        if ($dryRun) {
            $this->warn("\n⚠️  This was a dry run. Run without --dry-run to apply changes.");
            return 0;
        }

        // Confirm before proceeding
        if (!$this->confirm("\n❓ Do you want to proceed with combining these categories?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info("\n🔄 Starting category combination...");

        DB::beginTransaction();

        try {
            // First, handle categories that need to be renamed/created
            // These will become the target categories for others
            foreach ($categoriesToCreate as $targetName => $sourceCategory) {
                $this->info("   📝 Renaming '{$sourceCategory->name}' to '{$targetName}'...");

                // Update category name
                $sourceCategory->update([
                    'name' => $targetName,
                    'slug' => \Illuminate\Support\Str::slug($targetName),
                ]);

                $this->info("      ✅ Renamed successfully");
            }

            // Refresh categories to create in case they're referenced in toCombine
            foreach ($categoriesToCreate as $targetName => $oldSource) {
                $newTarget = Category::where('name', $targetName)->first();
                if ($newTarget) {
                    // Update any toCombine entries that reference the old source
                    foreach ($toCombine as &$item) {
                        if ($item['target']->id === $oldSource->id) {
                            $item['target'] = $newTarget;
                        }
                    }
                }
            }

            // Now combine categories
            $combined = 0;
            $storiesUpdated = 0;

            foreach ($toCombine as $item) {
                $source = $item['source'];
                $target = $item['target'];

                $this->info("   🔄 Combining '{$item['source_name']}' → '{$item['target_name']}'...");

                // Get stories in source category
                $stories = Story::where('category_id', $source->id)->get();
                $storyCount = $stories->count();

                if ($storyCount > 0) {
                    // Update stories to use target category
                    Story::where('category_id', $source->id)
                        ->update(['category_id' => $target->id]);

                    $storiesUpdated += $storyCount;
                    $this->info("      ✅ Updated {$storyCount} stories");
                }

                // Delete source category
                $source->delete();
                $this->info("      ✅ Deleted source category");

                $combined++;
            }

            DB::commit();

            $this->info("\n✅ Category combination completed!");
            $this->info("   - Combined {$combined} categories");
            $this->info("   - Updated {$storiesUpdated} stories");
            $this->info("   - Remaining categories: " . Category::count());

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error combining categories: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

