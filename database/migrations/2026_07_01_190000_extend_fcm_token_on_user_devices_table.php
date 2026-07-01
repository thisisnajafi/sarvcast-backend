<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_devices')) {
            return;
        }

        // The original table indexed fcm_token (varchar 500). MySQL cannot widen an
        // indexed utf8mb4 varchar beyond ~768 chars, so drop the index first.
        $indexes = collect(DB::select('SHOW INDEX FROM user_devices WHERE Column_name = ?', ['fcm_token']));
        foreach ($indexes->pluck('Key_name')->unique() as $indexName) {
            if ($indexName === 'PRIMARY') {
                continue;
            }
            DB::statement(sprintf('ALTER TABLE `user_devices` DROP INDEX `%s`', $indexName));
        }

        Schema::table('user_devices', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('user_devices')) {
            return;
        }

        Schema::table('user_devices', function (Blueprint $table) {
            $table->string('fcm_token', 500)->nullable()->change();
        });

        Schema::table('user_devices', function (Blueprint $table) {
            $table->index('fcm_token');
        });
    }
};
