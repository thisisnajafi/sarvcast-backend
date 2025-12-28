<?php

/**
 * Script to assign super_admin role to specific users
 * 
 * Usage: php update_super_admins.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$phoneNumbers = [
    '09136708883',
    '09138333293',
];

echo "Updating users to super_admin role...\n\n";

foreach ($phoneNumbers as $phoneNumber) {
    $user = User::where('phone_number', $phoneNumber)->first();
    
    if (!$user) {
        echo "❌ User with phone number {$phoneNumber} not found.\n";
        continue;
    }
    
    $oldRole = $user->role;
    $user->role = User::ROLE_SUPER_ADMIN;
    $user->save();
    
    echo "✅ Updated user: {$user->first_name} {$user->last_name} ({$phoneNumber})\n";
    echo "   Role changed from: {$oldRole} → super_admin\n\n";
}

echo "Done!\n";

