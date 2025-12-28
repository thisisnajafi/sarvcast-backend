<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds script_file_url to episodes table for storing markdown script files.
     */
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('script_file_url', 500)->nullable()->after('audio_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('script_file_url');
        });
    }
};
