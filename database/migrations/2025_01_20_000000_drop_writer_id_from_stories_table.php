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
        Schema::table('stories', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['writer_id']);
            // Drop the column
            $table->dropColumn('writer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->unsignedBigInteger('writer_id')->nullable()->after('director_id');
            $table->foreign('writer_id')->references('id')->on('people')->onDelete('set null');
        });
    }
};

