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
        Schema::create('teacher_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('institution_name');
            $table->string('institution_type'); // school, university, private_center
            $table->string('teaching_subject'); // persian_language, literature, history, etc.
            $table->integer('years_of_experience');
            $table->string('certification_number')->nullable();
            $table->string('certification_authority')->nullable();
            $table->date('certification_date')->nullable();
            $table->integer('student_count')->default(0);
            $table->integer('max_student_licenses')->default(100);
            $table->decimal('discount_rate', 5, 2)->default(50.00); // 50% discount
            $table->enum('status', ['pending', 'verified', 'suspended', 'expired'])->default('pending');
            $table->json('verification_documents')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
            $table->index(['status', 'is_verified']);
            $table->index('institution_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_accounts');
    }
};
