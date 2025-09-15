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
        // System metrics table
        Schema::create('system_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // cpu, memory, disk, network, etc.
            $table->string('metric_name'); // specific metric name
            $table->decimal('value', 10, 4); // metric value
            $table->string('unit')->nullable(); // unit of measurement
            $table->json('metadata')->nullable(); // additional data
            $table->timestamp('recorded_at');
            
            $table->index(['metric_type', 'recorded_at']);
            $table->index(['metric_name', 'recorded_at']);
            $table->index('recorded_at');
        });

        // API performance logs table
        Schema::create('api_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint'); // API endpoint
            $table->string('method'); // HTTP method
            $table->integer('status_code'); // HTTP status code
            $table->decimal('response_time', 8, 3); // response time in seconds
            $table->integer('memory_usage')->nullable(); // memory usage in bytes
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_id')->nullable();
            $table->json('request_data')->nullable(); // request payload
            $table->json('response_data')->nullable(); // response payload
            $table->timestamp('requested_at');
            
            $table->index(['endpoint', 'requested_at']);
            $table->index(['status_code', 'requested_at']);
            $table->index(['response_time', 'requested_at']);
            $table->index('requested_at');
        });

        // Error logs table
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level'); // error level (error, warning, critical, etc.)
            $table->string('type'); // error type
            $table->text('message'); // error message
            $table->text('stack_trace')->nullable(); // stack trace
            $table->string('file')->nullable(); // file where error occurred
            $table->integer('line')->nullable(); // line number
            $table->string('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('context')->nullable(); // additional context
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('occurred_at');
            
            $table->index(['level', 'occurred_at']);
            $table->index(['type', 'occurred_at']);
            $table->index(['resolved', 'occurred_at']);
            $table->index('occurred_at');
        });

        // System alerts table
        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // alert type
            $table->string('severity'); // critical, warning, info
            $table->string('title'); // alert title
            $table->text('message'); // alert message
            $table->json('metadata')->nullable(); // additional data
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('triggered_at');
            
            $table->index(['severity', 'triggered_at']);
            $table->index(['acknowledged', 'triggered_at']);
            $table->index(['resolved', 'triggered_at']);
            $table->index('triggered_at');
        });

        // Uptime monitoring table
        Schema::create('uptime_monitoring', function (Blueprint $table) {
            $table->id();
            $table->string('service_name'); // service being monitored
            $table->string('service_type'); // web, api, database, etc.
            $table->string('url')->nullable(); // URL for HTTP checks
            $table->boolean('is_up'); // service status
            $table->decimal('response_time', 8, 3)->nullable(); // response time
            $table->integer('status_code')->nullable(); // HTTP status code
            $table->text('error_message')->nullable(); // error details
            $table->json('metadata')->nullable(); // additional data
            $table->timestamp('checked_at');
            
            $table->index(['service_name', 'checked_at']);
            $table->index(['is_up', 'checked_at']);
            $table->index('checked_at');
        });

        // Security events table
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // login_failed, suspicious_activity, etc.
            $table->string('severity'); // low, medium, high, critical
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('user_id')->nullable();
            $table->text('description'); // event description
            $table->json('metadata')->nullable(); // additional data
            $table->boolean('blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('occurred_at');
            
            $table->index(['event_type', 'occurred_at']);
            $table->index(['severity', 'occurred_at']);
            $table->index(['ip_address', 'occurred_at']);
            $table->index(['blocked', 'occurred_at']);
            $table->index('occurred_at');
        });

        // Database query logs table
        Schema::create('database_query_logs', function (Blueprint $table) {
            $table->id();
            $table->text('query'); // SQL query
            $table->decimal('execution_time', 8, 3); // execution time in seconds
            $table->integer('rows_affected')->nullable();
            $table->string('connection_name')->nullable();
            $table->json('bindings')->nullable(); // query bindings
            $table->timestamp('executed_at');
            
            $table->index(['execution_time', 'executed_at']);
            $table->index('executed_at');
        });

        // Performance benchmarks table
        Schema::create('performance_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('benchmark_name'); // benchmark identifier
            $table->string('component'); // component being benchmarked
            $table->decimal('value', 10, 4); // benchmark value
            $table->string('unit'); // unit of measurement
            $table->json('metadata')->nullable(); // additional data
            $table->timestamp('measured_at');
            
            $table->index(['benchmark_name', 'measured_at']);
            $table->index(['component', 'measured_at']);
            $table->index('measured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_benchmarks');
        Schema::dropIfExists('database_query_logs');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('uptime_monitoring');
        Schema::dropIfExists('system_alerts');
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('api_performance_logs');
        Schema::dropIfExists('system_metrics');
    }
};