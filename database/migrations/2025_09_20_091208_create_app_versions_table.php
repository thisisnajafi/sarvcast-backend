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
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique(); // e.g., "1.0.0", "2.1.3"
            $table->string('build_number', 50)->nullable(); // e.g., "100", "201"
            $table->enum('platform', ['android', 'ios', 'web', 'all'])->default('all');
            $table->enum('update_type', ['optional', 'forced', 'maintenance'])->default('optional');
            $table->string('title', 100); // Update title
            $table->text('description')->nullable(); // Update description
            $table->text('changelog')->nullable(); // What's new
            $table->text('update_notes')->nullable(); // Additional notes
            $table->string('download_url')->nullable(); // App store or direct download URL
            $table->string('minimum_os_version', 20)->nullable(); // Minimum OS version required
            $table->json('compatibility')->nullable(); // Device compatibility info
            $table->boolean('is_active')->default(true); // Whether this version is currently active
            $table->boolean('is_latest')->default(false); // Whether this is the latest version
            $table->timestamp('release_date')->nullable(); // When this version was released
            $table->timestamp('force_update_date')->nullable(); // When to start forcing this update
            $table->integer('priority')->default(0); // Priority for display order
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            // Indexes
            $table->index(['platform', 'is_active']);
            $table->index(['platform', 'is_latest']);
            $table->index(['update_type', 'is_active']);
            $table->index('release_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};