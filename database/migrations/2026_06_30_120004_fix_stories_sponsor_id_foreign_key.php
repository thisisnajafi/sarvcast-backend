<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recovery migration for servers where stories.sponsor_id was added without a
 * valid foreign key (MySQL 3780 charset/type mismatch).
 */
return new class extends Migration
{
    private const FK_NAME = 'stories_sponsor_id_foreign';

    public function up(): void
    {
        if (! Schema::hasTable('sponsors') || ! Schema::hasTable('stories')) {
            return;
        }

        if ($this->foreignKeyExists('stories', self::FK_NAME)) {
            return;
        }

        $this->ensureSponsorsPrimaryKeyIsUuid();

        if (! Schema::hasColumn('stories', 'sponsor_id')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->uuid('sponsor_id')->nullable()->after('category_id');
            });
        }

        $this->alignStoriesSponsorIdColumn();

        Schema::table('stories', function (Blueprint $table) {
            $table->foreign('sponsor_id', self::FK_NAME)
                ->references('id')
                ->on('sponsors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally no-op: 2026_06_27_120001 owns rollback of sponsor_id.
    }

    private function alignStoriesSponsorIdColumn(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $sponsorId = $this->columnMeta('sponsors', 'id');
        if ($sponsorId === null) {
            return;
        }

        $charset = $sponsorId->CHARACTER_SET_NAME ?: 'utf8mb4';
        $collation = $sponsorId->COLLATION_NAME ?: 'utf8mb4_unicode_ci';

        DB::statement(sprintf(
            'ALTER TABLE `stories` MODIFY `sponsor_id` CHAR(36) CHARACTER SET %s COLLATE %s NULL',
            $this->quoteIdentifier($charset),
            $this->quoteIdentifier($collation),
        ));
    }

    private function ensureSponsorsPrimaryKeyIsUuid(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = $this->columnMeta('sponsors', 'id');
        if ($column === null || str_contains(strtolower($column->COLUMN_TYPE), 'char(36)')) {
            return;
        }

        if (DB::table('sponsors')->exists()) {
            throw new \RuntimeException(
                'sponsors.id must be CHAR(36) UUID but is '.$column->COLUMN_TYPE.'. Empty or migrate sponsors before continuing.'
            );
        }

        DB::statement('ALTER TABLE `sponsors` MODIFY `id` CHAR(36) NOT NULL');
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        ) !== null;
    }

    private function columnMeta(string $table, string $column): ?object
    {
        return DB::selectOne(
            'SELECT COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$table, $column]
        );
    }

    private function quoteIdentifier(string $value): string
    {
        return '`'.str_replace('`', '``', $value).'`';
    }
};
