<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sponsors')) {
            $this->ensureSponsorsIdIsUuid();

            return;
        }

        Schema::create('sponsors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('logo_path');
            $table->string('tagline', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('instagram_handle', 60)->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('map_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsors');
    }

    private function ensureSponsorsIdIsUuid(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = $this->columnMeta('sponsors', 'id');
        if ($column === null) {
            return;
        }

        if (str_contains(strtolower($column->COLUMN_TYPE), 'char(36)')) {
            return;
        }

        if (DB::table('sponsors')->exists()) {
            throw new \RuntimeException(
                'sponsors.id must be CHAR(36) UUID but is '.$column->COLUMN_TYPE.'. Migrate or empty the table before continuing.'
            );
        }

        Schema::drop('sponsors');

        Schema::create('sponsors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('logo_path');
            $table->string('tagline', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('instagram_handle', 60)->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('map_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
            $table->index('name');
        });
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
};
