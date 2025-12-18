<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_partner_id')->constrained('affiliate_partners')->onDelete('cascade');
            $table->foreignId('coupon_usage_id')->nullable()->constrained('coupon_usages')->onDelete('set null');
            $table->decimal('amount', 10, 2); // Commission amount
            $table->string('currency', 3)->default('IRR'); // Currency code
            $table->enum('payment_type', ['coupon_commission', 'referral_commission', 'manual'])->default('coupon_commission');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['bank_transfer', 'digital_wallet', 'manual'])->default('bank_transfer');
            $table->string('payment_reference')->nullable(); // External payment reference
            $table->text('payment_details')->nullable(); // Bank account details, etc.
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamp('processed_at')->nullable(); // When payment was processed
            $table->timestamp('paid_at')->nullable(); // When payment was completed
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who processed the payment
            $table->json('metadata')->nullable(); // Additional payment data
            $table->timestamps();
            
            // Indexes
            $table->index(['affiliate_partner_id', 'status']);
            $table->index(['status', 'processed_at']);
            $table->index(['payment_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payments');
    }
};
