<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
        DB::table('users')
            ->where('status', User::STATUS_PROFILE_COMPLETION_NEEDED)
            ->update(['status' => User::STATUS_ACTIVE]);
    }
};
