<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->boolean('has_multiple_voice_actors')->default(false)->after('use_image_timeline')->comment('Whether episode has multiple voice actors');
            $table->unsignedInteger('voice_actor_count')->default(0)->after('has_multiple_voice_actors')->comment('Total number of voice actors in episode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['has_multiple_voice_actors', 'voice_actor_count']);
        });
    }
};
