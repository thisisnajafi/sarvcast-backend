<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('feature')->comment('Feature being monitored (timeline, comment, etc.)');
            $table->json('data')->comment('Performance data in JSON format');
            $table->decimal('response_time', 10, 2)->comment('Response time in milliseconds');
            $table->bigInteger('memory_usage')->comment('Memory usage in bytes');
            $table->integer('database_queries')->comment('Number of database queries');
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index(['response_time', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};