<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add story_id column to image_timelines table
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->foreignId('story_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        // Migrate existing data: copy episode's story_id to image_timelines
        DB::statement('
            UPDATE image_timelines 
            SET story_id = (
                SELECT episodes.story_id 
                FROM episodes 
                WHERE episodes.id = image_timelines.episode_id
            )
        ');

        // Make story_id required (remove nullable)
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->foreignId('story_id')->nullable(false)->change();
        });

        // Update indexes
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->dropIndex('idx_episode_time');
            $table->dropIndex('idx_episode_order');
            
            $table->index(['story_id', 'start_time', 'end_time'], 'idx_story_time');
            $table->index(['story_id', 'image_order'], 'idx_story_order');
        });

        // Add use_image_timeline column to stories table
        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('use_image_timeline')->default(false)->comment('Whether story uses timeline-based image changes');
        });

        // Migrate episode's use_image_timeline to story level
        DB::statement('
            UPDATE stories 
            SET use_image_timeline = true 
            WHERE id IN (
                SELECT DISTINCT episodes.story_id 
                FROM episodes 
                WHERE episodes.use_image_timeline = true
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove story_id column and restore episode-based structure
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->dropIndex('idx_story_time');
            $table->dropIndex('idx_story_order');
            
            $table->index(['episode_id', 'start_time', 'end_time'], 'idx_episode_time');
            $table->index(['episode_id', 'image_order'], 'idx_episode_order');
        });

        Schema::table('image_timelines', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
            $table->dropColumn('story_id');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('use_image_timeline');
        });
    }
};
