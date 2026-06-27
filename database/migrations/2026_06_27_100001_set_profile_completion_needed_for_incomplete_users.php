<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'onboarding_completed')) {
            return;
        }

        $this->ensureProfileCompletionStatusEnum();

        DB::table('users')
            ->where('status', User::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->where('onboarding_completed', false)
                    ->orWhereNull('onboarding_completed');
            })
            ->update(['status' => User::STATUS_PROFILE_COMPLETION_NEEDED]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'onboarding_completed')) {
            return;
        }

        DB::table('users')
            ->where('status', User::STATUS_PROFILE_COMPLETION_NEEDED)
            ->update(['status' => User::STATUS_ACTIVE]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE `users` MODIFY COLUMN `status` ENUM(
                    'active',
                    'inactive',
                    'suspended',
                    'pending'
                ) NOT NULL DEFAULT 'pending'"
            );
        }
    }

    private function ensureProfileCompletionStatusEnum(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `users` MODIFY COLUMN `status` ENUM(
                'active',
                'inactive',
                'suspended',
                'pending',
                'profile_completion_needed'
            ) NOT NULL DEFAULT 'pending'"
        );
    }
};
