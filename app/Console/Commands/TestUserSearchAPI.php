<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\UserController;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;

class TestUserSearchAPI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:user-search-api {query?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test user search API endpoint';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query') ?? '0913';
        
        $this->info("Testing user search API with query: {$query}");
        
        try {
            $request = new Request(['q' => $query]);
            $controller = new UserController(new InAppNotificationService());
            $response = $controller->search($request);
            
            $this->info("Response status: " . $response->getStatusCode());
            $this->info("Response content: " . $response->getContent());
            
        } catch (\Exception $e) {
            $this->error("Error testing API: " . $e->getMessage());
        }
        
        $this->info('API test completed!');
    }
}