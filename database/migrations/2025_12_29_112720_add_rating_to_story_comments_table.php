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
        Schema::table('story_comments', function (Blueprint $table) {
            $table->tinyInteger('rating')->nullable()->after('content');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('story_comments', function (Blueprint $table) {
            $table->dropIndex(['rating']);
            $table->dropColumn('rating');
        });
    }
};
