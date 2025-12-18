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
            $table->unsignedBigInteger('user_id');
            $table->string('type', 50); // notification type (info, success, warning, error, subscription, payment, content)
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable(); // additional data (links, images, etc.)
            $table->string('action_type', 50)->nullable(); // action type (link, button, dismiss)
            $table->string('action_url')->nullable(); // action URL or route
            $table->string('action_text', 100)->nullable(); // action button text
            $table->boolean('is_read')->default(false);
            $table->boolean('is_important')->default(false); // important notifications stay longer
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // auto-expire notifications
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->string('category', 50)->nullable(); // subscription, payment, content, system
            $table->json('metadata')->nullable(); // additional metadata
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('is_important');
            $table->index('priority');
            $table->index('category');
            $table->index('expires_at');
            $table->index('created_at');
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
