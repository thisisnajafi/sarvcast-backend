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
        // User recommendation preferences table
        Schema::create('user_recommendation_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('preference_type'); // category, director, narrator, etc.
            $table->string('preference_value'); // ID or value
            $table->decimal('weight', 5, 2)->default(1.00); // Preference weight
            $table->integer('interaction_count')->default(1); // Number of interactions
            $table->timestamp('last_updated');
            
            $table->unique(['user_id', 'preference_type', 'preference_value']);
            $table->index(['user_id', 'preference_type']);
            $table->index(['preference_type', 'preference_value']);
        });

        // Recommendation sessions table
        Schema::create('recommendation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_type'); // personalized, trending, similar, etc.
            $table->json('recommendations'); // Array of recommended story IDs
            $table->json('metadata')->nullable(); // Additional session data
            $table->timestamp('created_at');
            
            $table->index(['user_id', 'created_at']);
            $table->index(['session_type', 'created_at']);
        });

        // Recommendation interactions table
        Schema::create('recommendation_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->string('interaction_type'); // click, play, favorite, dismiss, etc.
            $table->string('recommendation_type'); // collaborative, content_based, etc.
            $table->foreignId('session_id')->nullable()->constrained('recommendation_sessions')->onDelete('set null');
            $table->json('context')->nullable(); // Additional context data
            $table->timestamp('interacted_at');
            
            $table->index(['user_id', 'interacted_at']);
            $table->index(['story_id', 'interacted_at']);
            $table->index(['interaction_type', 'interacted_at']);
            $table->index(['recommendation_type', 'interacted_at']);
        });

        // User similarity matrix table
        Schema::create('user_similarity_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('similar_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('similarity_score', 5, 4); // Similarity score (0-1)
            $table->integer('common_items'); // Number of common items
            $table->timestamp('calculated_at');
            
            $table->unique(['user_id', 'similar_user_id']);
            $table->index(['user_id', 'similarity_score']);
            $table->index(['similar_user_id', 'similarity_score']);
        });

        // Content similarity matrix table
        Schema::create('content_similarity_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->foreignId('similar_story_id')->constrained('stories')->onDelete('cascade');
            $table->decimal('similarity_score', 5, 4); // Similarity score (0-1)
            $table->string('similarity_type'); // category, director, narrator, content, etc.
            $table->timestamp('calculated_at');
            
            $table->unique(['story_id', 'similar_story_id']);
            $table->index(['story_id', 'similarity_score']);
            $table->index(['similar_story_id', 'similarity_score']);
            $table->index(['similarity_type', 'similarity_score']);
        });

        // Recommendation feedback table
        Schema::create('recommendation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->string('recommendation_type'); // Type of recommendation
            $table->string('feedback_type'); // positive, negative, neutral
            $table->text('feedback_text')->nullable(); // Optional feedback text
            $table->json('metadata')->nullable(); // Additional feedback data
            $table->timestamp('feedback_at');
            
            $table->index(['user_id', 'feedback_at']);
            $table->index(['story_id', 'feedback_at']);
            $table->index(['recommendation_type', 'feedback_type']);
        });

        // Recommendation performance metrics table
        Schema::create('recommendation_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // click_through_rate, conversion_rate, etc.
            $table->string('recommendation_type'); // collaborative, content_based, etc.
            $table->decimal('metric_value', 10, 4); // Metric value
            $table->date('metric_date'); // Date for the metric
            $table->json('metadata')->nullable(); // Additional metric data
            $table->timestamp('calculated_at');
            
            $table->unique(['metric_type', 'recommendation_type', 'metric_date']);
            $table->index(['metric_type', 'metric_date']);
            $table->index(['recommendation_type', 'metric_date']);
        });

        // User recommendation history table
        Schema::create('user_recommendation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->string('recommendation_type'); // Type of recommendation
            $table->decimal('recommendation_score', 8, 4); // Recommendation score
            $table->integer('position'); // Position in recommendation list
            $table->boolean('clicked')->default(false); // Whether user clicked
            $table->boolean('played')->default(false); // Whether user played
            $table->boolean('favorited')->default(false); // Whether user favorited
            $table->timestamp('recommended_at');
            
            $table->index(['user_id', 'recommended_at']);
            $table->index(['story_id', 'recommended_at']);
            $table->index(['recommendation_type', 'recommended_at']);
            $table->index(['clicked', 'recommended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_recommendation_history');
        Schema::dropIfExists('recommendation_metrics');
        Schema::dropIfExists('recommendation_feedback');
        Schema::dropIfExists('content_similarity_matrix');
        Schema::dropIfExists('user_similarity_matrix');
        Schema::dropIfExists('recommendation_interactions');
        Schema::dropIfExists('recommendation_sessions');
        Schema::dropIfExists('user_recommendation_preferences');
    }
};