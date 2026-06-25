<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('disk', 32)->default('public');
            $table->string('path', 500);
            $table->string('url', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('folder', 100)->default('general');
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['folder', 'status', 'created_at']);
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
