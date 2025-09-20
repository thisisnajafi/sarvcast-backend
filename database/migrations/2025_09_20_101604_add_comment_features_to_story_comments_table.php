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
        Schema::table('story_comments', function (Blueprint $table) {
            // Add nested comments support
            $table->unsignedBigInteger('parent_id')->nullable()->after('user_id');
            $table->foreign('parent_id')->references('id')->on('story_comments')->onDelete('cascade');
            
            // Rename comment to content for consistency
            $table->renameColumn('comment', 'content');
            
            // Add additional features
            $table->boolean('is_pinned')->default(false)->after('is_visible');
            $table->integer('likes_count')->default(0)->after('is_pinned');
            $table->integer('replies_count')->default(0)->after('likes_count');
            $table->json('metadata')->nullable()->after('replies_count');
            
            // Add indexes
            $table->index(['parent_id', 'created_at']);
            $table->index('is_pinned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('story_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_pinned', 'likes_count', 'replies_count', 'metadata']);
            $table->renameColumn('content', 'comment');
            $table->dropIndex(['parent_id', 'created_at']);
            $table->dropIndex(['is_pinned']);
        });
    }
};