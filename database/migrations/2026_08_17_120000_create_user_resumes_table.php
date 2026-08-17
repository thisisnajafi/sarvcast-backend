<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_resumes')) {
            return;
        }

        Schema::create('user_resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('headline', 120)->nullable();
            $table->unsignedTinyInteger('years_of_experience')->nullable();
            $table->text('about')->nullable();
            $table->json('specialties')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
            $table->json('awards')->nullable();
            $table->json('languages')->nullable();
            $table->string('demo_url', 500)->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('show_in_talent_directory')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_public');
            $table->index(['is_public', 'show_in_talent_directory']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_resumes');
    }
};
