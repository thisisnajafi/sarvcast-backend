<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_handles_missing_performance_metric_columns_without_throwing(): void
    {
        Schema::dropIfExists('performance_metrics');
        Schema::create('performance_metrics', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Route::middleware('performance')->get('/__performance-middleware-test', function () {
            return response()->json(['ok' => true]);
        });

        $response = $this->get('/__performance-middleware-test');

        $response->assertOk();
        $this->assertSame(1, DB::table('performance_metrics')->count());
    }
}
