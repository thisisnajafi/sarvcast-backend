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
        // User follows table
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('followed_at')->nullable();
            $table->boolean('is_mutual')->default(false);
            $table->json('metadata')->nullable(); // Additional follow data
            
            $table->unique(['follower_id', 'following_id']);
            $table->index(['follower_id', 'followed_at']);
            $table->index(['following_id', 'followed_at']);
            $table->index(['is_mutual', 'followed_at']);
        });

        // Content shares table
        Schema::create('content_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shareable_type'); // story, episode, playlist, etc.
            $table->unsignedBigInteger('shareable_id');
            $table->string('share_type'); // social_media, link, embed, etc.
            $table->string('platform')->nullable(); // facebook, twitter, instagram, etc.
            $table->text('message')->nullable(); // Custom message with share
            $table->string('share_url')->nullable(); // Generated share URL
            $table->json('metadata')->nullable(); // Additional share data
            $table->timestamp('shared_at')->nullable();
            
            $table->index(['user_id', 'shared_at']);
            $table->index(['shareable_type', 'shareable_id']);
            $table->index(['share_type', 'shared_at']);
            $table->index(['platform', 'shared_at']);
        });

        // User activity feed table
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // favorite, play, share, follow, etc.
            $table->string('activity_target_type')->nullable(); // story, episode, user, etc.
            $table->unsignedBigInteger('activity_target_id')->nullable();
            $table->text('activity_description')->nullable(); // Human-readable description
            $table->json('activity_data')->nullable(); // Additional activity data
            $table->boolean('is_public')->default(true); // Whether activity is public
            $table->boolean('is_anonymous')->default(false); // Whether activity is anonymous
            $table->timestamp('activity_at')->nullable();
            
            $table->index(['user_id', 'activity_at']);
            $table->index(['activity_type', 'activity_at']);
            $table->index(['activity_target_type', 'activity_target_id']);
            $table->index(['is_public', 'activity_at']);
        });

        // User playlists table
        Schema::create('user_playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_collaborative')->default(false);
            $table->string('cover_image')->nullable();
            $table->json('metadata')->nullable(); // Additional playlist data
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['is_public', 'created_at']);
            $table->index(['is_collaborative', 'created_at']);
        });

        // Playlist items table
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained('user_playlists')->onDelete('cascade');
            $table->string('item_type'); // story, episode
            $table->unsignedBigInteger('item_id');
            $table->integer('sort_order')->default(0);
            $table->timestamp('added_at')->nullable();
            
            $table->unique(['playlist_id', 'item_type', 'item_id']);
            $table->index(['playlist_id', 'sort_order']);
            $table->index(['item_type', 'item_id']);
        });

        // User comments table
        Schema::create('user_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('commentable_type'); // story, episode, playlist
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('parent_id')->nullable()->constrained('user_comments')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->integer('likes_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->json('metadata')->nullable(); // Additional comment data
            $table->timestamp('commented_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            $table->index(['user_id', 'commented_at']);
            $table->index(['commentable_type', 'commentable_id']);
            $table->index(['parent_id', 'commented_at']);
            $table->index(['is_approved', 'commented_at']);
            $table->index(['is_pinned', 'commented_at']);
        });

        // Comment likes table
        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('comment_id')->constrained('user_comments')->onDelete('cascade');
            $table->timestamp('liked_at')->nullable();
            
            $table->unique(['user_id', 'comment_id']);
            $table->index(['comment_id', 'liked_at']);
            $table->index(['user_id', 'liked_at']);
        });

        // User mentions table
        Schema::create('user_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mentioned_by')->constrained('users')->onDelete('cascade');
            $table->string('mentionable_type'); // comment, story, episode
            $table->unsignedBigInteger('mentionable_id');
            $table->text('context')->nullable(); // Context where mention occurred
            $table->boolean('is_read')->default(false);
            $table->timestamp('mentioned_at')->nullable();
            
            $table->index(['user_id', 'mentioned_at']);
            $table->index(['mentioned_by', 'mentioned_at']);
            $table->index(['mentionable_type', 'mentionable_id']);
            $table->index(['is_read', 'mentioned_at']);
        });

        // Social interactions table
        Schema::create('social_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('interaction_type'); // like, share, comment, follow, etc.
            $table->string('target_type'); // story, episode, user, comment, etc.
            $table->unsignedBigInteger('target_id');
            $table->json('interaction_data')->nullable(); // Additional interaction data
            $table->timestamp('interacted_at')->nullable();
            
            $table->index(['user_id', 'interacted_at']);
            $table->index(['interaction_type', 'interacted_at']);
            $table->index(['target_type', 'target_id']);
            $table->unique(['user_id', 'interaction_type', 'target_type', 'target_id'], 'social_interactions_unique');
        });

        // User social settings table
        Schema::create('user_social_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('show_activity_feed')->default(true);
            $table->boolean('allow_followers')->default(true);
            $table->boolean('allow_sharing')->default(true);
            $table->boolean('allow_comments')->default(true);
            $table->boolean('allow_mentions')->default(true);
            $table->boolean('notify_follows')->default(true);
            $table->boolean('notify_shares')->default(true);
            $table->boolean('notify_comments')->default(true);
            $table->boolean('notify_mentions')->default(true);
            $table->json('privacy_settings')->nullable(); // Additional privacy settings
            $table->timestamp('updated_at')->nullable();
            
            $table->unique('user_id');
        });

        // Social analytics table
        Schema::create('social_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // follows_gained, shares_count, comments_count, etc.
            $table->string('target_type'); // user, story, episode, etc.
            $table->unsignedBigInteger('target_id');
            $table->date('metric_date');
            $table->integer('metric_value')->default(0);
            $table->json('metric_data')->nullable(); // Additional metric data
            $table->timestamp('calculated_at')->nullable();
            
            $table->unique(['metric_type', 'target_type', 'target_id', 'metric_date'], 'social_metrics_unique');
            $table->index(['metric_type', 'metric_date']);
            $table->index(['target_type', 'target_id']);
            $table->index(['metric_date', 'metric_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_analytics');
        Schema::dropIfExists('user_social_settings');
        Schema::dropIfExists('social_interactions');
        Schema::dropIfExists('user_mentions');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('user_comments');
        Schema::dropIfExists('playlist_items');
        Schema::dropIfExists('user_playlists');
        Schema::dropIfExists('user_activities');
        Schema::dropIfExists('content_shares');
        Schema::dropIfExists('user_follows');
    }
};