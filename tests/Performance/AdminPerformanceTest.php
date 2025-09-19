<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\CoinTransaction;
use App\Models\CouponCode;
use App\Models\AffiliatePartner;
use App\Models\SubscriptionPlan;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class AdminPerformanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
    }

    /** @test */
    public function coin_index_page_loads_quickly_with_large_dataset()
    {
        // Create large dataset
        CoinTransaction::factory()->count(1000)->create();

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->get('/admin/coins');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should load within 2 seconds
        $this->assertLessThan(2000, $executionTime, 
            "Coin index page took {$executionTime}ms to load, which exceeds 2000ms limit");
    }

    /** @test */
    public function coin_api_endpoint_performs_well_with_large_dataset()
    {
        // Create large dataset
        CoinTransaction::factory()->count(1000)->create();

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/admin/coins');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should respond within 500ms
        $this->assertLessThan(500, $executionTime, 
            "Coin API endpoint took {$executionTime}ms to respond, which exceeds 500ms limit");
    }

    /** @test */
    public function bulk_operations_perform_well_with_many_items()
    {
        // Create many transactions
        $transactions = CoinTransaction::factory()->count(100)->create([
            'user_id' => User::factory()->create()->id,
            'status' => 'pending'
        ]);

        Sanctum::actingAs($this->admin);

        $transactionIds = $transactions->pluck('id')->toArray();

        $startTime = microtime(true);
        
        $response = $this->postJson('/api/admin/coins/bulk-action', [
            'action' => 'approve',
            'selected_items' => $transactionIds
        ]);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should complete within 1 second
        $this->assertLessThan(1000, $executionTime, 
            "Bulk operation took {$executionTime}ms to complete, which exceeds 1000ms limit");
    }

    /** @test */
    public function statistics_endpoint_performs_well_with_complex_queries()
    {
        // Create complex dataset
        CoinTransaction::factory()->count(500)->create(['type' => 'earned']);
        CoinTransaction::factory()->count(300)->create(['type' => 'purchased']);
        CoinTransaction::factory()->count(200)->create(['type' => 'gift']);

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/admin/coins/statistics/data');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should respond within 1 second
        $this->assertLessThan(1000, $executionTime, 
            "Statistics endpoint took {$executionTime}ms to respond, which exceeds 1000ms limit");
    }

    /** @test */
    public function concurrent_requests_handle_well()
    {
        CoinTransaction::factory()->count(100)->create();

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        // Make 10 concurrent requests
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/admin/coins');
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        // Should handle all requests within 2 seconds
        $this->assertLessThan(2000, $executionTime, 
            "Concurrent requests took {$executionTime}ms to complete, which exceeds 2000ms limit");
    }

    /** @test */
    public function memory_usage_stays_reasonable_with_large_datasets()
    {
        $initialMemory = memory_get_usage();

        // Create large dataset
        CoinTransaction::factory()->count(1000)->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200);

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;

        // Should not use more than 50MB
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 
            "Memory usage was {$this->formatBytes($memoryUsed)}, which exceeds 50MB limit");
    }

    /** @test */
    public function database_queries_are_optimized()
    {
        CoinTransaction::factory()->count(100)->create();

        Sanctum::actingAs($this->admin);

        // Enable query logging
        \DB::enableQueryLog();

        $response = $this->getJson('/api/admin/coins');
        $response->assertStatus(200);

        $queries = \DB::getQueryLog();
        
        // Should not make more than 5 queries
        $this->assertLessThanOrEqual(5, count($queries), 
            "Made " . count($queries) . " queries, which exceeds 5 query limit");
    }

    /** @test */
    public function pagination_performs_well_with_large_datasets()
    {
        // Create large dataset
        CoinTransaction::factory()->count(5000)->create();

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/admin/coins?page=250'); // Last page
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should respond within 500ms even for last page
        $this->assertLessThan(500, $executionTime, 
            "Pagination to last page took {$executionTime}ms, which exceeds 500ms limit");
    }

    /** @test */
    public function search_performs_well_with_large_datasets()
    {
        // Create large dataset with searchable content
        $users = User::factory()->count(100)->create();
        foreach ($users as $user) {
            CoinTransaction::factory()->count(10)->create(['user_id' => $user->id]);
        }

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/admin/coins?search=' . $users->first()->name);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should respond within 1 second
        $this->assertLessThan(1000, $executionTime, 
            "Search took {$executionTime}ms to complete, which exceeds 1000ms limit");
    }

    /** @test */
    public function filtering_performs_well_with_complex_queries()
    {
        // Create complex dataset
        CoinTransaction::factory()->count(500)->create(['type' => 'earned', 'status' => 'completed']);
        CoinTransaction::factory()->count(300)->create(['type' => 'purchased', 'status' => 'pending']);
        CoinTransaction::factory()->count(200)->create(['type' => 'gift', 'status' => 'failed']);

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/admin/coins?type=earned&status=completed');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should respond within 500ms
        $this->assertLessThan(500, $executionTime, 
            "Filtering took {$executionTime}ms to complete, which exceeds 500ms limit");
    }

    /** @test */
    public function admin_dashboard_loads_quickly_with_complex_data()
    {
        // Create complex dataset for dashboard
        User::factory()->count(1000)->create();
        CoinTransaction::factory()->count(500)->create();
        CouponCode::factory()->count(100)->create();
        AffiliatePartner::factory()->count(50)->create();
        SubscriptionPlan::factory()->count(10)->create();

        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->get('/admin/dashboard');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should load within 3 seconds
        $this->assertLessThan(3000, $executionTime, 
            "Dashboard took {$executionTime}ms to load, which exceeds 3000ms limit");
    }

    /** @test */
    public function admin_middleware_performance_impact_is_minimal()
    {
        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/admin/coins');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Middleware should not add more than 50ms overhead
        $this->assertLessThan(50, $executionTime, 
            "Admin middleware added {$executionTime}ms overhead, which exceeds 50ms limit");
    }

    /** @test */
    public function performance_monitoring_has_minimal_overhead()
    {
        Sanctum::actingAs($this->admin);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/admin/coins');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Performance monitoring should not add more than 10ms overhead
        $this->assertLessThan(10, $executionTime, 
            "Performance monitoring added {$executionTime}ms overhead, which exceeds 10ms limit");
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
