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
        Schema::create('influencer_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_partner_id');
            $table->string('campaign_name');
            $table->text('campaign_description');
            $table->enum('campaign_type', ['story_review', 'educational_content', 'cultural_preservation', 'brand_partnership']);
            $table->enum('content_type', ['post', 'story', 'reel', 'video', 'live']);
            $table->integer('required_posts')->default(1);
            $table->integer('required_stories')->default(0);
            $table->decimal('compensation_per_post', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'cancelled'])->default('draft');
            $table->json('content_guidelines')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('target_audience')->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->timestamps();
            
            $table->foreign('affiliate_partner_id')->references('id')->on('affiliate_partners')->onDelete('cascade');
            $table->index(['status', 'start_date', 'end_date']);
            $table->index('campaign_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('influencer_campaigns');
    }
};
