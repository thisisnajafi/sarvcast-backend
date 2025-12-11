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
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Website flavor (default, already exists as 'price')
            // Keep existing 'price' column for website flavor

            // Myket flavor
            $table->decimal('myket_price', 10, 2)->nullable()->after('price');
            $table->string('myket_product_id', 255)->nullable()->after('myket_price');

            // CafeBazaar flavor
            $table->decimal('cafebazaar_price', 10, 2)->nullable()->after('myket_product_id');
            $table->string('cafebazaar_product_id', 255)->nullable()->after('cafebazaar_price');

            // Rename existing price to website_price for clarity (optional, keeping price for backward compatibility)
            // We'll keep 'price' as website_price and add these new columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'myket_price',
                'myket_product_id',
                'cafebazaar_price',
                'cafebazaar_product_id'
            ]);
        });
    }
};
