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
        Schema::create('affiliate_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('type', ['teacher', 'influencer', 'school', 'corporate']);
            $table->enum('tier', ['micro', 'mid', 'macro', 'enterprise'])->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(0.00); // Percentage
            $table->integer('follower_count')->nullable(); // For influencers
            $table->string('social_media_handle')->nullable();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->json('verification_documents')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'status']);
            $table->index(['tier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_partners');
    }
};
