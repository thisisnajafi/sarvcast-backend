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
        if (!Schema::hasTable('performance_metrics')) {
            Schema::create('performance_metrics', function (Blueprint $table): void {
                $table->id();
                $table->string('feature')->nullable();
                $table->json('data')->nullable();
                $table->decimal('response_time', 10, 2)->nullable();
                $table->bigInteger('memory_usage')->nullable();
                $table->integer('database_queries')->nullable();
                $table->string('url')->nullable();
                $table->string('method', 16)->nullable();
                $table->string('route_name')->nullable();
                $table->decimal('execution_time', 12, 3)->nullable();
                $table->bigInteger('peak_memory')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
            return;
        }

        Schema::table('performance_metrics', function (Blueprint $table): void {
            if (!Schema::hasColumn('performance_metrics', 'url')) {
                $table->string('url')->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'method')) {
                $table->string('method', 16)->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'route_name')) {
                $table->string('route_name')->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'execution_time')) {
                $table->decimal('execution_time', 12, 3)->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'memory_usage')) {
                $table->bigInteger('memory_usage')->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'peak_memory')) {
                $table->bigInteger('peak_memory')->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('performance_metrics', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to avoid destructive changes on production data.
    }
};
