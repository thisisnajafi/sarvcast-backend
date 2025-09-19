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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id'); // User who made the referral
            $table->unsignedBigInteger('referred_id'); // User who was referred
            $table->string('referral_code', 20);
            $table->enum('status', ['pending', 'completed', 'expired', 'cancelled'])->default('pending');
            $table->integer('coins_awarded')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('referred_id'); // Each user can only be referred once
            $table->index(['referrer_id', 'status']);
            $table->index(['referral_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
