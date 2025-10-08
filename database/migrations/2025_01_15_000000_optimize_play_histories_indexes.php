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
        Schema::table('play_histories', function (Blueprint $table) {
            // Add composite indexes for common query patterns
            
            // For user play history queries (user_id + played_at)
            $table->index(['user_id', 'played_at'], 'idx_user_played_at');
            
            // For episode statistics queries (episode_id + played_at)
            $table->index(['episode_id', 'played_at'], 'idx_episode_played_at');
            
            // For story statistics queries (story_id + played_at)
            $table->index(['story_id', 'played_at'], 'idx_story_played_at');
            
            // For completed episodes queries (user_id + completed + played_at)
            $table->index(['user_id', 'completed', 'played_at'], 'idx_user_completed_played_at');
            
            // For incomplete episodes queries (user_id + completed + played_at)
            $table->index(['user_id', 'completed', 'played_at'], 'idx_user_incomplete_played_at');
            
            // For analytics queries (played_at + completed)
            $table->index(['played_at', 'completed'], 'idx_played_at_completed');
            
            // For recent plays queries (played_at)
            $table->index(['played_at'], 'idx_played_at_desc');
            
            // For user episode combination (user_id + episode_id)
            $table->index(['user_id', 'episode_id'], 'idx_user_episode');
            
            // For user story combination (user_id + story_id)
            $table->index(['user_id', 'story_id'], 'idx_user_story');
            
            // For duration-based queries
            $table->index(['duration_played'], 'idx_duration_played');
            $table->index(['total_duration'], 'idx_total_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_histories', function (Blueprint $table) {
            // Drop the indexes
            $table->dropIndex('idx_user_played_at');
            $table->dropIndex('idx_episode_played_at');
            $table->dropIndex('idx_story_played_at');
            $table->dropIndex('idx_user_completed_played_at');
            $table->dropIndex('idx_user_incomplete_played_at');
            $table->dropIndex('idx_played_at_completed');
            $table->dropIndex('idx_played_at_desc');
            $table->dropIndex('idx_user_episode');
            $table->dropIndex('idx_user_story');
            $table->dropIndex('idx_duration_played');
            $table->dropIndex('idx_total_duration');
        });
    }
};
