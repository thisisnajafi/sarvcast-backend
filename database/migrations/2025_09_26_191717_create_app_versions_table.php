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
            $table->string('platform', 20); // 'android', 'ios', 'both'
            $table->string('version', 20); // e.g., '1.0.0', '2.1.3'
            $table->string('build_number', 20)->nullable(); // e.g., '100', '203'
            $table->enum('update_type', ['optional', 'force'])->default('optional');
            $table->string('download_url', 500); // Link to download/update
            $table->text('release_notes')->nullable(); // What's new in this version
            $table->text('update_message')->nullable(); // Custom message for users
            $table->boolean('is_active')->default(true); // Whether this version is active
            $table->boolean('is_latest')->default(false); // Whether this is the latest version
            $table->integer('min_supported_version_code')->nullable(); // Minimum supported version
            $table->integer('target_version_code')->nullable(); // Target version code
            $table->json('compatibility_requirements')->nullable(); // OS requirements, etc.
            $table->timestamp('release_date')->nullable(); // When this version was released
            $table->timestamp('effective_date')->nullable(); // When this version becomes effective
            $table->timestamp('expiry_date')->nullable(); // When this version expires
            $table->unsignedBigInteger('created_by')->nullable(); // Admin who created this
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('platform');
            $table->index('version');
            $table->index('is_active');
            $table->index('is_latest');
            $table->index('update_type');
            $table->index('effective_date');
            
            // Unique constraint for platform + version
            $table->unique(['platform', 'version']);
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