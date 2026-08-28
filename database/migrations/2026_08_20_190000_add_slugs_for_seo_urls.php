<?php

use App\Support\PersianSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SEO URL slugs for stories and categories.
 *
 * Three things happen here:
 *
 * 1. `stories.slug` is added — it did not exist, so story URLs could only ever
 *    be numeric.
 * 2. Both slug columns get a unique index. `categories.slug` previously had a
 *    plain index only, so two categories could share a slug even though the
 *    admin controller validated `unique:categories` at the application layer.
 * 3. Existing rows are backfilled with `PersianSlug`, which transliterates
 *    Persian properly (`قصه شب` → `ghesse-shab`) where Laravel's `Str::slug()`
 *    yields `ksh-shb`.
 *
 * Backfill runs before the unique index is created, and de-duplicates in PHP,
 * so the index cannot fail on legacy data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stories', 'slug')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->string('slug', 191)->nullable()->after('title');
            });
        }

        $this->backfillStories();
        $this->backfillCategories();

        Schema::table('stories', function (Blueprint $table) {
            $table->unique('slug', 'stories_slug_unique');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug', 'categories_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropUnique('stories_slug_unique');
            $table->dropColumn('slug');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_unique');
        });
    }

    private function backfillStories(): void
    {
        $taken = [];

        DB::table('stories')
            ->select('id', 'title', 'slug')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$taken) {
                foreach ($rows as $row) {
                    if (filled($row->slug)) {
                        $taken[$row->slug] = true;
                        continue;
                    }

                    $slug = PersianSlug::unique(
                        $row->title,
                        fn (string $candidate) => isset($taken[$candidate]),
                        150
                    );

                    $taken[$slug] = true;
                    DB::table('stories')->where('id', $row->id)->update(['slug' => $slug]);
                }
            });
    }

    /**
     * Categories already carry hand-written English slugs from `CategorySeeder`
     * (`bedtime-stories`, `adventure-stories`, …). Those are replaced with
     * transliterated Persian to match the agreed URL convention. This is safe:
     * the slug is output-only in the API — nothing resolves a category by it —
     * and no public category pages exist yet, so none are indexed.
     */
    private function backfillCategories(): void
    {
        $taken = [];

        foreach (DB::table('categories')->select('id', 'name')->orderBy('id')->get() as $row) {
            $slug = PersianSlug::unique(
                $row->name,
                fn (string $candidate) => isset($taken[$candidate]),
                100
            );

            $taken[$slug] = true;
            DB::table('categories')->where('id', $row->id)->update(['slug' => $slug]);
        }
    }
};
