<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            if (! Schema::hasColumn('stories', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (! Schema::hasColumn('stories', 'featured_order')) {
                $table->unsignedInteger('featured_order')->default(0)->after('is_featured');
            }
        });

        Schema::table('stories', function (Blueprint $table) {
            if (Schema::hasColumn('stories', 'is_featured') && Schema::hasColumn('stories', 'featured_order')) {
                $table->index(['is_featured', 'featured_order'], 'stories_featured_order_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            if (Schema::hasColumn('stories', 'featured_order')) {
                $table->dropIndex('stories_featured_order_index');
                $table->dropColumn('featured_order');
            }
            if (Schema::hasColumn('stories', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });
    }
};
