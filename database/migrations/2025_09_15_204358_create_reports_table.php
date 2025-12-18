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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_id'); // User who reported
            $table->string('content_type', 50); // story, episode, user, comment
            $table->unsignedBigInteger('content_id'); // ID of the reported content
            $table->string('type', 50); // inappropriate_content, spam, harassment, copyright, other
            $table->text('description'); // Detailed description of the issue
            $table->json('evidence')->nullable(); // Screenshots, links, etc.
            $table->string('status', 20)->default('pending'); // pending, investigating, resolved, dismissed
            $table->unsignedBigInteger('resolved_by')->nullable(); // Moderator who resolved
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable(); // Resolution details
            $table->string('action_taken', 50)->nullable(); // no_action, warning, remove_content, suspend_user, other
            $table->integer('priority')->default(1); // 1=low, 2=medium, 3=high, 4=urgent
            $table->boolean('is_anonymous')->default(false); // Anonymous report
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();
            
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('content_type');
            $table->index('content_id');
            $table->index('type');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};