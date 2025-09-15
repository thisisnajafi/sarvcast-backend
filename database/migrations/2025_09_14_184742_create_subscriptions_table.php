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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['monthly', 'quarterly', 'yearly', 'family']);
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending', 'trial'])->default('pending');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('IRR');
            $table->boolean('auto_renew')->default(true);
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('status');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
