<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('event_uuid', 64)->nullable()->after('request_id');
            $table->unique(['device_id', 'event_uuid'], 'activity_logs_device_event_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropUnique('activity_logs_device_event_uuid_unique');
            $table->dropColumn('event_uuid');
        });
    }
};
