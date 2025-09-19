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
        Schema::create('image_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->onDelete('cascade');
            $table->integer('start_time')->comment('Start time in seconds');
            $table->integer('end_time')->comment('End time in seconds');
            $table->string('image_url', 500)->comment('Image URL for this time period');
            $table->integer('image_order')->comment('Order of image in timeline');
            $table->timestamps();
            
            $table->index(['episode_id', 'start_time', 'end_time'], 'idx_episode_time');
            $table->index(['episode_id', 'image_order'], 'idx_episode_order');
        });

        // Add use_image_timeline column to episodes table
        Schema::table('episodes', function (Blueprint $table) {
            $table->boolean('use_image_timeline')->default(false)->comment('Whether episode uses timeline-based image changes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('use_image_timeline');
        });
        
        Schema::dropIfExists('image_timelines');
    }
};