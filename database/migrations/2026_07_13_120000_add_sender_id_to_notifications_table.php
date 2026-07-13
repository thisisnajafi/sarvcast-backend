<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->nullable()->after('user_id');
                $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
                $table->index('sender_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'sender_id')) {
                $table->dropForeign(['sender_id']);
                $table->dropIndex(['sender_id']);
                $table->dropColumn('sender_id');
            }
        });
    }
};
