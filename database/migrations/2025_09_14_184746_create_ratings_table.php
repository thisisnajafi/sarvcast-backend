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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('story_id')->nullable();
            $table->unsignedBigInteger('episode_id')->nullable();
            $table->tinyInteger('rating'); // 1-5 stars
            $table->text('review')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
            $table->unique(['user_id', 'story_id']);
            $table->unique(['user_id', 'episode_id']);
            $table->index('user_id');
            $table->index('story_id');
            $table->index('episode_id');
            $table->check('rating >= 1 AND rating <= 5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
