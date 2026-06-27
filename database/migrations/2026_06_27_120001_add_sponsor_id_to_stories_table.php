<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->foreignUuid('sponsor_id')
                ->nullable()
                ->after('category_id')
                ->constrained('sponsors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_id');
        });
    }
};
