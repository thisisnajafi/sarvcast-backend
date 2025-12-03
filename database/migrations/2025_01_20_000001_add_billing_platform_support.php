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
        // Add billing platform support to payments table
        Schema::table('payments', function (Blueprint $table) {
            // Billing platform: 'website' (Zarinpal), 'cafebazaar', 'myket'
            $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->default('website')->after('payment_gateway');

            // In-app purchase specific fields
            $table->string('purchase_token')->nullable()->after('billing_platform'); // Purchase token from store
            $table->string('order_id')->nullable()->after('purchase_token'); // Order ID from store
            $table->string('package_name')->nullable()->after('order_id'); // App package name
            $table->string('product_id')->nullable()->after('package_name'); // Product/SKU ID
            $table->string('purchase_state')->nullable()->after('product_id'); // Purchase state (purchased, pending, etc.)
            $table->timestamp('purchase_time')->nullable()->after('purchase_state'); // Purchase timestamp from store
            $table->json('store_response')->nullable()->after('purchase_time'); // Full response from store
            $table->boolean('is_acknowledged')->default(false)->after('store_response'); // Whether purchase is acknowledged
            $table->timestamp('acknowledged_at')->nullable()->after('is_acknowledged');

            // Indexes
            $table->index('billing_platform');
            $table->index('purchase_token');
            $table->index('order_id');
            $table->index('product_id');
        });

        // Add billing platform support to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->default('website')->after('payment_method');
            $table->string('store_subscription_id')->nullable()->after('billing_platform'); // Subscription ID from store
            $table->boolean('auto_renew_enabled')->default(true)->after('auto_renew'); // Auto-renew status from store
            $table->timestamp('store_expiry_time')->nullable()->after('store_subscription_id'); // Expiry time from store
            $table->json('store_metadata')->nullable()->after('store_expiry_time'); // Additional store data

            $table->index('billing_platform');
            $table->index('store_subscription_id');
        });

        // Add billing platform configuration to app_versions table
        if (Schema::hasTable('app_versions')) {
            Schema::table('app_versions', function (Blueprint $table) {
                $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->nullable()->after('platform');
                $table->json('billing_config')->nullable()->after('billing_platform'); // Store-specific config

                $table->index('billing_platform');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['billing_platform']);
            $table->dropIndex(['purchase_token']);
            $table->dropIndex(['order_id']);
            $table->dropIndex(['product_id']);

            $table->dropColumn([
                'billing_platform',
                'purchase_token',
                'order_id',
                'package_name',
                'product_id',
                'purchase_state',
                'purchase_time',
                'store_response',
                'is_acknowledged',
                'acknowledged_at'
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['billing_platform']);
            $table->dropIndex(['store_subscription_id']);

            $table->dropColumn([
                'billing_platform',
                'store_subscription_id',
                'auto_renew_enabled',
                'store_expiry_time',
                'store_metadata'
            ]);
        });

        if (Schema::hasTable('app_versions')) {
            Schema::table('app_versions', function (Blueprint $table) {
                $table->dropIndex(['billing_platform']);
                $table->dropColumn(['billing_platform', 'billing_config']);
            });
        }
    }
};

