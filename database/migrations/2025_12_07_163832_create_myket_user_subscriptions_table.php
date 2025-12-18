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
        Schema::create('myket_user_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_id');
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending', 'trial'])->default('pending');
            $table->timestamps();
            
            $table->primary(['user_id', 'subscription_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('myket_subscriptions')->onDelete('cascade');
            $table->index('user_id');
            $table->index('subscription_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('myket_user_subscriptions');
    }
};
