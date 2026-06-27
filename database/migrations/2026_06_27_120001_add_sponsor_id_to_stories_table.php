<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sponsors') || ! Schema::hasTable('stories')) {
            return;
        }

        if (Schema::hasColumn('stories', 'sponsor_id')) {
            return;
        }

        Schema::table('stories', function (Blueprint $table) {
            $table->uuid('sponsor_id')->nullable()->after('category_id');
            $table->foreign('sponsor_id')->references('id')->on('sponsors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stories') || ! Schema::hasColumn('stories', 'sponsor_id')) {
            return;
        }

        Schema::table('stories', function (Blueprint $table) {
            $table->dropForeign(['sponsor_id']);
            $table->dropColumn('sponsor_id');
        });
    }
};
