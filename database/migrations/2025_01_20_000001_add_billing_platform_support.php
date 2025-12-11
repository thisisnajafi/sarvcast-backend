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
            if (!Schema::hasColumn('payments', 'billing_platform')) {
                $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->default('website')->after('payment_gateway');
                $table->index('billing_platform');
            }

            // In-app purchase specific fields
            if (!Schema::hasColumn('payments', 'purchase_token')) {
                $table->string('purchase_token')->nullable()->after('billing_platform'); // Purchase token from store
                $table->index('purchase_token');
            }
            if (!Schema::hasColumn('payments', 'order_id')) {
                $table->string('order_id')->nullable()->after('purchase_token'); // Order ID from store
                $table->index('order_id');
            }
            if (!Schema::hasColumn('payments', 'package_name')) {
                $table->string('package_name')->nullable()->after('order_id'); // App package name
            }
            if (!Schema::hasColumn('payments', 'product_id')) {
                $table->string('product_id')->nullable()->after('package_name'); // Product/SKU ID
                $table->index('product_id');
            }
            if (!Schema::hasColumn('payments', 'purchase_state')) {
                $table->string('purchase_state')->nullable()->after('product_id'); // Purchase state (purchased, pending, etc.)
            }
            if (!Schema::hasColumn('payments', 'purchase_time')) {
                $table->timestamp('purchase_time')->nullable()->after('purchase_state'); // Purchase timestamp from store
            }
            if (!Schema::hasColumn('payments', 'store_response')) {
                $table->json('store_response')->nullable()->after('purchase_time'); // Full response from store
            }
            if (!Schema::hasColumn('payments', 'is_acknowledged')) {
                $table->boolean('is_acknowledged')->default(false)->after('store_response'); // Whether purchase is acknowledged
            }
            if (!Schema::hasColumn('payments', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('is_acknowledged');
            }
        });

        // Add billing platform support to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'billing_platform')) {
                $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->default('website')->after('payment_method');
                $table->index('billing_platform');
            }
            if (!Schema::hasColumn('subscriptions', 'store_subscription_id')) {
                $table->string('store_subscription_id')->nullable()->after('billing_platform'); // Subscription ID from store
                $table->index('store_subscription_id');
            }
            if (!Schema::hasColumn('subscriptions', 'auto_renew_enabled')) {
                $table->boolean('auto_renew_enabled')->default(true)->after('auto_renew'); // Auto-renew status from store
            }
            if (!Schema::hasColumn('subscriptions', 'store_expiry_time')) {
                $table->timestamp('store_expiry_time')->nullable()->after('store_subscription_id'); // Expiry time from store
            }
            if (!Schema::hasColumn('subscriptions', 'store_metadata')) {
                $table->json('store_metadata')->nullable()->after('store_expiry_time'); // Additional store data
            }
        });

        // Add billing platform configuration to app_versions table
        if (Schema::hasTable('app_versions')) {
            Schema::table('app_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('app_versions', 'billing_platform')) {
                    $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->nullable()->after('platform');
                    $table->index('billing_platform');
                }
                if (!Schema::hasColumn('app_versions', 'billing_config')) {
                    $table->json('billing_config')->nullable()->after('billing_platform'); // Store-specific config
                }
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

