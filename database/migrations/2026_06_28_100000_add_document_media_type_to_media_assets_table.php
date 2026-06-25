<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE media_assets MODIFY media_type ENUM('image', 'audio', 'document') NOT NULL DEFAULT 'image'");
        } else {
            Schema::table('media_assets', function (Blueprint $table) {
                $table->string('media_type', 20)->default('image')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('media_assets')->where('media_type', 'document')->update(['media_type' => 'image']);
            DB::statement("ALTER TABLE media_assets MODIFY media_type ENUM('image', 'audio') NOT NULL DEFAULT 'image'");
        }
    }
};
