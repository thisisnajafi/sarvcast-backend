<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Person;

class TestNarratorQuery extends Command
{
    protected $signature = 'test:narrator-query';
    protected $description = 'Test narrator query to ensure it works';

    public function handle()
    {
        $this->info('Testing narrator query...');
        
        try {
            $narrators = Person::whereJsonContains('roles', 'narrator')->get();
            $this->info("Found {$narrators->count()} narrators");
            
            if ($narrators->count() > 0) {
                $this->info("Sample narrators:");
                foreach ($narrators->take(3) as $narrator) {
                    $this->info("- {$narrator->name} (Roles: " . implode(', ', $narrator->roles) . ")");
                }
            }
            
            $this->info('Query successful!');
            
        } catch (\Exception $e) {
            $this->error('Query failed: ' . $e->getMessage());
        }
    }
}