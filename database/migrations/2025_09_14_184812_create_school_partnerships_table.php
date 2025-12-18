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
        Schema::create('school_partnerships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_partner_id');
            $table->string('school_name');
            $table->string('school_type'); // public, private, international, cultural_center
            $table->string('school_level'); // elementary, middle, high, university
            $table->string('location');
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->integer('student_count');
            $table->integer('teacher_count');
            $table->enum('partnership_model', ['revenue_sharing', 'licensing', 'pilot']);
            $table->decimal('discount_rate', 5, 2)->default(60.00); // 60% discount
            $table->decimal('revenue_share_rate', 5, 2)->nullable(); // 20% revenue share
            $table->decimal('annual_license_fee', 10, 2)->nullable();
            $table->integer('max_student_capacity')->default(500);
            $table->date('partnership_start_date');
            $table->date('partnership_end_date');
            $table->enum('status', ['pending', 'active', 'suspended', 'expired', 'terminated'])->default('pending');
            $table->json('verification_documents')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('affiliate_partner_id')->references('id')->on('affiliate_partners')->onDelete('cascade');
            $table->index(['status', 'partnership_model']);
            $table->index('school_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_partnerships');
    }
};
