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
        Schema::create('influencer_content', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('affiliate_partner_id');
            $table->string('content_type'); // post, story, reel, video, live
            $table->string('platform'); // instagram, telegram, youtube, etc.
            $table->string('content_url')->nullable();
            $table->text('content_text')->nullable();
            $table->json('media_urls')->nullable();
            $table->json('hashtags')->nullable();
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'published'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('campaign_id')->references('id')->on('influencer_campaigns')->onDelete('cascade');
            $table->foreign('affiliate_partner_id')->references('id')->on('affiliate_partners')->onDelete('cascade');
            $table->index(['campaign_id', 'status']);
            $table->index(['affiliate_partner_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('influencer_content');
    }
};
