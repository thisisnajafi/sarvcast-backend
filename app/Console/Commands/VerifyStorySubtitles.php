<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;

class VerifyStorySubtitles extends Command
{
    protected $signature = 'stories:verify-subtitles';
    protected $description = 'Verify story subtitles are in Persian';

    public function handle()
    {
        $stories = Story::whereNotNull('subtitle')
            ->where('subtitle', '!=', '')
            ->get();
        
        $englishSubtitles = [];
        $persianSubtitles = [];
        
        foreach ($stories as $story) {
            // Check if subtitle contains English (Latin characters)
            if (preg_match('/[a-zA-Z]/', $story->subtitle)) {
                $englishSubtitles[] = [
                    'title' => $story->title,
                    'subtitle' => $story->subtitle,
                ];
            } else {
                $persianSubtitles[] = [
                    'title' => $story->title,
                    'subtitle' => mb_substr($story->subtitle, 0, 50) . '...',
                ];
            }
        }
        
        $this->info("Total stories with subtitles: " . $stories->count());
        $this->info("Persian subtitles: " . count($persianSubtitles));
        $this->info("English subtitles: " . count($englishSubtitles));
        
        if (!empty($englishSubtitles)) {
            $this->warn("\n⚠️  Stories with English subtitles:");
            $this->table(['Title', 'Subtitle'], array_slice($englishSubtitles, 0, 10));
            if (count($englishSubtitles) > 10) {
                $this->line("... and " . (count($englishSubtitles) - 10) . " more");
            }
        }
        
        if (empty($englishSubtitles)) {
            $this->info("\n✅ All subtitles are in Persian!");
        }
        
        return 0;
    }
}

