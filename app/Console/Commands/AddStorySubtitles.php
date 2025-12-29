<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use Illuminate\Support\Facades\DB;

class AddStorySubtitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:add-subtitles 
                            {--stories-path= : Path to sarvcast-stories directory}
                            {--dry-run : Show what would be added without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add subtitles to stories that don\'t have them, extracted from markdown files';

    /**
     * Base path to stories directory
     */
    protected $storiesPath;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Finding stories without subtitles...');
        
        // Determine stories path
        $this->storiesPath = $this->option('stories-path');
        if (!$this->storiesPath) {
            $possiblePaths = [
                base_path('../sarvcast-stories'),
                base_path('../../sarvcast-stories'),
                base_path('sarvcast-stories'),
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
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }
        
        // Find stories without subtitles
        $storiesWithoutSubtitle = Story::whereNull('subtitle')
            ->orWhere('subtitle', '')
            ->get();
        
        if ($storiesWithoutSubtitle->isEmpty()) {
            $this->info('✅ All stories already have subtitles.');
            return 0;
        }
        
        $this->info("📊 Found {$storiesWithoutSubtitle->count()} stories without subtitles");
        
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($storiesWithoutSubtitle as $story) {
            // Find markdown file for this story
            $mdFile = $this->findMarkdownFileForStory($story);
            
            if (!$mdFile) {
                if ($dryRun) {
                    $this->line("   ⚠️  No markdown file found for: {$story->title}");
                }
                $skipped++;
                continue;
            }
            
            $subtitle = $this->extractSubtitleFromMarkdown($mdFile);
            
            if (!$subtitle) {
                if ($dryRun) {
                    $this->line("   ⚠️  No subtitle found in markdown for: {$story->title}");
                }
                $skipped++;
                continue;
            }
            
            if ($dryRun) {
                $this->line("   📝 Would add subtitle to '{$story->title}': {$subtitle}");
            } else {
                try {
                    $story->update(['subtitle' => $subtitle]);
                    $this->info("   ✅ Added subtitle to: {$story->title}");
                    $updated++;
                } catch (\Exception $e) {
                    $this->error("   ❌ Error updating '{$story->title}': " . $e->getMessage());
                    $errors++;
                }
            }
        }
        
        if ($dryRun) {
            $this->warn("\n⚠️  Would add subtitles to {$updated} stories (dry run)");
            $this->info("   Skipped: {$skipped}");
        } else {
            $this->info("\n✅ Story subtitles update completed!");
            $this->info("   - Updated: {$updated}");
            $this->info("   - Skipped: {$skipped}");
            $this->info("   - Errors: {$errors}");
        }
        
        return 0;
    }
    
    /**
     * Find markdown file for a story
     */
    protected function findMarkdownFileForStory($story)
    {
        $basePath = $this->storiesPath;
        $directories = glob($basePath . '/*', GLOB_ONLYDIR);
        
        $excludePatterns = [
            'README', 'CHANGELOG', 'GUIDE', 'TEMPLATE', 'PROMPT',
            'ANIMATED', 'COMPLETE', 'QUICK_START', 'PROJECT_SUMMARY',
            '__pycache__', '.git', 'iransans', 'sarvcast-team', 'Voices'
        ];
        
        foreach ($directories as $dir) {
            $dirName = basename($dir);
            
            // Skip excluded directories
            if (in_array($dirName, $excludePatterns) || 
                preg_match('/^(' . implode('|', $excludePatterns) . ')/i', $dirName)) {
                continue;
            }
            
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
                    
                    $content = file_get_contents($mdFile);
                    if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
                        $title = trim($matches[1]);
                        // Remove English part in parentheses
                        if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/', $title, $titleMatch)) {
                            $title = trim($titleMatch[1]);
                        }
                        
                        if ($title === $story->title) {
                            return $mdFile;
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract subtitle from markdown file or directory name
     */
    protected function extractSubtitleFromMarkdown($filePath)
    {
        $content = file_get_contents($filePath);
        
        // Method 1: Extract from title with parentheses (e.g., "# دوست تنها (Lonely Friend)")
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            $title = trim($matches[1]);
            // Extract English part in parentheses as subtitle
            if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/', $title, $titleMatch)) {
                return trim($titleMatch[2]); // English part as subtitle
            }
        }
        
        // Method 2: Extract from second heading (e.g., "## Little Kitchen")
        if (preg_match('/^##\s+(.+)$/m', $content, $matches)) {
            $subtitle = trim($matches[1]);
            // Check if it's English (contains Latin characters)
            if (preg_match('/[a-zA-Z]/', $subtitle)) {
                return $subtitle;
            }
        }
        
        // Method 3: Try to extract from directory name (e.g., "27 - jealous toys")
        $dir = dirname($filePath);
        $parentDir = dirname($dir);
        $dirName = basename($parentDir);
        
        if (preg_match('/^\d+\s*-\s*(.+)$/', $dirName, $matches)) {
            $englishName = trim($matches[1]);
            // Convert to title case
            return ucwords(str_replace(['-', '_'], ' ', $englishName));
        }
        
        return null;
    }
}

