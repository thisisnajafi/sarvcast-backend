<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Category;
use App\Models\Character;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ProcessStoriesFromMarkdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:process-markdown
                            {--stories-path= : Path to manji-stories directory}
                            {--dry-run : Run without making database changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all markdown story files: sync categories, create/update stories, upload scripts, and create characters';

    /**
     * Base path to stories directory
     */
    protected $storiesPath;

    /**
     * All categories found in markdown files
     */
    protected $categoriesFromFiles = [];

    /**
     * All stories found in markdown files
     */
    protected $storiesFromFiles = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting story processing from markdown files...');

        // Determine stories path
        $this->storiesPath = $this->option('stories-path');
        if (!$this->storiesPath) {
            // Try to find it relative to Laravel root
            $possiblePaths = [
                base_path('../manji-stories'),
                base_path('../../manji-stories'),
                base_path('manji-stories'),
            ];

            foreach ($possiblePaths as $path) {
                if (is_dir($path)) {
                    $this->storiesPath = $path;
                    break;
                }
            }
        }

        if (!$this->storiesPath || !is_dir($this->storiesPath)) {
            $this->error('❌ Stories directory not found. Please provide --stories-path option.');
            return 1;
        }

        $this->info("📁 Using stories path: {$this->storiesPath}");

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No database changes will be made');
        }

        // Step 1: Scan all markdown files
        $this->info("\n📖 Step 1: Scanning markdown files...");
        $this->scanMarkdownFiles();

        // Step 2: Process categories
        $this->info("\n📂 Step 2: Processing categories...");
        $this->processCategories($dryRun);

        // Step 3: Process stories
        $this->info("\n📚 Step 3: Processing stories...");
        $this->processStories($dryRun);

        $this->info("\n✅ Story processing completed!");
        $this->info("\n📋 Next steps:");
        $this->info("   1. Review created stories in the admin panel");
        $this->info("   2. Assign voice actors to characters if needed");
        $this->info("   3. Update story images and other metadata");
        $this->info("   4. Publish stories when ready");
        return 0;
    }

    /**
     * Scan all markdown files and extract information
     */
    protected function scanMarkdownFiles()
    {
        $this->storiesFromFiles = [];
        $this->categoriesFromFiles = [];

        $basePath = $this->storiesPath;
        $directories = glob($basePath . '/*', GLOB_ONLYDIR);

        $excludePatterns = [
            'README', 'CHANGELOG', 'GUIDE', 'TEMPLATE', 'PROMPT',
            'ANIMATED', 'COMPLETE', 'QUICK_START', 'PROJECT_SUMMARY',
            '__pycache__', '.git', 'iransans', 'manji-team', 'Voices'
        ];

        foreach ($directories as $dir) {
            $dirName = basename($dir);

            // Skip excluded directories
            if (in_array($dirName, $excludePatterns) ||
                preg_match('/^(' . implode('|', $excludePatterns) . ')/i', $dirName)) {
                continue;
            }

            // Find markdown files in episode directories
            $episodeDirs = glob($dir . '/episode*', GLOB_ONLYDIR);

            foreach ($episodeDirs as $episodeDir) {
                $mdFiles = glob($episodeDir . '/*.md');

                foreach ($mdFiles as $mdFile) {
                    $fileName = basename($mdFile);
                    $fileStem = strtoupper(pathinfo($fileName, PATHINFO_FILENAME));

                    // Skip documentation files
                    if (preg_match('/(' . implode('|', $excludePatterns) . ')/i', $fileStem)) {
                        continue;
                    }

                    $storyInfo = $this->parseMarkdownFile($mdFile, $dirName);
                    if ($storyInfo) {
                        $this->storiesFromFiles[] = $storyInfo;

                        // Collect category
                        if (!empty($storyInfo['category'])) {
                            $this->categoriesFromFiles[$storyInfo['category']] = true;
                        }
                    }
                }
            }
        }

        $this->info("   Found " . count($this->storiesFromFiles) . " story files");
        $this->info("   Found " . count($this->categoriesFromFiles) . " unique categories");
    }

    /**
     * Parse a markdown file and extract story information
     */
    protected function parseMarkdownFile($filePath, $dirName)
    {
        $content = file_get_contents($filePath);
        if (!$content) {
            return null;
        }

        $info = [
            'file_path' => $filePath,
            'dir_name' => $dirName,
            'title' => null,
            'category' => null,
            'age_group' => null,
            'characters' => [],
            'description' => null,
        ];

        // Extract title (first # heading)
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            $title = trim($matches[1]);
            // Remove English part in parentheses if exists
            if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/', $title, $titleMatch)) {
                $info['title'] = trim($titleMatch[1]);
            } else {
                $info['title'] = $title;
            }
        }

        // Extract category (دسته‌بندی) - try multiple patterns
        if (preg_match('/\*\*دسته‌بندی\*\*:\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['category'] = trim($matches[1]);
        } elseif (preg_match('/دسته‌بندی[:\-]\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['category'] = trim($matches[1]);
        }

        // Extract age group (رده سنی)
        if (preg_match('/\*\*رده سنی\*\*:\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['age_group'] = trim($matches[1]);
        } elseif (preg_match('/رده سنی[:\-]\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['age_group'] = trim($matches[1]);
        }

        // Extract description (پیام اصلی)
        if (preg_match('/\*\*پیام اصلی\*\*:\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['description'] = trim($matches[1]);
        } elseif (preg_match('/پیام اصلی[:\-]\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['description'] = trim($matches[1]);
        }

        // Extract characters (شخصیت‌ها section)
        if (preg_match('/##\s*شخصیت‌ها\s*\n(.*?)(?=\n##|\n---|$)/s', $content, $matches)) {
            $charactersSection = $matches[1];
            $characterLines = explode("\n", $charactersSection);

            foreach ($characterLines as $line) {
                $line = trim($line);
                if (empty($line) || !preg_match('/^-\s*\*\*(.+?)\*\*:\s*(.+)$/', $line, $charMatches)) {
                    continue;
                }

                $characterName = trim($charMatches[1]);
                $characterDesc = trim($charMatches[2]);

                if (!empty($characterName)) {
                    $info['characters'][] = [
                        'name' => $characterName,
                        'description' => $characterDesc,
                    ];
                }
            }
        }

        // If no characters found in section, try to extract from dialogue
        if (empty($info['characters'])) {
            // Look for character names in bold (**Character Name**)
            if (preg_match_all('/\*\*([^*]+)\*\*\s*(?:\([^)]+\))?\s*:/', $content, $charMatches)) {
                $foundChars = array_unique($charMatches[1]);
                foreach ($foundChars as $charName) {
                    $charName = trim($charName);
                    // Skip common words
                    if (!in_array($charName, ['راوی', 'بچه‌های دیگر', 'همه'])) {
                        $info['characters'][] = [
                            'name' => $charName,
                            'description' => null,
                        ];
                    }
                }
            }
        }

        return $info;
    }

    /**
     * Process categories - ensure all categories from files exist in database
     */
    protected function processCategories($dryRun)
    {
        $categoriesInDb = Category::pluck('name')->toArray();
        $categoriesToCreate = [];

        foreach (array_keys($this->categoriesFromFiles) as $categoryName) {
            if (!in_array($categoryName, $categoriesInDb)) {
                $categoriesToCreate[] = $categoryName;
            }
        }

        if (empty($categoriesToCreate)) {
            $this->info("   ✅ All categories already exist in database");
            return;
        }

        $this->info("   📝 Found " . count($categoriesToCreate) . " categories to create:");
        foreach ($categoriesToCreate as $cat) {
            $this->line("      - {$cat}");
        }

        if ($dryRun) {
            $this->warn("   ⚠️  Would create " . count($categoriesToCreate) . " categories (dry run)");
            return;
        }

        foreach ($categoriesToCreate as $categoryName) {
            try {
                Category::create([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
                $this->info("   ✅ Created category: {$categoryName}");
            } catch (\Exception $e) {
                $this->error("   ❌ Failed to create category {$categoryName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Process all stories
     */
    protected function processStories($dryRun)
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($this->storiesFromFiles as $storyInfo) {
            $processed++;
            $this->info("\n   📖 Processing story {$processed}/" . count($this->storiesFromFiles) . ": {$storyInfo['title']}");

            try {
                // Find story in database by title (exact match first)
                $story = Story::where('title', $storyInfo['title'])->first();

                // If not found, try partial match or slug
                if (!$story) {
                    $story = Story::where('title', 'like', '%' . $storyInfo['title'] . '%')->first();
                }

                // Try matching by directory name (story number)
                if (!$story && preg_match('/^(\d+)\s*-/', $storyInfo['dir_name'], $matches)) {
                    $storyNumber = $matches[1];
                    // Could match by story number if stored somewhere, but for now skip
                }

                if (!$story) {
                    // Story doesn't exist - create it
                    $this->line("      ➕ Story not in database - creating...");
                    $story = $this->createStory($storyInfo, $dryRun);
                    if ($story) {
                        $created++;
                    } else {
                        $errors++;
                        continue;
                    }
                } else {
                    $this->line("      ✓ Story exists in database (ID: {$story->id}, Title: {$story->title})");
                    $updated++;
                }

                // Check if story has characters
                $hasCharacters = $story->characters()->count() > 0;

                if (!$hasCharacters && !empty($storyInfo['characters'])) {
                    $this->line("      👥 Creating characters...");
                    $this->createCharacters($story, $storyInfo['characters'], $dryRun);
                } else if ($hasCharacters) {
                    $this->line("      ✓ Story already has characters");
                } else {
                    $this->line("      ⚠️  No characters found in markdown file");
                }

                // Upload markdown file if not already uploaded
                if (empty($story->script_file_url)) {
                    $this->line("      📄 Uploading markdown file...");
                    $this->uploadMarkdownFile($story, $storyInfo['file_path'], $dryRun);
                } else {
                    $this->line("      ✓ Markdown file already uploaded");
                }

            } catch (\Exception $e) {
                $this->error("      ❌ Error processing story: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("\n   📊 Summary:");
        $this->info("      Processed: {$processed}");
        $this->info("      Created: {$created}");
        $this->info("      Updated: {$updated}");
        $this->info("      Errors: {$errors}");
    }

    /**
     * Create a new story in database
     */
    protected function createStory($storyInfo, $dryRun)
    {
        if ($dryRun) {
            $this->warn("      ⚠️  Would create story (dry run)");
            return null;
        }

        // Get category
        $category = null;
        if (!empty($storyInfo['category'])) {
            $category = Category::where('name', $storyInfo['category'])->first();
        }

        if (!$category) {
            // Use first available category as fallback
            $category = Category::first();
            if (!$category) {
                $this->error("      ❌ No categories available in database");
                return null;
            }
            $this->warn("      ⚠️  Category '{$storyInfo['category']}' not found, using '{$category->name}'");
        }

        // Default values
        $ageGroup = $storyInfo['age_group'] ?? '6-8 سال';
        $description = $storyInfo['description'] ?? $storyInfo['title'];

        try {
            $story = Story::create([
                'title' => $storyInfo['title'],
                'subtitle' => null,
                'description' => $description,
                'image_url' => 'stories/default.jpg', // Default image
                'category_id' => $category->id,
                'age_group' => $ageGroup,
                'language' => 'fa',
                'duration' => 0,
                'total_episodes' => 0,
                'free_episodes' => 0,
                'is_premium' => false,
                'is_completely_free' => false,
                'status' => 'draft',
                'tags' => null,
            ]);

            $this->info("      ✅ Created story (ID: {$story->id})");
            return $story;

        } catch (\Exception $e) {
            $this->error("      ❌ Failed to create story: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create characters for a story
     */
    protected function createCharacters($story, $characters, $dryRun)
    {
        if ($dryRun) {
            $this->warn("      ⚠️  Would create " . count($characters) . " characters (dry run)");
            return;
        }

        foreach ($characters as $charInfo) {
            try {
                // Check if character already exists
                $existing = Character::where('story_id', $story->id)
                    ->where('name', $charInfo['name'])
                    ->first();

                if ($existing) {
                    $this->line("         ✓ Character '{$charInfo['name']}' already exists");
                    continue;
                }

                Character::create([
                    'story_id' => $story->id,
                    'name' => $charInfo['name'],
                    'description' => $charInfo['description'],
                    'voice_actor_id' => null, // No voice actor assigned
                    'image_url' => null,
                ]);

                $this->info("         ✅ Created character: {$charInfo['name']}");

            } catch (\Exception $e) {
                $this->error("         ❌ Failed to create character '{$charInfo['name']}': " . $e->getMessage());
            }
        }
    }

    /**
     * Upload markdown file for a story
     */
    protected function uploadMarkdownFile($story, $filePath, $dryRun)
    {
        if ($dryRun) {
            $this->warn("      ⚠️  Would upload markdown file (dry run)");
            return;
        }

        if (!file_exists($filePath)) {
            $this->error("      ❌ Markdown file not found: {$filePath}");
            return;
        }

        try {
            // Create directory if it doesn't exist
            $directory = 'stories/scripts';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Generate filename
            $filename = time() . '_' . $story->id . '_' . Str::slug($story->title) . '.md';
            $destinationPath = $directory . '/' . $filename;

            // Copy file to storage
            $fileContent = file_get_contents($filePath);
            Storage::disk('public')->put($destinationPath, $fileContent);

            // Get URL
            $url = Storage::url($destinationPath);

            // Update story
            $story->update(['script_file_url' => $url]);

            $this->info("      ✅ Uploaded markdown file: {$filename}");

        } catch (\Exception $e) {
            $this->error("      ❌ Failed to upload markdown file: " . $e->getMessage());
        }
    }
}

