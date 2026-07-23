<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $hasEmail = Schema::hasColumn('users', 'email');
        $hasVerified = Schema::hasColumn('users', 'email_verified_at');
        if (! $hasEmail && ! $hasVerified) {
            return;
        }

        // SQLite rebuilds the table on dropColumn; leftover unique/index names on
        // email break when both unique(email) and index(email) were created.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_email_unique');
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_email_index');

            Schema::table('users', function (Blueprint $table) use ($hasEmail, $hasVerified) {
                $columns = array_values(array_filter([
                    $hasEmail ? 'email' : null,
                    $hasVerified ? 'email_verified_at' : null,
                ]));
                $table->dropColumn($columns);
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) use ($hasEmail, $hasVerified) {
            if ($hasEmail) {
                try {
                    $table->dropUnique(['email']);
                } catch (\Throwable) {
                    // Index may already be absent on some environments.
                }
                try {
                    $table->dropIndex(['email']);
                } catch (\Throwable) {
                    // Index may already be absent on some environments.
                }
            }

            $columns = array_values(array_filter([
                $hasEmail ? 'email' : null,
                $hasVerified ? 'email_verified_at' : null,
            ]));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->after('id');
            }
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
            }
            $table->index('email');
        });
    }
};
