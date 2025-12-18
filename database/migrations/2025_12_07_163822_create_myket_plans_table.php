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
        Schema::create('myket_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام پلن
            $table->decimal('price', 10, 2); // قیمت
            $table->integer('duration_days'); // مدت زمان به روز
            $table->json('features')->nullable(); // ویژگی‌های پلن
            $table->boolean('is_active')->default(true); // فعال/غیرفعال
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('myket_plans');
    }
};
