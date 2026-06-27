<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('query', 200);
            $table->unsignedInteger('result_count')->default(0);
            $table->foreignId('clicked_story_id')->nullable()->constrained('stories')->nullOnDelete();
            $table->timestamp('searched_at');
            $table->timestamps();

            $table->index(['user_id', 'searched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};
