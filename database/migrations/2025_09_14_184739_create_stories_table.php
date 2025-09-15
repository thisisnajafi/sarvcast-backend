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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('subtitle', 300)->nullable();
            $table->text('description');
            $table->string('image_url', 500);
            $table->string('cover_image_url', 500)->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('director_id')->nullable();
            $table->unsignedBigInteger('writer_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('narrator_id')->nullable();
            $table->string('age_group', 20);
            $table->string('language', 10)->default('fa');
            $table->integer('duration'); // total duration in minutes
            $table->integer('total_episodes')->default(0);
            $table->integer('free_episodes')->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_completely_free')->default(false);
            $table->integer('play_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->json('tags')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('director_id')->references('id')->on('people')->onDelete('set null');
            $table->foreign('writer_id')->references('id')->on('people')->onDelete('set null');
            $table->foreign('author_id')->references('id')->on('people')->onDelete('set null');
            $table->foreign('narrator_id')->references('id')->on('people')->onDelete('set null');
            $table->index('category_id');
            $table->index('status');
            $table->index('is_premium');
            $table->index('age_group');
            $table->fullText(['title', 'subtitle', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
