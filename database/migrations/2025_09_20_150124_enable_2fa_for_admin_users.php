<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable 2FA for all admin users based on role field
        DB::table('users')
            ->whereIn('role', ['admin', 'super_admin'])
            ->update(['requires_2fa' => true]);
        
        // Enable 2FA for users with admin roles in the role system
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->get();
        
        foreach ($adminUsers as $user) {
            $user->update(['requires_2fa' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable 2FA for admin users
        DB::table('users')
            ->whereIn('role', ['admin', 'super_admin'])
            ->update(['requires_2fa' => false]);
        
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->get();
        
        foreach ($adminUsers as $user) {
            $user->update(['requires_2fa' => false]);
        }
    }
};