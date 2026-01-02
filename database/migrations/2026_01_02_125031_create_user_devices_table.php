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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_id', 100);
            $table->string('device_type', 50); // android, ios
            $table->string('device_model', 100)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->string('fcm_token', 500)->nullable();
            $table->timestamp('last_active')->nullable();
            $table->timestamps();
            
            // Unique constraint: one device_id per user
            $table->unique(['user_id', 'device_id'], 'unique_user_device');
            
            // Index for faster FCM token lookups
            $table->index('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
