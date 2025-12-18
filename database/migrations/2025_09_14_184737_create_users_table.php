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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('phone_number', 20)->unique()->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('profile_image_url', 500)->nullable();
            $table->enum('role', ['parent', 'child', 'admin'])->default('parent');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('timezone', 50)->default('Asia/Tehran');
            $table->json('preferences')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('email');
            $table->index('phone_number');
            $table->index('parent_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
