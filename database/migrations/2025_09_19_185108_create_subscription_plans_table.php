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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام پلن
            $table->string('slug')->unique(); // شناسه یکتا (1month, 3months, etc.)
            $table->text('description')->nullable(); // توضیحات
            $table->integer('duration_days'); // مدت زمان به روز
            $table->decimal('price', 10, 2); // قیمت
            $table->string('currency', 3)->default('IRT'); // ارز
            $table->integer('discount_percentage')->default(0); // درصد تخفیف
            $table->boolean('is_active')->default(true); // فعال/غیرفعال
            $table->boolean('is_featured')->default(false); // پلن ویژه
            $table->integer('sort_order')->default(0); // ترتیب نمایش
            $table->json('features')->nullable(); // ویژگی‌های پلن
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
