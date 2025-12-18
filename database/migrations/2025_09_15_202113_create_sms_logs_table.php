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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 15);
            $table->text('message');
            $table->string('template_key')->nullable();
            $table->json('variables')->nullable();
            $table->string('provider', 50);
            $table->string('status', 20)->default('pending'); // pending, sent, failed, delivered
            $table->string('message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();
            
            $table->index('phone_number');
            $table->index('provider');
            $table->index('status');
            $table->index('sent_at');
            $table->index('template_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
