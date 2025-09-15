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
        // Add revenue analytics fields to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('gateway_transaction_id');
            $table->string('payment_gateway')->nullable()->after('payment_method');
            $table->decimal('gateway_fee', 10, 2)->default(0)->after('payment_gateway');
            $table->decimal('net_amount', 10, 2)->default(0)->after('gateway_fee'); // Amount after fees
            $table->string('currency', 3)->default('IRR')->after('net_amount');
            $table->decimal('exchange_rate', 10, 4)->default(1)->after('currency');
            $table->json('payment_metadata')->nullable()->after('exchange_rate'); // Additional payment data
            $table->timestamp('processed_at')->nullable()->after('payment_metadata');
            $table->timestamp('refunded_at')->nullable()->after('processed_at');
            $table->string('refund_reason')->nullable()->after('refunded_at');
            $table->decimal('refund_amount', 10, 2)->default(0)->after('refund_reason');
            
            $table->index('payment_method');
            $table->index('payment_gateway');
            $table->index('currency');
            $table->index('processed_at');
            $table->index('refunded_at');
        });

        // Add revenue analytics fields to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('monthly_price', 10, 2)->default(0)->after('price');
            $table->decimal('yearly_price', 10, 2)->default(0)->after('monthly_price');
            $table->integer('trial_days')->default(0)->after('yearly_price');
            $table->boolean('auto_renew')->default(true)->after('trial_days');
            $table->string('cancellation_reason')->nullable()->after('auto_renew');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            $table->integer('renewal_count')->default(0)->after('cancelled_at');
            $table->decimal('total_revenue', 10, 2)->default(0)->after('renewal_count');
            $table->decimal('avg_monthly_revenue', 10, 2)->default(0)->after('total_revenue');
            $table->json('subscription_metadata')->nullable()->after('avg_monthly_revenue');
            
            $table->index('monthly_price');
            $table->index('yearly_price');
            $table->index('trial_days');
            $table->index('auto_renew');
            $table->index('cancelled_at');
            $table->index('renewal_count');
            $table->index('total_revenue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['payment_gateway']);
            $table->dropIndex(['currency']);
            $table->dropIndex(['processed_at']);
            $table->dropIndex(['refunded_at']);
            
            $table->dropColumn([
                'payment_method',
                'payment_gateway',
                'gateway_fee',
                'net_amount',
                'currency',
                'exchange_rate',
                'payment_metadata',
                'processed_at',
                'refunded_at',
                'refund_reason',
                'refund_amount'
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['monthly_price']);
            $table->dropIndex(['yearly_price']);
            $table->dropIndex(['trial_days']);
            $table->dropIndex(['auto_renew']);
            $table->dropIndex(['cancelled_at']);
            $table->dropIndex(['renewal_count']);
            $table->dropIndex(['total_revenue']);
            
            $table->dropColumn([
                'monthly_price',
                'yearly_price',
                'trial_days',
                'auto_renew',
                'cancellation_reason',
                'cancelled_at',
                'renewal_count',
                'total_revenue',
                'avg_monthly_revenue',
                'subscription_metadata'
            ]);
        });
    }
};