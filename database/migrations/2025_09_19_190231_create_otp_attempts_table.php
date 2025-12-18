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
        Schema::create('otp_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number'); // شماره تلفن
            $table->string('code', 6); // کد ۶ رقمی
            $table->string('purpose')->default('verification'); // هدف: login, admin_2fa, verification
            $table->boolean('verified')->default(false); // آیا تایید شده
            $table->timestamp('expires_at'); // زمان انقضا
            $table->timestamps();
            
            $table->index(['phone_number', 'purpose']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_attempts');
    }
};
