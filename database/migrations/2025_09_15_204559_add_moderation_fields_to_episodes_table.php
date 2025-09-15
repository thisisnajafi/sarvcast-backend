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
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('moderation_status', 20)->default('pending')->after('status'); // pending, approved, rejected, flagged
            $table->unsignedBigInteger('moderator_id')->nullable()->after('moderation_status'); // Moderator who reviewed
            $table->timestamp('moderated_at')->nullable()->after('moderator_id'); // When it was moderated
            $table->text('moderation_notes')->nullable()->after('moderated_at'); // Moderator notes
            $table->integer('moderation_rating')->nullable()->after('moderation_notes'); // Moderator rating 1-5
            $table->string('age_rating', 10)->nullable()->after('moderation_rating'); // G, PG, PG-13, R
            $table->json('content_warnings')->nullable()->after('age_rating'); // Content warnings array
            $table->string('rejection_code', 50)->nullable()->after('content_warnings'); // Reason for rejection
            $table->text('rejection_suggestions')->nullable()->after('rejection_code'); // Suggestions for improvement
            $table->boolean('allow_resubmission')->default(true)->after('rejection_suggestions'); // Can be resubmitted
            $table->string('moderation_priority', 10)->default('medium')->after('allow_resubmission'); // low, medium, high
            $table->string('flag_type', 50)->nullable()->after('moderation_priority'); // Type of flag
            $table->json('moderation_history')->nullable()->after('flag_type'); // History of moderation actions
            
            $table->foreign('moderator_id')->references('id')->on('users')->onDelete('set null');
            $table->index('moderation_status');
            $table->index('moderator_id');
            $table->index('moderated_at');
            $table->index('moderation_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropForeign(['moderator_id']);
            $table->dropIndex(['moderation_status']);
            $table->dropIndex(['moderator_id']);
            $table->dropIndex(['moderated_at']);
            $table->dropIndex(['moderation_priority']);
            
            $table->dropColumn([
                'moderation_status',
                'moderator_id',
                'moderated_at',
                'moderation_notes',
                'moderation_rating',
                'age_rating',
                'content_warnings',
                'rejection_code',
                'rejection_suggestions',
                'allow_resubmission',
                'moderation_priority',
                'flag_type',
                'moderation_history'
            ]);
        });
    }
};