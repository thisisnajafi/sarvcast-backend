<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Performance indexes for play_histories.
     * Runs after create_play_histories_table; idempotent for servers that already
     * applied the earlier 2025_01_15 migration when the table existed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('play_histories')) {
            return;
        }

        Schema::table('play_histories', function (Blueprint $table) {
            $this->addIndexIfMissing($table, ['user_id', 'played_at'], 'idx_user_played_at');
            $this->addIndexIfMissing($table, ['episode_id', 'played_at'], 'idx_episode_played_at');
            $this->addIndexIfMissing($table, ['story_id', 'played_at'], 'idx_story_played_at');
            $this->addIndexIfMissing($table, ['user_id', 'completed', 'played_at'], 'idx_user_completed_played_at');
            $this->addIndexIfMissing($table, ['played_at', 'completed'], 'idx_played_at_completed');
            $this->addIndexIfMissing($table, ['played_at'], 'idx_played_at_desc');
            $this->addIndexIfMissing($table, ['user_id', 'episode_id'], 'idx_user_episode');
            $this->addIndexIfMissing($table, ['user_id', 'story_id'], 'idx_user_story');
            $this->addIndexIfMissing($table, ['duration_played'], 'idx_duration_played');
            $this->addIndexIfMissing($table, ['total_duration'], 'idx_total_duration');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('play_histories')) {
            return;
        }

        Schema::table('play_histories', function (Blueprint $table) {
            foreach ([
                'idx_user_played_at',
                'idx_episode_played_at',
                'idx_story_played_at',
                'idx_user_completed_played_at',
                'idx_played_at_completed',
                'idx_played_at_desc',
                'idx_user_episode',
                'idx_user_story',
                'idx_duration_played',
                'idx_total_duration',
            ] as $indexName) {
                $this->dropIndexIfExists($table, $indexName);
            }
        });
    }

    private function addIndexIfMissing(Blueprint $table, array $columns, string $indexName): void
    {
        if ($this->indexExists('play_histories', $indexName)) {
            return;
        }

        $table->index($columns, $indexName);
    }

    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        if (! $this->indexExists('play_histories', $indexName)) {
            return;
        }

        $table->dropIndex($indexName);
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
