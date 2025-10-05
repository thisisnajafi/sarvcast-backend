<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 DIRECT FIX FOR SUBSCRIPTION ID 12\n";
echo "====================================\n\n";

try {
    echo "Finding subscription ID 12...\n";
    $subscription = \App\Models\Subscription::find(12);
    
    if (!$subscription) {
        echo "❌ Subscription not found!\n";
        exit(1);
    }
    
    echo "✅ Found subscription:\n";
    echo "   ID: {$subscription->id}\n";
    echo "   User ID: {$subscription->user_id}\n";
    echo "   Type: {$subscription->type}\n";
    echo "   Status: {$subscription->status}\n";
    echo "   Start Date: " . ($subscription->start_date ?: 'NULL') . "\n";
    echo "   End Date: " . ($subscription->end_date ?: 'NULL') . "\n\n";
    
    if ($subscription->end_date) {
        echo "⚠️  Subscription already has an end_date!\n";
        echo "   Current end_date: {$subscription->end_date}\n";
        
        $isActive = $subscription->status === 'active' && $subscription->end_date > now();
        echo "   Is Active: " . ($isActive ? 'YES' : 'NO') . "\n";
        
        if ($isActive) {
            echo "✅ Subscription is already properly active!\n";
            exit(0);
        }
    }
    
    echo "Calculating end_date...\n";
    $startDate = $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date) : now();
    
    // Calculate duration based on type
    $durationDays = match($subscription->type) {
        '1month' => 30,
        '3months' => 90,
        '6months' => 180,
        '1year' => 365,
        default => 30
    };
    
    $endDate = $startDate->copy()->addDays($durationDays);
    
    echo "   Start Date: {$startDate}\n";
    echo "   Duration: {$durationDays} days\n";
    echo "   End Date: {$endDate}\n\n";
    
    echo "Updating subscription...\n";
    $subscription->end_date = $endDate;
    $subscription->save();
    
    echo "✅ Subscription updated successfully!\n\n";
    
    // Verify the update
    $subscription = $subscription->fresh();
    echo "Verification:\n";
    echo "   ID: {$subscription->id}\n";
    echo "   Status: {$subscription->status}\n";
    echo "   Start Date: {$subscription->start_date}\n";
    echo "   End Date: {$subscription->end_date}\n";
    echo "   Updated At: {$subscription->updated_at}\n\n";
    
    // Test if it's now active
    $isActive = $subscription->status === 'active' && $subscription->end_date > now();
    echo "Is Active: " . ($isActive ? '✅ YES' : '❌ NO') . "\n\n";
    
    if ($isActive) {
        echo "🎉 SUCCESS! Subscription is now properly active!\n\n";
        
        // Test the user's premium status
        $user = \App\Models\User::find($subscription->user_id);
        $activeSubscription = $user->activeSubscription;
        
        echo "Testing user premium status:\n";
        echo "   Active Subscription: " . ($activeSubscription ? 'Found' : 'Not Found') . "\n";
        echo "   Has Active Subscription: " . ($user->hasActiveSubscription() ? 'YES' : 'NO') . "\n\n";
        
        echo "✅ User should now be premium!\n";
        echo "   Test: GET /api/v1/auth/profile\n";
    } else {
        echo "❌ Subscription still not active. Check the dates.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}
