<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('meta_description', 180)->nullable();
            $table->string('focus_keyword', 80)->nullable();
            $table->string('og_image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('meta_description', 180)->nullable();
            $table->string('focus_keyword', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 191)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('meta_description', 180)->nullable();
            $table->string('focus_keyword', 80)->nullable();
            $table->json('secondary_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_title', 110)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->string('og_image_url', 500)->nullable();
            $table->string('twitter_card', 32)->default('summary_large_image');
            $table->boolean('noindex')->default(false);
            $table->string('schema_type', 32)->default('Article');
            $table->json('faqs')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('reading_time_minutes')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->unique(['blog_post_id', 'blog_tag_id']);
        });

        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source', 500);
            $table->string('destination', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique('source');
        });

        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        DB::table('seo_settings')->insert([
            'key' => 'robots_txt',
            'value' => implode("\n", [
                'User-agent: *',
                'Allow: /',
                'Disallow: /404',
                'Disallow: /assets/fonts/',
                '',
                'User-agent: GPTBot',
                'Allow: /',
                '',
                'User-agent: OAI-SearchBot',
                'Allow: /',
                '',
                'User-agent: ChatGPT-User',
                'Allow: /',
                '',
                'User-agent: ClaudeBot',
                'Allow: /',
                '',
                'User-agent: PerplexityBot',
                'Allow: /',
                '',
                'Sitemap: https://manjiapp.ir/sitemap.xml',
                'Host: https://manjiapp.ir',
                '',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('seo_redirects');
        Schema::dropIfExists('seo_settings');
    }
};
