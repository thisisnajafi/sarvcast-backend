<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasTable('profile_views')) {
            return;
        }

        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viewed_user_id');
            $table->unsignedBigInteger('viewer_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('viewed_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('viewer_id')->references('id')->on('users')->onDelete('set null');
            $table->index('viewed_user_id');
            $table->index('viewer_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};
