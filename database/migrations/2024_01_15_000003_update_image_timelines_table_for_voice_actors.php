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
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->foreignId('voice_actor_id')->nullable()->constrained('episode_voice_actors')->onDelete('set null')->after('episode_id')->comment('Associated voice actor for this timeline segment');
            $table->text('scene_description')->nullable()->after('image_url')->comment('Description of the scene');
            $table->string('transition_type', 50)->default('fade')->after('scene_description')->comment('Transition type: fade, slide, cut, etc.');
            $table->boolean('is_key_frame')->default(false)->after('transition_type')->comment('Whether this is a key frame');

            // Index for performance
            $table->index(['voice_actor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->dropForeign(['voice_actor_id']);
            $table->dropIndex(['voice_actor_id']);
            $table->dropColumn(['voice_actor_id', 'scene_description', 'transition_type', 'is_key_frame']);
        });
    }
};
