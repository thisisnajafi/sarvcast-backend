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
     * Safely adds 'super_admin' and 'voice_actor' to the role enum
     * without affecting existing data.
     */
    public function up(): void
    {
        // Modify the role enum to include 'super_admin' and 'voice_actor'
        // Keep all existing values: 'parent', 'child', 'admin', 'basic'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic', 'super_admin', 'voice_actor') DEFAULT 'basic'");
        
        // Add index on role column if it doesn't exist (for performance)
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_role_index')) {
                $table->index('role', 'users_role_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to previous enum values (before adding super_admin and voice_actor)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic') DEFAULT 'basic'");
        
        // Note: If there are any users with 'super_admin' or 'voice_actor' roles,
        // they will need to be manually updated before rolling back
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};
