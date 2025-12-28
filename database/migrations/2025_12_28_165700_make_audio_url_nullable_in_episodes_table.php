<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes audio_url nullable in episodes table to allow creating episodes without audio files.
     */
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('audio_url', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('audio_url', 500)->nullable(false)->change();
        });
    }
};

