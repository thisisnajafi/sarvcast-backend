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
        Schema::create('school_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_partnership_id');
            $table->unsignedBigInteger('user_id'); // Student or teacher
            $table->string('license_type'); // student, teacher, admin
            $table->string('user_role'); // student, teacher, administrator
            $table->decimal('original_price', 10, 2);
            $table->decimal('discounted_price', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->boolean('is_activated')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            
            $table->foreign('school_partnership_id')->references('id')->on('school_partnerships')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['school_partnership_id', 'user_id']);
            $table->index(['status', 'end_date']);
            $table->index('license_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_licenses');
    }
};
