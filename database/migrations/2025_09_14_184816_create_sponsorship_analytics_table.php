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
        Schema::create('sponsorship_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sponsored_content_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type'); // impression, click, conversion, view_complete, skip
            $table->timestamp('event_timestamp');
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('platform')->nullable(); // web, android, ios
            $table->json('metadata')->nullable(); // Additional event data
            $table->timestamps();
            
            $table->foreign('sponsored_content_id')->references('id')->on('sponsored_content')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['sponsored_content_id', 'event_type', 'event_timestamp'], 'sponsorship_analytics_content_event_idx');
            $table->index(['user_id', 'event_timestamp'], 'sponsorship_analytics_user_event_idx');
            $table->index('event_type', 'sponsorship_analytics_event_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorship_analytics');
    }
};
