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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // NULL for broadcast notifications
            $table->string('title', 200);
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'success', 'error', 'promotional'])->default('info');
            $table->json('data')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('scheduled_at');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
