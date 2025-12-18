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
        Schema::create('sponsored_content', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sponsorship_id');
            $table->unsignedBigInteger('story_id')->nullable();
            $table->unsignedBigInteger('episode_id')->nullable();
            $table->string('content_type'); // story, episode, banner, popup, notification
            $table->string('content_title');
            $table->text('content_description');
            $table->text('sponsor_message')->nullable();
            $table->string('brand_logo_url')->nullable();
            $table->string('brand_website_url')->nullable();
            $table->json('content_media')->nullable(); // Images, videos, etc.
            $table->enum('placement_type', ['pre_roll', 'mid_roll', 'post_roll', 'banner', 'popup', 'notification', 'dedicated_content']);
            $table->integer('display_duration')->nullable(); // seconds for video content
            $table->integer('display_frequency')->default(1); // How many times per user session
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'active', 'paused', 'completed', 'rejected'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('ctr', 5, 2)->default(0); // Click-through rate
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('sponsorship_id')->references('id')->on('corporate_sponsorships')->onDelete('cascade');
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
            $table->index(['status', 'start_date', 'end_date']);
            $table->index('content_type');
            $table->index('placement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsored_content');
    }
};
