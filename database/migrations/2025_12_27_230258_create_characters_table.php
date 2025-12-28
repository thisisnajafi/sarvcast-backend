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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id');
            $table->string('name', 200);
            $table->string('image_url', 500)->nullable();
            $table->unsignedBigInteger('voice_actor_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('voice_actor_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('story_id');
            $table->index('voice_actor_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
