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
        Schema::table('app_versions', function (Blueprint $table) {
            // Flavor-specific update links
            if (!Schema::hasColumn('app_versions', 'website_update_url')) {
                $table->string('website_update_url')->nullable()->after('download_url');
            }
            if (!Schema::hasColumn('app_versions', 'cafebazaar_update_url')) {
                $table->string('cafebazaar_update_url')->nullable()->after('website_update_url');
            }
            if (!Schema::hasColumn('app_versions', 'myket_update_url')) {
                $table->string('myket_update_url')->nullable()->after('cafebazaar_update_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_versions', function (Blueprint $table) {
            if (Schema::hasColumn('app_versions', 'website_update_url')) {
                $table->dropColumn('website_update_url');
            }
            if (Schema::hasColumn('app_versions', 'cafebazaar_update_url')) {
                $table->dropColumn('cafebazaar_update_url');
            }
            if (Schema::hasColumn('app_versions', 'myket_update_url')) {
                $table->dropColumn('myket_update_url');
            }
        });
    }
};
