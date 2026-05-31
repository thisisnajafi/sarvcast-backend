<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure subscription_plans.cafebazaar_product_id matches CafeBazaar Pishkhan SKUs.
     */
    public function up(): void
    {
        $mapping = [
            '1month' => '1-month-sub',
            '3months' => '3-month-sub',
            '6months' => '6-month-sub',
            '1year' => '1-year-sub',
        ];

        foreach ($mapping as $slug => $productId) {
            DB::table('subscription_plans')
                ->where('slug', $slug)
                ->where(function ($query) {
                    $query->whereNull('cafebazaar_product_id')
                        ->orWhere('cafebazaar_product_id', '');
                })
                ->update(['cafebazaar_product_id' => $productId]);
        }
    }

    public function down(): void
    {
        // Non-destructive: leave product IDs in place on rollback.
    }
};
