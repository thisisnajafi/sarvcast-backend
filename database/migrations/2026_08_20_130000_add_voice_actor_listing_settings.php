<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key', 120)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            'key' => 'public_voice_actors_require_photo',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Existing voice actors should stay listed until an admin hides them.
        $voiceActorIds = User::query()
            ->where('role', User::ROLE_VOICE_ACTOR)
            ->pluck('id');

        if ($voiceActorIds->isNotEmpty()) {
            DB::table('user_resumes')
                ->whereIn('user_id', $voiceActorIds)
                ->update(['show_in_talent_directory' => true]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
