<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applied after payments/subscriptions tables exist.
     * Idempotent for servers that already ran the earlier dated migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $paymentsAnchor = Schema::hasColumn('payments', 'payment_gateway')
                ? 'payment_gateway'
                : 'payment_method';

            if (! Schema::hasColumn('payments', 'billing_platform')) {
                $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->default('website')->after($paymentsAnchor);
                $table->index('billing_platform');
            }
            if (! Schema::hasColumn('payments', 'purchase_token')) {
                $table->string('purchase_token')->nullable()->after('billing_platform');
                $table->index('purchase_token');
            }
            if (! Schema::hasColumn('payments', 'order_id')) {
                $table->string('order_id')->nullable()->after('purchase_token');
                $table->index('order_id');
            }
            if (! Schema::hasColumn('payments', 'package_name')) {
                $table->string('package_name')->nullable()->after('order_id');
            }
            if (! Schema::hasColumn('payments', 'product_id')) {
                $table->string('product_id')->nullable()->after('package_name');
                $table->index('product_id');
            }
            if (! Schema::hasColumn('payments', 'purchase_state')) {
                $table->string('purchase_state')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('payments', 'purchase_time')) {
                $table->timestamp('purchase_time')->nullable()->after('purchase_state');
            }
            if (! Schema::hasColumn('payments', 'store_response')) {
                $table->json('store_response')->nullable()->after('purchase_time');
            }
            if (! Schema::hasColumn('payments', 'is_acknowledged')) {
                $table->boolean('is_acknowledged')->default(false)->after('store_response');
            }
            if (! Schema::hasColumn('payments', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('is_acknowledged');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'billing_platform')) {
                $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->default('website')->after('payment_method');
                $table->index('billing_platform');
            }
            if (! Schema::hasColumn('subscriptions', 'store_subscription_id')) {
                $table->string('store_subscription_id')->nullable()->after('billing_platform');
                $table->index('store_subscription_id');
            }
            if (! Schema::hasColumn('subscriptions', 'auto_renew_enabled')) {
                $table->boolean('auto_renew_enabled')->default(true)->after('auto_renew');
            }
            if (! Schema::hasColumn('subscriptions', 'store_expiry_time')) {
                $table->timestamp('store_expiry_time')->nullable()->after('store_subscription_id');
            }
            if (! Schema::hasColumn('subscriptions', 'store_metadata')) {
                $table->json('store_metadata')->nullable()->after('store_expiry_time');
            }
        });

        if (Schema::hasTable('app_versions')) {
            Schema::table('app_versions', function (Blueprint $table) {
                if (! Schema::hasColumn('app_versions', 'billing_platform')) {
                    $table->enum('billing_platform', ['website', 'cafebazaar', 'myket'])->nullable()->after('platform');
                    $table->index('billing_platform');
                }
                if (! Schema::hasColumn('app_versions', 'billing_config')) {
                    $table->json('billing_config')->nullable()->after('billing_platform');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'billing_platform')) {
                    $table->dropIndex(['billing_platform']);
                }
                if (Schema::hasColumn('payments', 'purchase_token')) {
                    $table->dropIndex(['purchase_token']);
                }
                if (Schema::hasColumn('payments', 'order_id')) {
                    $table->dropIndex(['order_id']);
                }
                if (Schema::hasColumn('payments', 'product_id')) {
                    $table->dropIndex(['product_id']);
                }

                $columns = array_filter([
                    Schema::hasColumn('payments', 'billing_platform') ? 'billing_platform' : null,
                    Schema::hasColumn('payments', 'purchase_token') ? 'purchase_token' : null,
                    Schema::hasColumn('payments', 'order_id') ? 'order_id' : null,
                    Schema::hasColumn('payments', 'package_name') ? 'package_name' : null,
                    Schema::hasColumn('payments', 'product_id') ? 'product_id' : null,
                    Schema::hasColumn('payments', 'purchase_state') ? 'purchase_state' : null,
                    Schema::hasColumn('payments', 'purchase_time') ? 'purchase_time' : null,
                    Schema::hasColumn('payments', 'store_response') ? 'store_response' : null,
                    Schema::hasColumn('payments', 'is_acknowledged') ? 'is_acknowledged' : null,
                    Schema::hasColumn('payments', 'acknowledged_at') ? 'acknowledged_at' : null,
                ]);

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'billing_platform')) {
                    $table->dropIndex(['billing_platform']);
                }
                if (Schema::hasColumn('subscriptions', 'store_subscription_id')) {
                    $table->dropIndex(['store_subscription_id']);
                }

                $columns = array_filter([
                    Schema::hasColumn('subscriptions', 'billing_platform') ? 'billing_platform' : null,
                    Schema::hasColumn('subscriptions', 'store_subscription_id') ? 'store_subscription_id' : null,
                    Schema::hasColumn('subscriptions', 'auto_renew_enabled') ? 'auto_renew_enabled' : null,
                    Schema::hasColumn('subscriptions', 'store_expiry_time') ? 'store_expiry_time' : null,
                    Schema::hasColumn('subscriptions', 'store_metadata') ? 'store_metadata' : null,
                ]);

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('app_versions')) {
            Schema::table('app_versions', function (Blueprint $table) {
                if (Schema::hasColumn('app_versions', 'billing_platform')) {
                    $table->dropIndex(['billing_platform']);
                }

                $columns = array_filter([
                    Schema::hasColumn('app_versions', 'billing_platform') ? 'billing_platform' : null,
                    Schema::hasColumn('app_versions', 'billing_config') ? 'billing_config' : null,
                ]);

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
