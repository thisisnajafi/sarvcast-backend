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
            $table->unsignedBigInteger('narrator_id')->nullable()->after('story_id');
            $table->foreign('narrator_id')->references('id')->on('people')->onDelete('set null');
            $table->index('narrator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropForeign(['narrator_id']);
            $table->dropIndex(['narrator_id']);
            $table->dropColumn('narrator_id');
        });
    }
};