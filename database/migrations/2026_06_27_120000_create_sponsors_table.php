<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
};
