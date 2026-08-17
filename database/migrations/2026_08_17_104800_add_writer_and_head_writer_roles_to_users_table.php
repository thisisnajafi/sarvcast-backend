<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive staff roles: writer, head_writer.
     * MySQL keeps the ENUM; SQLite cannot ALTER ENUM so the column becomes a string.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 32)->default('basic')->change();
            });

            return;
        }

        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic', 'super_admin', 'voice_actor', 'writer', 'head_writer') DEFAULT 'basic'");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        DB::table('users')
            ->whereIn('role', ['writer', 'head_writer'])
            ->update(['role' => 'basic']);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic', 'super_admin', 'voice_actor') DEFAULT 'basic'");
    }
};
