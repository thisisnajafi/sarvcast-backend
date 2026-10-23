<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_manager_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('school_manager_profiles', 'premium_grant_expires_at')) {
                $table->timestamp('premium_grant_expires_at')->nullable()->after('member_limit');
            }
            if (! Schema::hasColumn('school_manager_profiles', 'allowed_grant_plan_ids')) {
                $table->json('allowed_grant_plan_ids')->nullable()->after('premium_grant_expires_at');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep the policy columns (and any data in them) on rollback.
    }
};
