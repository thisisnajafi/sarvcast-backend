<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_partnerships', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliate_partner_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('school_partnerships', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliate_partner_id')->nullable(false)->change();
        });
    }
};
