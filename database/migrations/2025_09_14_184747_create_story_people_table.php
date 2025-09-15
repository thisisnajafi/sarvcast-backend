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
        Schema::create('story_people', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id');
            $table->unsignedBigInteger('person_id');
            $table->enum('role', ['voice_actor', 'director', 'writer', 'producer']);
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->unique(['story_id', 'person_id', 'role']);
            $table->index('story_id');
            $table->index('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_people');
    }
};
