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
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_source', 50)->nullable()->after('timezone'); // web, mobile, referral, etc.
            $table->string('referral_code', 20)->nullable()->after('registration_source'); // User's referral code
            $table->string('referred_by', 20)->nullable()->after('referral_code'); // Referral code used during registration
            $table->string('device_type', 20)->nullable()->after('referred_by'); // mobile, desktop, tablet
            $table->string('browser', 50)->nullable()->after('device_type'); // Browser used for registration
            $table->string('os', 50)->nullable()->after('browser'); // Operating system
            $table->string('country', 50)->nullable()->after('os'); // Country based on IP
            $table->string('city', 50)->nullable()->after('country'); // City based on IP
            $table->integer('total_sessions')->default(0)->after('city'); // Total number of sessions
            $table->integer('total_play_time')->default(0)->after('total_sessions'); // Total play time in seconds
            $table->integer('total_favorites')->default(0)->after('total_play_time'); // Total favorites count
            $table->integer('total_ratings')->default(0)->after('total_favorites'); // Total ratings given
            $table->decimal('total_spent', 10, 2)->default(0)->after('total_ratings'); // Total amount spent
            $table->timestamp('last_activity_at')->nullable()->after('total_spent'); // Last activity timestamp
            $table->timestamp('first_purchase_at')->nullable()->after('last_activity_at'); // First purchase timestamp
            $table->timestamp('last_purchase_at')->nullable()->after('first_purchase_at'); // Last purchase timestamp
            $table->json('analytics_data')->nullable()->after('last_purchase_at'); // Additional analytics data
            
            $table->index('registration_source');
            $table->index('referral_code');
            $table->index('referred_by');
            $table->index('device_type');
            $table->index('country');
            $table->index('last_activity_at');
            $table->index('first_purchase_at');
            $table->index('last_purchase_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['registration_source']);
            $table->dropIndex(['referral_code']);
            $table->dropIndex(['referred_by']);
            $table->dropIndex(['device_type']);
            $table->dropIndex(['country']);
            $table->dropIndex(['last_activity_at']);
            $table->dropIndex(['first_purchase_at']);
            $table->dropIndex(['last_purchase_at']);
            
            $table->dropColumn([
                'registration_source',
                'referral_code',
                'referred_by',
                'device_type',
                'browser',
                'os',
                'country',
                'city',
                'total_sessions',
                'total_play_time',
                'total_favorites',
                'total_ratings',
                'total_spent',
                'last_activity_at',
                'first_purchase_at',
                'last_purchase_at',
                'analytics_data'
            ]);
        });
    }
};