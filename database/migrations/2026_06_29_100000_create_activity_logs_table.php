<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->index();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 20)->default('admin');
            $table->string('action', 64)->index();
            $table->string('subject_type', 64)->nullable()->index();
            $table->string('subject_id', 64)->nullable()->index();
            $table->string('subject_label', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 64)->nullable()->index();
            $table->string('device_id', 64)->nullable()->index();
            $table->string('app_version', 32)->nullable();
            $table->string('platform', 16)->nullable();
            $table->string('status', 16)->default('success');
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->nullable();

            $table->index(['channel', 'occurred_at']);
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
