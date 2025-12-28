<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates author_id and narrator_id foreign keys to reference users table instead of people table.
     * Sets any invalid foreign key values to NULL before updating constraints.
     */
    public function up(): void
    {
        // Get the actual foreign key constraint names
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'stories' 
            AND COLUMN_NAME IN ('author_id', 'narrator_id')
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        // Drop existing foreign keys by their actual constraint names
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE stories DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Try alternative constraint name format
                try {
                    $altName = "stories_{$fk->COLUMN_NAME}_foreign";
                    DB::statement("ALTER TABLE stories DROP FOREIGN KEY `{$altName}`");
                } catch (\Exception $e2) {
                    // Ignore if already dropped or doesn't exist
                }
            }
        }

        // Set any author_id or narrator_id values that don't exist in users table to NULL
        DB::statement("
            UPDATE stories 
            SET author_id = NULL 
            WHERE author_id IS NOT NULL 
            AND author_id NOT IN (SELECT id FROM users)
        ");

        DB::statement("
            UPDATE stories 
            SET narrator_id = NULL 
            WHERE narrator_id IS NOT NULL 
            AND narrator_id NOT IN (SELECT id FROM users)
        ");

        // Now update foreign keys to reference users table
        Schema::table('stories', function (Blueprint $table) {
            $table->foreign('author_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            $table->foreign('narrator_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            // Drop foreign keys to users
            $table->dropForeign(['author_id']);
            $table->dropForeign(['narrator_id']);
        });

        // Restore foreign keys to people table
        Schema::table('stories', function (Blueprint $table) {
            $table->foreign('author_id')
                  ->references('id')
                  ->on('people')
                  ->onDelete('set null');
            
            $table->foreign('narrator_id')
                  ->references('id')
                  ->on('people')
                  ->onDelete('set null');
        });
    }
};
