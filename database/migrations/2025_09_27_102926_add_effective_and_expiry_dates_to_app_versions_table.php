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
            $table->timestamp('effective_date')->nullable()->after('release_date');
            $table->timestamp('expiry_date')->nullable()->after('effective_date');
            
            // Add indexes for the new columns
            $table->index('effective_date');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_versions', function (Blueprint $table) {
            $table->dropIndex(['effective_date']);
            $table->dropIndex(['expiry_date']);
            $table->dropColumn(['effective_date', 'expiry_date']);
        });
    }
};