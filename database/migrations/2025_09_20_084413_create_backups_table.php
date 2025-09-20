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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['database', 'files', 'config', 'full'])->default('full');
            $table->json('include_files')->nullable();
            $table->json('exclude_files')->nullable();
            $table->boolean('compression')->default(false);
            $table->boolean('encryption')->default(false);
            $table->string('schedule')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('file_path')->nullable();
            $table->bigInteger('size')->nullable(); // Size in bytes
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional backup metadata
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'type']);
            $table->index(['created_at', 'status']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};