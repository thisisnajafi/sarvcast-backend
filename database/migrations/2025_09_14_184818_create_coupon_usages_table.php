<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_code_id')->constrained('coupon_codes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->decimal('original_amount', 10, 2); // Original subscription amount
            $table->decimal('discount_amount', 10, 2); // Amount discounted
            $table->decimal('final_amount', 10, 2); // Final amount paid
            $table->decimal('commission_amount', 10, 2)->nullable(); // Commission amount for partner
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->timestamp('used_at')->useCurrent(); // When the coupon was used
            $table->json('metadata')->nullable(); // Additional usage data
            $table->timestamps();
            
            // Indexes
            $table->index(['coupon_code_id', 'user_id']);
            $table->index(['user_id', 'used_at']);
            $table->index(['status', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
