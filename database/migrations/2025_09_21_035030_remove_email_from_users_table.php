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
        Schema::table('users', function (Blueprint $table) {
            // Drop email-related columns and indexes
            $table->dropIndex(['email']);
            $table->dropColumn(['email', 'email_verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Re-add email columns
            $table->string('email')->nullable()->after('id');
            $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
            $table->index('email');
        });
    }
};