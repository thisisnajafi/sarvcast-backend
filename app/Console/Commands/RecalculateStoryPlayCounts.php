<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;

class RecalculateStoryPlayCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:recalculate-play-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate story play counts from episode play counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recalculating story play counts...');
        
        $stories = Story::all();
        $bar = $this->output->createProgressBar($stories->count());
        $bar->start();

        $updated = 0;
        foreach ($stories as $story) {
            $story->recalculatePlayCount();
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully recalculated play counts for {$updated} stories.");
        
        return Command::SUCCESS;
    }
}

