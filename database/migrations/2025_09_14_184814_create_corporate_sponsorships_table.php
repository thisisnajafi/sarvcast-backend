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
        Schema::create('corporate_sponsorships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_partner_id');
            $table->string('company_name');
            $table->string('company_type'); // tech, education, media, cultural, other
            $table->string('industry'); // technology, education, entertainment, cultural, etc.
            $table->string('company_size'); // startup, small, medium, large, enterprise
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->string('website_url')->nullable();
            $table->text('company_description');
            $table->enum('sponsorship_type', ['content_sponsorship', 'brand_partnership', 'educational_initiative', 'cultural_preservation', 'technology_partnership']);
            $table->decimal('sponsorship_amount', 12, 2);
            $table->string('currency', 3)->default('IRR');
            $table->enum('payment_frequency', ['one_time', 'monthly', 'quarterly', 'annually']);
            $table->date('sponsorship_start_date');
            $table->date('sponsorship_end_date');
            $table->enum('status', ['pending', 'approved', 'active', 'suspended', 'completed', 'cancelled'])->default('pending');
            $table->json('sponsorship_benefits')->nullable(); // What the sponsor gets
            $table->json('content_requirements')->nullable(); // Content guidelines
            $table->json('target_audience')->nullable(); // Target demographics
            $table->boolean('requires_content_approval')->default(true);
            $table->boolean('allows_brand_mention')->default(true);
            $table->boolean('requires_logo_display')->default(true);
            $table->text('special_requirements')->nullable();
            $table->json('verification_documents')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->foreign('affiliate_partner_id')->references('id')->on('affiliate_partners')->onDelete('cascade');
            $table->index(['status', 'sponsorship_type']);
            $table->index('company_type');
            $table->index('industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_sponsorships');
    }
};
