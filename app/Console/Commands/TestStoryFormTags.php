<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestStoryFormTags extends Command
{
    protected $signature = 'test:story-form-tags';
    protected $description = 'Test story form tags display logic';

    public function handle()
    {
        $this->info('Testing story form tags display logic...');
        
        // Test cases for old() function simulation
        $testCases = [
            'string' => 'حیوانات، دوستانه، گاو',
            'array' => ['حیوانات', 'دوستانه', 'گاو'],
            'null' => null,
            'empty_string' => '',
            'empty_array' => [],
        ];
        
        $this->info("\nTesting old('tags') display logic:");
        foreach ($testCases as $type => $value) {
            $result = $this->simulateOldTagsDisplay($value);
            $this->info("Type: {$type}, Value: " . json_encode($value, JSON_UNESCAPED_UNICODE) . " -> Display: '{$result}'");
        }
        
        $this->info('\nForm display test completed!');
    }
    
    private function simulateOldTagsDisplay($oldTags)
    {
        // Simulate the Blade logic: is_array(old('tags')) ? implode(', ', old('tags')) : old('tags')
        if (is_array($oldTags)) {
            return implode(', ', $oldTags);
        }
        return $oldTags ?? '';
    }
}