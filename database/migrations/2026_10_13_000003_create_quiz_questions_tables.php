<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_questions')) {
            Schema::create('quiz_questions', function (Blueprint $table) {
                $table->id();
                $table->text('question_text');
                $table->string('question_type', 50);
                $table->string('difficulty_level', 20)->default('easy');
                $table->unsignedInteger('points')->default(1);
                $table->unsignedInteger('time_limit')->nullable();
                $table->text('explanation')->nullable();
                $table->foreignId('story_id')->nullable()->constrained('stories')->nullOnDelete();
                $table->foreignId('episode_id')->nullable()->constrained('episodes')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->string('correct_answer')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quiz_question_options')) {
            Schema::create('quiz_question_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_question_id')->constrained('quiz_questions')->cascadeOnDelete();
                $table->string('text');
                $table->boolean('is_correct')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_question_options');
        Schema::dropIfExists('quiz_questions');
    }
};
