<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('usable_type', 100);
            $table->unsignedBigInteger('usable_id');
            $table->string('field', 100);
            $table->timestamp('created_at')->nullable();

            $table->unique(['media_asset_id', 'usable_type', 'usable_id', 'field'], 'media_usages_unique');
            $table->index(['usable_type', 'usable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
    }
};
