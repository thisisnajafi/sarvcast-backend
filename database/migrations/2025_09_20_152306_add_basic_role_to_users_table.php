<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic') DEFAULT 'basic'");
        }

        $parentIdsWithChildren = DB::table('users')
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id');

        $query = DB::table('users')->where('role', 'parent');

        if ($parentIdsWithChildren->isNotEmpty()) {
            $query->whereNotIn('id', $parentIdsWithChildren);
        }

        $query->update(['role' => 'basic']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')
            ->where('role', 'basic')
            ->update(['role' => 'parent']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin') DEFAULT 'parent'");
        }
    }
};