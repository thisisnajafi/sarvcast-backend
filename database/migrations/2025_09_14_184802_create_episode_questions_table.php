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
        Schema::create('episode_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('episode_id');
            $table->text('question');
            $table->string('option_a', 255);
            $table->string('option_b', 255);
            $table->string('option_c', 255);
            $table->string('option_d', 255);
            $table->enum('correct_answer', ['a', 'b', 'c', 'd']);
            $table->text('explanation')->nullable();
            $table->integer('coins_reward')->default(5);
            $table->integer('difficulty_level')->default(1); // 1-5 scale
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
            $table->index(['episode_id', 'is_active']);
            $table->index('difficulty_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episode_questions');
    }
};
