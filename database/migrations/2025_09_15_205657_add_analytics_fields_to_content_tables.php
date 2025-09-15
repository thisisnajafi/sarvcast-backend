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
        // Add analytics fields to stories table
        Schema::table('stories', function (Blueprint $table) {
            $table->integer('total_plays')->default(0)->after('play_count');
            $table->integer('total_favorites')->default(0)->after('total_plays');
            $table->integer('total_ratings')->default(0)->after('total_favorites');
            $table->decimal('avg_rating', 3, 2)->default(0)->after('total_ratings');
            $table->integer('total_duration_played')->default(0)->after('avg_rating'); // Total duration played by users
            $table->integer('unique_listeners')->default(0)->after('total_duration_played'); // Unique users who played
            $table->integer('completion_count')->default(0)->after('unique_listeners'); // Times completed
            $table->decimal('completion_rate', 5, 2)->default(0)->after('completion_count'); // Completion percentage
            $table->integer('share_count')->default(0)->after('completion_rate'); // Times shared
            $table->integer('download_count')->default(0)->after('share_count'); // Times downloaded
            $table->timestamp('last_played_at')->nullable()->after('download_count'); // Last time played
            $table->timestamp('trending_since')->nullable()->after('last_played_at'); // When it started trending
            $table->json('analytics_data')->nullable()->after('trending_since'); // Additional analytics data
            
            $table->index('total_plays');
            $table->index('total_favorites');
            $table->index('avg_rating');
            $table->index('unique_listeners');
            $table->index('completion_rate');
            $table->index('last_played_at');
            $table->index('trending_since');
        });

        // Add analytics fields to episodes table
        Schema::table('episodes', function (Blueprint $table) {
            $table->integer('total_plays')->default(0)->after('play_count');
            $table->integer('total_favorites')->default(0)->after('total_plays');
            $table->integer('total_ratings')->default(0)->after('total_favorites');
            $table->decimal('avg_rating', 3, 2)->default(0)->after('total_ratings');
            $table->integer('total_duration_played')->default(0)->after('avg_rating');
            $table->integer('unique_listeners')->default(0)->after('total_duration_played');
            $table->integer('completion_count')->default(0)->after('unique_listeners');
            $table->decimal('completion_rate', 5, 2)->default(0)->after('completion_count');
            $table->integer('share_count')->default(0)->after('completion_rate');
            $table->integer('download_count')->default(0)->after('share_count');
            $table->timestamp('last_played_at')->nullable()->after('download_count');
            $table->timestamp('trending_since')->nullable()->after('last_played_at');
            $table->json('analytics_data')->nullable()->after('trending_since');
            
            $table->index('total_plays');
            $table->index('total_favorites');
            $table->index('avg_rating');
            $table->index('unique_listeners');
            $table->index('completion_rate');
            $table->index('last_played_at');
            $table->index('trending_since');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropIndex(['total_plays']);
            $table->dropIndex(['total_favorites']);
            $table->dropIndex(['avg_rating']);
            $table->dropIndex(['unique_listeners']);
            $table->dropIndex(['completion_rate']);
            $table->dropIndex(['last_played_at']);
            $table->dropIndex(['trending_since']);
            
            $table->dropColumn([
                'total_plays',
                'total_favorites',
                'total_ratings',
                'avg_rating',
                'total_duration_played',
                'unique_listeners',
                'completion_count',
                'completion_rate',
                'share_count',
                'download_count',
                'last_played_at',
                'trending_since',
                'analytics_data'
            ]);
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropIndex(['total_plays']);
            $table->dropIndex(['total_favorites']);
            $table->dropIndex(['avg_rating']);
            $table->dropIndex(['unique_listeners']);
            $table->dropIndex(['completion_rate']);
            $table->dropIndex(['last_played_at']);
            $table->dropIndex(['trending_since']);
            
            $table->dropColumn([
                'total_plays',
                'total_favorites',
                'total_ratings',
                'avg_rating',
                'total_duration_played',
                'unique_listeners',
                'completion_count',
                'completion_rate',
                'share_count',
                'download_count',
                'last_played_at',
                'trending_since',
                'analytics_data'
            ]);
        });
    }
};