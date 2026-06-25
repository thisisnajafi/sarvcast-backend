<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE stories MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'published', 'archived') NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('stories')->where('status', 'archived')->update(['status' => 'draft']);

        DB::statement(
            "ALTER TABLE stories MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'published') NOT NULL DEFAULT 'draft'"
        );
    }
};
