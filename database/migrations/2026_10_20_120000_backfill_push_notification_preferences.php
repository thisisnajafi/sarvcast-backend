<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data migration: set push_notifications_enabled=true when missing in users.preferences.
 * Prefer running `php artisan users:backfill-push-notification-preferences --dry-run` first.
 * Safe to re-run; skips users that already have the key.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'preferences')) {
            return;
        }

        $chunkSize = 200;

        DB::table('users')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($users) {
                foreach ($users as $user) {
                    $preferences = [];
                    if ($user->preferences !== null && $user->preferences !== '') {
                        $decoded = is_string($user->preferences)
                            ? json_decode($user->preferences, true)
                            : $user->preferences;
                        $preferences = is_array($decoded) ? $decoded : [];
                    }

                    if (array_key_exists('push_notifications_enabled', $preferences)) {
                        continue;
                    }

                    $preferences['push_notifications_enabled'] = true;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'preferences' => json_encode($preferences),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally empty: do not remove user preference keys on rollback.
    }
};
