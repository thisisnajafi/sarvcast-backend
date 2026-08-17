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

        DB::statement("ALTER TABLE episodes MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'published', 'archived') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE episodes SET status = 'draft' WHERE status = 'archived'");
        DB::statement("ALTER TABLE episodes MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'published') NOT NULL DEFAULT 'draft'");
    }
};
