<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'background_photo_url')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('background_photo_url', 500)->nullable()->after('profile_image_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'background_photo_url')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('background_photo_url');
        });
    }
};
