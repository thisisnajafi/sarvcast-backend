<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stories') || ! Schema::hasColumn('stories', 'writer_id')) {
            return;
        }

        Schema::table('stories', function (Blueprint $table) {
            $table->dropForeign(['writer_id']);
            $table->dropColumn('writer_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stories') || Schema::hasColumn('stories', 'writer_id')) {
            return;
        }

        Schema::table('stories', function (Blueprint $table) {
            $table->unsignedBigInteger('writer_id')->nullable()->after('director_id');
            $table->foreign('writer_id')->references('id')->on('people')->onDelete('set null');
        });
    }
};
