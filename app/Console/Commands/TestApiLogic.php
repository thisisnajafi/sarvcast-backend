<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;

class TestApiLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:test-logic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test API logic without database connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing API logic...');
        
        try {
            // Test StoryController
            $this->info('Testing StoryController...');
            $storyController = new StoryController(new \App\Services\AccessControlService());
            
            // Create a mock request
            $request = Request::create('/api/v1/stories', 'GET');
            
            // Test the index method
            $response = $storyController->index($request);
            $this->info('StoryController response status: ' . $response->getStatusCode());
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                $this->info('Stories count: ' . count($data['data'] ?? []));
                if (count($data['data'] ?? []) > 0) {
                    $this->info('First story: ' . json_encode($data['data'][0] ?? 'none'));
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error testing StoryController: ' . $e->getMessage());
        }
        
        try {
            // Test CategoryController
            $this->info('Testing CategoryController...');
            $categoryController = new CategoryController();
            
            // Create a mock request
            $request = Request::create('/api/v1/categories', 'GET');
            
            // Test the index method
            $response = $categoryController->index($request);
            $this->info('CategoryController response status: ' . $response->getStatusCode());
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                $this->info('Categories count: ' . count($data['data'] ?? []));
                if (count($data['data'] ?? []) > 0) {
                    $this->info('First category: ' . json_encode($data['data'][0] ?? 'none'));
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error testing CategoryController: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}