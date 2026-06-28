<?php

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('display_title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['is_visible', 'sort_order']);
        });

        $legacy = [
            ['phone' => '09025472668', 'title' => 'روانشناس کودک', 'order' => 1],
            ['phone' => '09131397003', 'title' => 'کارگردان، مدرس', 'order' => 2],
            ['phone' => '09136708883', 'title' => 'برنامه نویس اپلیکیشن', 'order' => 3],
            ['phone' => '09138333293', 'title' => 'مدیر تولید و برنامه ریزی', 'order' => 4],
            ['phone' => '09393676109', 'title' => 'تهیه و تدوین', 'order' => 5],
        ];

        foreach ($legacy as $item) {
            $user = User::where('phone_number', $item['phone'])->first();
            if (! $user) {
                continue;
            }

            TeamMember::query()->create([
                'user_id' => $user->id,
                'display_title' => $item['title'],
                'sort_order' => $item['order'],
                'is_visible' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
