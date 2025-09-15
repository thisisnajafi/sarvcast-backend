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
        // Achievements table
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('badge_color')->default('#3B82F6');
            $table->string('category'); // listening, social, content, streak, etc.
            $table->string('type'); // single, cumulative, streak, special
            $table->json('criteria'); // Achievement criteria (e.g., {"action": "play", "count": 100})
            $table->integer('points')->default(0); // Points awarded for achievement
            $table->boolean('is_active')->default(true);
            $table->boolean('is_hidden')->default(false); // Hidden until unlocked
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Additional achievement data
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index(['points', 'is_active']);
        });

        // User achievements table
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
            $table->timestamp('unlocked_at');
            $table->json('progress_data')->nullable(); // Progress data when unlocked
            $table->boolean('is_notified')->default(false); // Whether user was notified
            $table->json('metadata')->nullable(); // Additional unlock data
            
            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'unlocked_at']);
            $table->index(['achievement_id', 'unlocked_at']);
        });

        // User points table
        Schema::create('user_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_points')->default(0);
            $table->integer('available_points')->default(0); // Points available for spending
            $table->integer('spent_points')->default(0);
            $table->integer('level')->default(1);
            $table->integer('experience')->default(0); // XP for leveling
            $table->json('level_progress')->nullable(); // Progress to next level
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index(['total_points', 'level']);
            $table->index(['level', 'experience']);
        });

        // Point transactions table
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type'); // earned, spent, bonus, penalty
            $table->integer('points'); // Positive for earned, negative for spent
            $table->string('source_type')->nullable(); // achievement, activity, purchase, etc.
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description');
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->timestamp('transacted_at');
            
            $table->index(['user_id', 'transacted_at']);
            $table->index(['transaction_type', 'transacted_at']);
            $table->index(['source_type', 'source_id']);
        });

        // Leaderboards table
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // points, listening_time, achievements, etc.
            $table->string('period'); // daily, weekly, monthly, all_time
            $table->string('scope'); // global, category, friends
            $table->json('criteria')->nullable(); // Leaderboard criteria
            $table->boolean('is_active')->default(true);
            $table->integer('max_entries')->default(100);
            $table->json('metadata')->nullable(); // Additional leaderboard data
            $table->timestamps();
            
            $table->index(['type', 'period', 'is_active']);
            $table->index(['scope', 'is_active']);
        });

        // Leaderboard entries table
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leaderboard_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('rank');
            $table->decimal('score', 15, 2); // Score for ranking
            $table->json('score_data')->nullable(); // Detailed score breakdown
            $table->date('period_date'); // Date for the period
            $table->timestamp('updated_at');
            
            $table->unique(['leaderboard_id', 'user_id', 'period_date']);
            $table->index(['leaderboard_id', 'rank', 'period_date']);
            $table->index(['user_id', 'period_date']);
            $table->index(['score', 'period_date']);
        });

        // Streaks table
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('streak_type'); // listening, login, daily_goal, etc.
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_activity_date');
            $table->date('streak_start_date')->nullable();
            $table->json('streak_data')->nullable(); // Additional streak data
            $table->timestamps();
            
            $table->unique(['user_id', 'streak_type']);
            $table->index(['streak_type', 'current_streak']);
            $table->index(['streak_type', 'longest_streak']);
        });

        // Challenges table
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('type'); // daily, weekly, monthly, special
            $table->string('category'); // listening, social, content, etc.
            $table->json('objectives'); // Challenge objectives
            $table->json('rewards'); // Rewards for completion
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_repeatable')->default(false);
            $table->integer('max_participants')->nullable();
            $table->json('metadata')->nullable(); // Additional challenge data
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['category', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });

        // User challenge participation table
        Schema::create('user_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('challenge_id')->constrained()->onDelete('cascade');
            $table->string('status'); // active, completed, failed, expired
            $table->json('progress'); // Progress towards objectives
            $table->json('completed_objectives')->nullable(); // Completed objectives
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('rewards_claimed')->nullable(); // Rewards claimed
            $table->json('metadata')->nullable(); // Additional participation data
            
            $table->unique(['user_id', 'challenge_id']);
            $table->index(['user_id', 'status']);
            $table->index(['challenge_id', 'status']);
            $table->index(['status', 'started_at']);
        });

        // Badges table
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon');
            $table->string('color')->default('#3B82F6');
            $table->string('category'); // listening, social, achievement, special
            $table->string('rarity'); // common, uncommon, rare, epic, legendary
            $table->json('requirements')->nullable(); // Badge requirements
            $table->boolean('is_active')->default(true);
            $table->boolean('is_hidden')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Additional badge data
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['rarity', 'is_active']);
        });

        // User badges table
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('badge_id')->constrained()->onDelete('cascade');
            $table->timestamp('earned_at');
            $table->boolean('is_displayed')->default(true); // Whether to display on profile
            $table->json('metadata')->nullable(); // Additional badge data
            
            $table->unique(['user_id', 'badge_id']);
            $table->index(['user_id', 'earned_at']);
            $table->index(['badge_id', 'earned_at']);
        });

        // Gamification settings table
        Schema::create('gamification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->text('setting_value');
            $table->string('setting_type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Gamification analytics table
        Schema::create('gamification_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // achievements_unlocked, points_earned, etc.
            $table->string('target_type')->nullable(); // user, achievement, challenge, etc.
            $table->unsignedBigInteger('target_id')->nullable();
            $table->date('metric_date');
            $table->integer('metric_value')->default(0);
            $table->json('metric_data')->nullable(); // Additional metric data
            $table->timestamp('calculated_at');
            
            $table->unique(['metric_type', 'target_type', 'target_id', 'metric_date']);
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
        Schema::dropIfExists('gamification_analytics');
        Schema::dropIfExists('gamification_settings');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('user_challenges');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('user_streaks');
        Schema::dropIfExists('leaderboard_entries');
        Schema::dropIfExists('leaderboards');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('user_points');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};