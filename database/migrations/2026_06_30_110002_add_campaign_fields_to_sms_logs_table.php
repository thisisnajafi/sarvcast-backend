<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('phone_number')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('sms_logs', 'sms_template_id')) {
                $table->foreignId('sms_template_id')->nullable()->after('template_key')->constrained('sms_templates')->nullOnDelete();
            }

            if (! Schema::hasColumn('sms_logs', 'sms_campaign_id')) {
                $table->foreignId('sms_campaign_id')->nullable()->after('sms_template_id')->constrained('sms_campaigns')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sms_logs', 'sms_campaign_id')) {
                $table->dropConstrainedForeignId('sms_campaign_id');
            }

            if (Schema::hasColumn('sms_logs', 'sms_template_id')) {
                $table->dropConstrainedForeignId('sms_template_id');
            }

            if (Schema::hasColumn('sms_logs', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
