<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->enum('media_type', ['image', 'audio'])->default('image')->after('extension');
            $table->unsignedInteger('duration_seconds')->nullable()->after('height');
            $table->index('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex(['media_type']);
            $table->dropColumn(['media_type', 'duration_seconds']);
        });
    }
};
