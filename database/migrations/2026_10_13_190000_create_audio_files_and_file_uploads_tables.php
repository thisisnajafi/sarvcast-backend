<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audio_files')) {
            Schema::create('audio_files', function (Blueprint $table) {
                $table->id();
                $table->string('original_name');
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('file_type', 32)->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('tags')->nullable();
                $table->string('quality', 32)->nullable();
                $table->string('status', 32)->default('pending');
                $table->boolean('is_public')->default(true);
                $table->foreignId('episode_id')->nullable()->constrained('episodes')->nullOnDelete();
                $table->foreignId('story_id')->nullable()->constrained('stories')->nullOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index('file_type');
            });
        }

        if (! Schema::hasTable('file_uploads')) {
            Schema::create('file_uploads', function (Blueprint $table) {
                $table->id();
                $table->string('original_name');
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('mime_type', 128)->nullable();
                $table->string('file_type', 32)->nullable();
                $table->string('category', 64)->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('tags')->nullable();
                $table->string('status', 32)->default('active');
                $table->boolean('is_public')->default(true);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index(['file_type', 'category']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
        Schema::dropIfExists('audio_files');
    }
};
