<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds workflow_status and script_file_url to stories table.
     * Workflow status tracks the story creation cycle: written, characters_made, recorded, timeline_created, published
     */
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->enum('workflow_status', [
                'written',
                'characters_made',
                'recorded',
                'timeline_created',
                'published'
            ])->nullable()->after('status')->default('written');
            $table->string('script_file_url', 500)->nullable()->after('workflow_status');
            
            $table->index('workflow_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropIndex(['workflow_status']);
            $table->dropColumn(['workflow_status', 'script_file_url']);
        });
    }
};
