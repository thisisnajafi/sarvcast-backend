<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('image_timelines') || ! Schema::hasTable('stories') || ! Schema::hasTable('episodes')) {
            return;
        }

        if (! Schema::hasColumn('image_timelines', 'story_id')) {
            Schema::table('image_timelines', function (Blueprint $table) {
                $table->foreignId('story_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE image_timelines
                SET story_id = (
                    SELECT episodes.story_id
                    FROM episodes
                    WHERE episodes.id = image_timelines.episode_id
                )
                WHERE story_id IS NULL
                  AND episode_id IS NOT NULL
            ');
        } else {
            DB::statement('
                UPDATE image_timelines
                INNER JOIN episodes ON episodes.id = image_timelines.episode_id
                SET image_timelines.story_id = episodes.story_id
                WHERE image_timelines.story_id IS NULL
            ');
        }

        if (Schema::hasColumn('image_timelines', 'story_id')) {
            Schema::table('image_timelines', function (Blueprint $table) {
                $table->foreignId('story_id')->nullable(false)->change();
            });
        }

        if ($this->foreignKeyExists('image_timelines', 'episode_id')) {
            Schema::table('image_timelines', function (Blueprint $table) {
                $table->dropForeign(['episode_id']);
            });
        }

        Schema::table('image_timelines', function (Blueprint $table) {
            if ($this->indexExists('image_timelines', 'idx_episode_time')) {
                $table->dropIndex('idx_episode_time');
            }
            if ($this->indexExists('image_timelines', 'idx_episode_order')) {
                $table->dropIndex('idx_episode_order');
            }
            if (! $this->indexExists('image_timelines', 'idx_story_time')) {
                $table->index(['story_id', 'start_time', 'end_time'], 'idx_story_time');
            }
            if (! $this->indexExists('image_timelines', 'idx_story_order')) {
                $table->index(['story_id', 'image_order'], 'idx_story_order');
            }
        });

        if (! $this->foreignKeyExists('image_timelines', 'episode_id') && Schema::hasColumn('image_timelines', 'episode_id')) {
            Schema::table('image_timelines', function (Blueprint $table) {
                $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
            });
        }

        if (! Schema::hasColumn('stories', 'use_image_timeline')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->boolean('use_image_timeline')->default(false)->comment('Whether story uses timeline-based image changes');
            });
        }

        $storyIdsWithTimeline = DB::table('episodes')
            ->where('use_image_timeline', true)
            ->distinct()
            ->pluck('story_id')
            ->filter();

        if ($storyIdsWithTimeline->isNotEmpty()) {
            DB::table('stories')
                ->whereIn('id', $storyIdsWithTimeline)
                ->update(['use_image_timeline' => true]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('image_timelines') || ! Schema::hasTable('stories')) {
            return;
        }

        if ($this->foreignKeyExists('image_timelines', 'episode_id')) {
            Schema::table('image_timelines', function (Blueprint $table) {
                $table->dropForeign(['episode_id']);
            });
        }

        Schema::table('image_timelines', function (Blueprint $table) {
            if ($this->indexExists('image_timelines', 'idx_story_time')) {
                $table->dropIndex('idx_story_time');
            }
            if ($this->indexExists('image_timelines', 'idx_story_order')) {
                $table->dropIndex('idx_story_order');
            }
            if (! $this->indexExists('image_timelines', 'idx_episode_time')) {
                $table->index(['episode_id', 'start_time', 'end_time'], 'idx_episode_time');
            }
            if (! $this->indexExists('image_timelines', 'idx_episode_order')) {
                $table->index(['episode_id', 'image_order'], 'idx_episode_order');
            }
        });

        if ($this->foreignKeyExists('image_timelines', 'story_id')) {
            Schema::table('image_timelines', function (Blueprint $table) {
                $table->dropForeign(['story_id']);
                $table->dropColumn('story_id');
            });
        }

        if (! $this->foreignKeyExists('image_timelines', 'episode_id') && Schema::hasColumn('image_timelines', 'episode_id')) {
            Schema::table('image_timelines', function (Blueprint $table) {
                $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('stories', 'use_image_timeline')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->dropColumn('use_image_timeline');
            });
        }
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::selectOne(
            'SELECT COUNT(*) AS count
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column]
        );

        return (int) ($result->count ?? 0) > 0;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT COUNT(*) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->count ?? 0) > 0;
    }
};
