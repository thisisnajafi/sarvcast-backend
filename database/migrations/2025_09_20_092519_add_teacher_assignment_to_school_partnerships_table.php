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
        Schema::table('school_partnerships', function (Blueprint $table) {
            // Add teacher assignment fields
            $table->unsignedBigInteger('assigned_teacher_id')->nullable()->after('affiliate_partner_id');
            $table->timestamp('teacher_assigned_at')->nullable()->after('assigned_teacher_id');
            $table->text('teacher_assignment_notes')->nullable()->after('teacher_assigned_at');
            
            // Add foreign key constraint
            $table->foreign('assigned_teacher_id')->references('id')->on('teacher_accounts')->onDelete('set null');
            
            // Add index
            $table->index('assigned_teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_partnerships', function (Blueprint $table) {
            $table->dropForeign(['assigned_teacher_id']);
            $table->dropIndex(['assigned_teacher_id']);
            
            $table->dropColumn([
                'assigned_teacher_id',
                'teacher_assigned_at',
                'teacher_assignment_notes'
            ]);
        });
    }
};