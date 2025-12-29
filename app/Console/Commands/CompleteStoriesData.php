<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Episode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class CompleteStoriesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:complete-data 
                            {--stories-path= : Path to sarvcast-stories directory}
                            {--dry-run : Run without making database changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete stories data: add subtitles, descriptions, create episodes, and upload episode scripts';

    /**
     * Base path to stories directory
     */
    protected $storiesPath;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting story data completion...');
        
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
            $this->warn('⚠️  DRY RUN MODE - No database changes will be made');
        }
        
        // Step 1: Update stories with subtitles and descriptions
        $this->info("\n📝 Step 1: Updating stories with subtitles and descriptions...");
        $this->updateStoriesMetadata($dryRun);
        
        // Step 2: Create episodes and upload scripts
        $this->info("\n📚 Step 2: Creating episodes and uploading scripts...");
        $this->createEpisodesWithScripts($dryRun);
        
        $this->info("\n✅ Story data completion finished!");
        return 0;
    }
    
    /**
     * Update stories with subtitles and descriptions from markdown files
     */
    protected function updateStoriesMetadata($dryRun)
    {
        $stories = Story::all();
        $updated = 0;
        $skipped = 0;
        
        foreach ($stories as $story) {
            // Find markdown file for this story
            $mdFile = $this->findMarkdownFileForStory($story);
            
            if (!$mdFile) {
                $this->line("   ⚠️  No markdown file found for: {$story->title}");
                $skipped++;
                continue;
            }
            
            $storyInfo = $this->parseMarkdownFile($mdFile);
            
            $updates = [];
            
            // Update subtitle if missing
            if (empty($story->subtitle) && !empty($storyInfo['subtitle'])) {
                $updates['subtitle'] = $storyInfo['subtitle'];
            }
            
            // Update description if it's just the title or empty
            if ((empty($story->description) || $story->description === $story->title) && !empty($storyInfo['description'])) {
                $updates['description'] = $storyInfo['description'];
            }
            
            if (!empty($updates)) {
                if ($dryRun) {
                    $this->line("   📝 Would update '{$story->title}': " . json_encode($updates, JSON_UNESCAPED_UNICODE));
                } else {
                    $story->update($updates);
                    $this->info("   ✅ Updated '{$story->title}'");
                }
                $updated++;
            } else {
                $skipped++;
            }
        }
        
        $this->info("   📊 Updated: {$updated}, Skipped: {$skipped}");
    }
    
    /**
     * Create episodes and upload scripts
     */
    protected function createEpisodesWithScripts($dryRun)
    {
        $stories = Story::all();
        $episodesCreated = 0;
        $scriptsUploaded = 0;
        $errors = 0;
        
        foreach ($stories as $story) {
            // Check if story already has episodes
            $existingEpisodes = $story->episodes()->count();
            
            if ($existingEpisodes > 0) {
                $this->line("   ⏭️  Story '{$story->title}' already has {$existingEpisodes} episode(s)");
                continue;
            }
            
            // Find episode directories for this story
            $episodeDirs = $this->findEpisodeDirectoriesForStory($story);
            
            if (empty($episodeDirs)) {
                $this->line("   ⚠️  No episode directories found for: {$story->title}");
                continue;
            }
            
            $this->info("   📖 Processing story: {$story->title}");
            
            foreach ($episodeDirs as $episodeDir) {
                try {
                    $episodeInfo = $this->parseEpisodeDirectory($episodeDir, $story);
                    
                    if (!$episodeInfo) {
                        continue;
                    }
                    
                    // Check if episode already exists
                    $existingEpisode = Episode::where('story_id', $story->id)
                        ->where('episode_number', $episodeInfo['episode_number'])
                        ->first();
                    
                    if ($existingEpisode) {
                        $this->line("      ⏭️  Episode {$episodeInfo['episode_number']} already exists");
                        
                        // Upload script if not already uploaded
                        if (empty($existingEpisode->script_file_url) && !empty($episodeInfo['script_file'])) {
                            if ($dryRun) {
                                $this->line("      📄 Would upload script for episode {$episodeInfo['episode_number']}");
                            } else {
                                $this->uploadEpisodeScript($existingEpisode, $episodeInfo['script_file']);
                                $scriptsUploaded++;
                            }
                        }
                        continue;
                    }
                    
                    if ($dryRun) {
                        $this->line("      ➕ Would create episode {$episodeInfo['episode_number']}: {$episodeInfo['title']}");
                        if (!empty($episodeInfo['script_file'])) {
                            $this->line("      📄 Would upload script: {$episodeInfo['script_file']}");
                        }
                    } else {
                        // Create episode
                        $episode = Episode::create([
                            'story_id' => $story->id,
                            'title' => $episodeInfo['title'],
                            'description' => $episodeInfo['description'],
                            'episode_number' => $episodeInfo['episode_number'],
                            'duration' => $episodeInfo['duration'] ?? 0, // Default to 0 if not found
                            'audio_url' => 'audio/episodes/placeholder.mp3', // Placeholder
                            'is_premium' => false,
                            'status' => 'draft',
                        ]);
                        
                        $this->info("      ✅ Created episode {$episodeInfo['episode_number']}: {$episodeInfo['title']}");
                        $episodesCreated++;
                        
                        // Upload script if available
                        if (!empty($episodeInfo['script_file'])) {
                            $this->uploadEpisodeScript($episode, $episodeInfo['script_file']);
                            $scriptsUploaded++;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $this->error("      ❌ Error processing episode: " . $e->getMessage());
                    $errors++;
                }
            }
        }
        
        $this->info("\n   📊 Summary:");
        $this->info("      Episodes created: {$episodesCreated}");
        $this->info("      Scripts uploaded: {$scriptsUploaded}");
        $this->info("      Errors: {$errors}");
    }
    
    /**
     * Find markdown file for a story
     */
    protected function findMarkdownFileForStory($story)
    {
        $basePath = $this->storiesPath;
        $directories = glob($basePath . '/*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $episodeDirs = glob($dir . '/episode*', GLOB_ONLYDIR);
            
            foreach ($episodeDirs as $episodeDir) {
                $mdFiles = glob($episodeDir . '/*.md');
                
                foreach ($mdFiles as $mdFile) {
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
     * Find episode directories for a story
     */
    protected function findEpisodeDirectoriesForStory($story)
    {
        $basePath = $this->storiesPath;
        $directories = glob($basePath . '/*', GLOB_ONLYDIR);
        $episodeDirs = [];
        
        foreach ($directories as $dir) {
            $episodeDirsInStory = glob($dir . '/episode*', GLOB_ONLYDIR);
            
            foreach ($episodeDirsInStory as $episodeDir) {
                $mdFiles = glob($episodeDir . '/*.md');
                
                foreach ($mdFiles as $mdFile) {
                    $content = file_get_contents($mdFile);
                    if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
                        $title = trim($matches[1]);
                        // Remove English part in parentheses
                        if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/', $title, $titleMatch)) {
                            $title = trim($titleMatch[1]);
                        }
                        
                        if ($title === $story->title) {
                            $episodeDirs[] = $episodeDir;
                            break;
                        }
                    }
                }
            }
        }
        
        return $episodeDirs;
    }
    
    /**
     * Parse markdown file to extract story information
     */
    protected function parseMarkdownFile($filePath)
    {
        $content = file_get_contents($filePath);
        $info = [
            'subtitle' => null,
            'description' => null,
        ];
        
        // Extract title (first # heading)
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            $title = trim($matches[1]);
            // Extract subtitle from title if it has English part
            if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/', $title, $titleMatch)) {
                $info['subtitle'] = trim($titleMatch[2]); // English part as subtitle
            }
        }
        
        // Extract description (پیام اصلی)
        if (preg_match('/\*\*پیام اصلی\*\*:\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['description'] = trim($matches[1]);
        } elseif (preg_match('/پیام اصلی[:\-]\s*(.+?)(?:\n|$)/', $content, $matches)) {
            $info['description'] = trim($matches[1]);
        }
        
        return $info;
    }
    
    /**
     * Parse episode directory to extract episode information
     */
    protected function parseEpisodeDirectory($episodeDir, $story)
    {
        // Extract episode number from directory name
        if (!preg_match('/episode\s*(\d+)/i', basename($episodeDir), $matches)) {
            return null;
        }
        
        $episodeNumber = (int)$matches[1];
        
        // Extract episode title from directory name
        $episodeTitle = basename($episodeDir);
        if (preg_match('/episode\s*\d+\s*-\s*(.+)/i', $episodeTitle, $titleMatch)) {
            $episodeTitle = trim($titleMatch[1]);
        } else {
            $episodeTitle = $story->title . ' - قسمت ' . $episodeNumber;
        }
        
        // Find markdown file
        $mdFiles = glob($episodeDir . '/*.md');
        $scriptFile = null;
        $description = null;
        $duration = null;
        
        foreach ($mdFiles as $mdFile) {
            // Skip documentation files
            $fileName = basename($mdFile);
            $fileStem = strtoupper(pathinfo($fileName, PATHINFO_FILENAME));
            $excludePatterns = ['README', 'CHANGELOG', 'GUIDE', 'TEMPLATE', 'PROMPT', 'ANIMATED', 'COMPLETE'];
            
            if (preg_match('/(' . implode('|', $excludePatterns) . ')/i', $fileStem)) {
                continue;
            }
            
            $scriptFile = $mdFile;
            
            // Parse markdown for description and duration
            $content = file_get_contents($mdFile);
            
            // Extract description (پیام اصلی)
            if (preg_match('/\*\*پیام اصلی\*\*:\s*(.+?)(?:\n|$)/', $content, $matches)) {
                $description = trim($matches[1]);
            }
            
            // Extract duration (مدت زمان)
            if (preg_match('/\*\*مدت زمان\*\*:\s*(\d+)/', $content, $matches)) {
                $duration = (int)$matches[1]; // in minutes
            }
            
            break;
        }
        
        return [
            'episode_number' => $episodeNumber,
            'title' => $episodeTitle,
            'description' => $description,
            'duration' => $duration,
            'script_file' => $scriptFile,
        ];
    }
    
    /**
     * Upload episode script
     */
    protected function uploadEpisodeScript($episode, $scriptFilePath)
    {
        if (!file_exists($scriptFilePath)) {
            $this->error("      ❌ Script file not found: {$scriptFilePath}");
            return;
        }
        
        try {
            // Create directory if it doesn't exist
            $directory = 'episodes/scripts';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            // Generate filename
            $filename = time() . '_' . $episode->id . '_episode_' . $episode->episode_number . '.md';
            $destinationPath = $directory . '/' . $filename;
            
            // Copy file to storage
            $fileContent = file_get_contents($scriptFilePath);
            Storage::disk('public')->put($destinationPath, $fileContent);
            
            // Get URL
            $url = Storage::url($destinationPath);
            
            // Update episode
            $episode->update(['script_file_url' => $url]);
            
            $this->info("      ✅ Uploaded script: {$filename}");
            
        } catch (\Exception $e) {
            $this->error("      ❌ Failed to upload script: " . $e->getMessage());
        }
    }
}

