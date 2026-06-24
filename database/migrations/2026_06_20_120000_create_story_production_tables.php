<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_production_files', function (Blueprint $table) {
            $table->id();
            $table->string('story_slug', 191)->index();
            $table->string('episode_slug', 191)->nullable()->index();
            $table->enum('file_type', [
                'characters_and_objects',
                'image_prompts',
                'story_script',
            ]);
            $table->string('original_filename', 500);
            $table->string('storage_path', 500);
            $table->string('source_path', 500)->nullable();
            $table->unsignedBigInteger('story_id')->nullable()->index();
            $table->unsignedBigInteger('episode_id')->nullable()->index();
            $table->unsignedInteger('episode_number')->nullable();
            $table->json('parsed_summary')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['story_slug', 'episode_slug', 'file_type'], 'story_prod_files_unique');
            $table->foreign('story_id')->references('id')->on('stories')->nullOnDelete();
            $table->foreign('episode_id')->references('id')->on('episodes')->nullOnDelete();
        });

        Schema::create('story_production_assets', function (Blueprint $table) {
            $table->id();
            $table->string('story_slug', 191)->index();
            $table->string('episode_slug', 191)->nullable()->index();
            $table->enum('asset_type', ['character', 'object', 'setting', 'scene', 'cover']);
            $table->string('asset_key', 191);
            $table->string('name_persian', 500)->nullable();
            $table->string('name_english', 500)->nullable();
            $table->text('prompt')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->unsignedBigInteger('story_id')->nullable()->index();
            $table->unsignedBigInteger('episode_id')->nullable()->index();
            $table->unsignedBigInteger('character_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['story_slug', 'episode_slug', 'asset_type', 'asset_key'],
                'story_prod_assets_unique'
            );
            $table->foreign('story_id')->references('id')->on('stories')->nullOnDelete();
            $table->foreign('episode_id')->references('id')->on('episodes')->nullOnDelete();
            $table->foreign('character_id')->references('id')->on('characters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_production_assets');
        Schema::dropIfExists('story_production_files');
    }
};
