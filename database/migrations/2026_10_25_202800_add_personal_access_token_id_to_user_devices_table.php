<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_devices')) {
            return;
        }

        if (Schema::hasColumn('user_devices', 'personal_access_token_id')) {
            return;
        }

        Schema::table('user_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_devices')) {
            return;
        }

        if (! Schema::hasColumn('user_devices', 'personal_access_token_id')) {
            return;
        }

        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropIndex(['personal_access_token_id']);
            $table->dropColumn('personal_access_token_id');
        });
    }
};
