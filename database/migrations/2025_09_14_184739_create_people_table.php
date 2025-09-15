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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('bio')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->json('roles'); // array of roles: voice_actor, director, writer, producer
            $table->integer('total_stories')->default(0);
            $table->integer('total_episodes')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            
            $table->index('name');
            $table->index('is_verified');
            $table->fullText(['name', 'bio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
