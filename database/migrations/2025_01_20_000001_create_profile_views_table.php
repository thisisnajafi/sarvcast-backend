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
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viewed_user_id'); // The user whose profile was viewed
            $table->unsignedBigInteger('viewer_id')->nullable(); // The user who viewed (null for anonymous)
            $table->string('ip_address', 45)->nullable(); // IP address for anonymous tracking
            $table->string('user_agent')->nullable(); // Browser/device info
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('viewed_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('viewer_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index('viewed_user_id');
            $table->index('viewer_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};

