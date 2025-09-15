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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100);
            $table->string('avatar_url', 500)->nullable();
            $table->integer('age');
            $table->unsignedBigInteger('favorite_category_id')->nullable();
            $table->json('preferences')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('favorite_category_id')->references('id')->on('categories')->onDelete('set null');
            $table->index('user_id');
            $table->index('age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
