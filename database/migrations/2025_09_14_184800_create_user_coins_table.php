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
        Schema::create('user_coins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('total_coins')->default(0);
            $table->integer('available_coins')->default(0);
            $table->integer('earned_coins')->default(0);
            $table->integer('spent_coins')->default(0);
            $table->timestamp('last_earned_at')->nullable();
            $table->timestamp('last_spent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
            $table->index(['user_id', 'available_coins']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_coins');
    }
};
