<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\StoryController;
use App\Models\Story;

class TestStoryTags extends Command
{
    protected $signature = 'test:story-tags';
    protected $description = 'Test story tags conversion from string to array';

    public function handle()
    {
        $this->info('Testing story tags conversion...');
        
        // Simulate the conversion logic
        $tagsString = 'حیوانات، دوستانه، گاو';
        $this->info("Input string: '{$tagsString}'");
        
        // Convert comma-separated string to array (handle both Persian and English commas)
        $tagsString = str_replace('،', ',', $tagsString);
        $tagsArray = array_filter(array_map('trim', explode(',', $tagsString)));
        $this->info("Converted array: " . json_encode($tagsArray, JSON_UNESCAPED_UNICODE));
        
        // Test with different separators
        $testCases = [
            'حیوانات، دوستانه، گاو',
            'حیوانات, دوستانه, گاو',
            'حیوانات،دوستانه،گاو',
            'حیوانات , دوستانه , گاو',
            'حیوانات',
            '',
        ];
        
        $this->info("\nTesting different input formats:");
        foreach ($testCases as $testCase) {
            $testString = str_replace('،', ',', $testCase);
            $result = array_filter(array_map('trim', explode(',', $testString)));
            $this->info("'{$testCase}' -> " . json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        
        $this->info('\nTags conversion test completed!');
    }
}