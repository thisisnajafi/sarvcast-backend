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
        Schema::create('episode_voice_actors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained()->onDelete('cascade');
            $table->string('role', 100)->comment('narrator, character, etc.');
            $table->string('character_name', 255)->nullable()->comment('Name of the character being voiced');
            $table->unsignedInteger('start_time')->comment('Start time in seconds');
            $table->unsignedInteger('end_time')->comment('End time in seconds');
            $table->text('voice_description')->nullable()->comment('Description of voice characteristics');
            $table->boolean('is_primary')->default(false)->comment('Primary voice actor for the episode');
            $table->timestamps();

            // Indexes for performance
            $table->index(['episode_id']);
            $table->index(['person_id']);
            $table->index(['start_time', 'end_time']);
            $table->index(['role']);
            $table->index(['is_primary']);
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['episode_id', 'person_id', 'role', 'start_time'], 'unique_episode_person_role_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episode_voice_actors');
    }
};
