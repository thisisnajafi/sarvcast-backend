<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SLUG_TO_MYKET_SKU = [
        '1month' => '1-month-sub',
        '3months' => '3-month-sub',
        '6months' => '6-month-sub',
        '1year' => '1-year-sub',
    ];

    public function up(): void
    {
        foreach (self::SLUG_TO_MYKET_SKU as $slug => $sku) {
            DB::table('subscription_plans')
                ->where('slug', $slug)
                ->where(function ($query) {
                    $query->whereNull('myket_product_id')
                        ->orWhere('myket_product_id', '');
                })
                ->update([
                    'myket_product_id' => $sku,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: leave backfilled values in place on rollback.
    }
};
