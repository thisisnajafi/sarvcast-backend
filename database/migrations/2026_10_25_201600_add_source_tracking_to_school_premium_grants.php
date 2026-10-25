<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_premium_grants', function (Blueprint $table) {
            if (! Schema::hasColumn('school_premium_grants', 'grant_source')) {
                $table->string('grant_source', 40)->default('school_panel')->after('granted_by');
            }
            if (! Schema::hasColumn('school_premium_grants', 'source_profile_id')) {
                $table->unsignedBigInteger('source_profile_id')->nullable()->after('grant_source');
                $table->index('source_profile_id');
            }
            if (! Schema::hasColumn('school_premium_grants', 'source_manager_user_id')) {
                $table->unsignedBigInteger('source_manager_user_id')->nullable()->after('source_profile_id');
                $table->index('source_manager_user_id');
            }
        });

        DB::table('school_premium_grants')
            ->whereNull('grant_source')
            ->update(['grant_source' => 'school_panel']);

        DB::table('school_premium_grants')
            ->whereNull('source_profile_id')
            ->update(['source_profile_id' => DB::raw('school_manager_profile_id')]);

        DB::table('school_premium_grants')
            ->whereNull('source_manager_user_id')
            ->update(['source_manager_user_id' => DB::raw('granted_by')]);
    }

    public function down(): void
    {
        // Non-destructive by design: keep source tracking data.
    }
};
