<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the role enum to include 'basic' and change default to 'basic'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic') DEFAULT 'basic'");
        
        // Update existing users with 'parent' role to 'basic' if they don't have children
        DB::statement("UPDATE users SET role = 'basic' WHERE role = 'parent' AND id NOT IN (SELECT DISTINCT parent_id FROM users WHERE parent_id IS NOT NULL)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin') DEFAULT 'parent'");
        
        // Convert 'basic' users back to 'parent'
        DB::statement("UPDATE users SET role = 'parent' WHERE role = 'basic'");
    }
};