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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('audio_url', 500);
            $table->string('local_audio_path', 500)->nullable();
            $table->integer('duration'); // in minutes
            $table->integer('episode_number');
            $table->boolean('is_premium')->default(false);
            $table->json('image_urls')->nullable(); // array of image URLs
            $table->integer('play_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->json('tags')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->index('story_id');
            $table->index('episode_number');
            $table->index('status');
            $table->index('is_premium');
            $table->unique(['story_id', 'episode_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
