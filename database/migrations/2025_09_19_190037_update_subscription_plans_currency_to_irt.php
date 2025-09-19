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
        // Update existing subscription plans currency from IRR to IRT
        DB::table('subscription_plans')->update(['currency' => 'IRT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert currency back to IRR
        DB::table('subscription_plans')->update(['currency' => 'IRR']);
    }
};
