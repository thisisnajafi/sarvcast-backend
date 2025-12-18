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
        Schema::table('teacher_accounts', function (Blueprint $table) {
            // Add commission percentage field
            $table->decimal('commission_rate', 5, 2)->default(10.00)->after('discount_rate'); // 10% commission
            
            // Add coupon code field
            $table->string('coupon_code', 20)->unique()->nullable()->after('commission_rate');
            
            // Add coupon usage tracking
            $table->integer('coupon_usage_count')->default(0)->after('coupon_code');
            $table->decimal('total_commission_earned', 10, 2)->default(0.00)->after('coupon_usage_count');
            
            // Add commission settings
            $table->json('commission_settings')->nullable()->after('total_commission_earned');
            
            // Add indexes
            $table->index('coupon_code');
            $table->index(['status', 'commission_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_accounts', function (Blueprint $table) {
            $table->dropIndex(['coupon_code']);
            $table->dropIndex(['status', 'commission_rate']);
            
            $table->dropColumn([
                'commission_rate',
                'coupon_code',
                'coupon_usage_count',
                'total_commission_earned',
                'commission_settings'
            ]);
        });
    }
};