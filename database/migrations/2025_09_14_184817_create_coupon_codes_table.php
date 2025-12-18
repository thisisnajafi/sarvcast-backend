<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Unique coupon code
            $table->string('name'); // Display name for the coupon
            $table->text('description')->nullable(); // Description of the coupon
            $table->enum('type', ['percentage', 'fixed_amount', 'free_trial'])->default('percentage'); // Discount type
            $table->decimal('discount_value', 10, 2); // Discount amount (percentage or fixed amount)
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Minimum purchase amount to use coupon
            $table->decimal('maximum_discount', 10, 2)->nullable(); // Maximum discount amount for percentage coupons
            $table->enum('partner_type', ['influencer', 'teacher', 'partner', 'promotional'])->default('promotional'); // Type of partner
            $table->foreignId('partner_id')->nullable()->constrained('affiliate_partners')->onDelete('set null'); // Associated partner
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Admin who created the coupon
            $table->integer('usage_limit')->nullable(); // Maximum number of times this coupon can be used
            $table->integer('usage_count')->default(0); // Number of times this coupon has been used
            $table->integer('user_limit')->nullable(); // Maximum number of times a single user can use this coupon
            $table->timestamp('starts_at')->nullable(); // When the coupon becomes active
            $table->timestamp('expires_at')->nullable(); // When the coupon expires
            $table->boolean('is_active')->default(true); // Whether the coupon is currently active
            $table->json('applicable_plans')->nullable(); // Which subscription plans this coupon applies to
            $table->json('metadata')->nullable(); // Additional metadata (campaign info, etc.)
            $table->timestamps();
            
            // Indexes
            $table->index(['code', 'is_active']);
            $table->index(['partner_type', 'partner_id']);
            $table->index(['starts_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_codes');
    }
};
