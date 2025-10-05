<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 STEP-BY-STEP PREMIUM DETECTION DEBUG FOR USER ID 3...\n\n";

try {
    echo "STEP 1: Check User Data\n";
    echo "======================\n";
    $user = \App\Models\User::find(3);
    if ($user) {
        echo "✅ User found: {$user->first_name} {$user->last_name}\n";
        echo "📱 Phone: {$user->phone_number}\n";
        echo "👤 Role: {$user->role}\n";
        echo "📊 Status: {$user->status}\n";
        echo "🕐 Current time: " . now() . "\n";
    } else {
        echo "❌ User not found\n";
        exit;
    }
    
    echo "\nSTEP 2: Check All Subscriptions (Raw Database Query)\n";
    echo "==================================================\n";
    $subscriptions = \App\Models\Subscription::where('user_id', 3)->get();
    echo "📋 Total subscriptions: " . $subscriptions->count() . "\n";
    
    foreach ($subscriptions as $subscription) {
        echo "📄 Subscription ID: {$subscription->id}\n";
        echo "   Type: {$subscription->type}\n";
        echo "   Status: {$subscription->status}\n";
        echo "   Price: {$subscription->price}\n";
        echo "   Start Date: {$subscription->start_date}\n";
        echo "   End Date: {$subscription->end_date}\n";
        echo "   Created: {$subscription->created_at}\n";
        echo "   Updated: {$subscription->updated_at}\n";
        echo "   Auto Renew: " . ($subscription->auto_renew ? 'Yes' : 'No') . "\n";
        
        // Check if this subscription should be active
        $isStatusActive = $subscription->status === 'active';
        $isEndDateFuture = $subscription->end_date ? $subscription->end_date > now() : false;
        $shouldBeActive = $isStatusActive && $isEndDateFuture;
        
        echo "   🔍 Status Check: {$subscription->status} === 'active' = " . ($isStatusActive ? '✅' : '❌') . "\n";
        echo "   🔍 Date Check: {$subscription->end_date} > " . now() . " = " . ($isEndDateFuture ? '✅' : '❌') . "\n";
        echo "   🔍 Should Be Active: " . ($shouldBeActive ? '✅ YES' : '❌ NO') . "\n";
        echo "   ---\n";
    }
    
    echo "\nSTEP 3: Test Subscription::active() Scope\n";
    echo "=======================================\n";
    $activeSubscriptions = \App\Models\Subscription::where('user_id', 3)->active()->get();
    echo "📋 Active subscriptions (using scope): " . $activeSubscriptions->count() . "\n";
    
    if ($activeSubscriptions->count() > 0) {
        foreach ($activeSubscriptions as $sub) {
            echo "✅ Active: ID {$sub->id}, Status: {$sub->status}, End: {$sub->end_date}\n";
        }
    } else {
        echo "❌ No active subscriptions found by scope\n";
    }
    
    echo "\nSTEP 4: Test User::activeSubscription Relationship\n";
    echo "===============================================\n";
    $activeSubscription = $user->activeSubscription;
    if ($activeSubscription) {
        echo "✅ Active subscription found via relationship:\n";
        echo "   ID: {$activeSubscription->id}\n";
        echo "   Type: {$activeSubscription->type}\n";
        echo "   Status: {$activeSubscription->status}\n";
        echo "   End Date: {$activeSubscription->end_date}\n";
        echo "   Days Remaining: " . max(0, now()->diffInDays($activeSubscription->end_date, false)) . "\n";
    } else {
        echo "❌ No active subscription found via relationship\n";
    }
    
    echo "\nSTEP 5: Test User::hasActiveSubscription() Method\n";
    echo "===============================================\n";
    $hasActive = $user->hasActiveSubscription();
    echo "📊 hasActiveSubscription() result: " . ($hasActive ? '✅ TRUE' : '❌ FALSE') . "\n";
    
    echo "\nSTEP 6: Test AccessControlService\n";
    echo "===============================\n";
    $accessControlService = app(\App\Services\AccessControlService::class);
    $hasPremiumAccess = $accessControlService->hasPremiumAccess(3);
    echo "📊 AccessControlService::hasPremiumAccess(3): " . ($hasPremiumAccess ? '✅ TRUE' : '❌ FALSE') . "\n";
    
    $accessLevel = $accessControlService->getUserAccessLevel(3);
    echo "📊 AccessControlService::getUserAccessLevel(3):\n";
    echo "   Level: {$accessLevel['level']}\n";
    echo "   Has Premium: " . ($accessLevel['has_premium'] ? '✅ TRUE' : '❌ FALSE') . "\n";
    echo "   Subscription: " . ($accessLevel['subscription'] ? 'Present' : 'None') . "\n";
    echo "   Expires At: {$accessLevel['expires_at']}\n";
    echo "   Days Remaining: {$accessLevel['days_remaining']}\n";
    
    echo "\nSTEP 7: Test AuthController Profile Method\n";
    echo "========================================\n";
    $authController = new \App\Http\Controllers\Api\AuthController();
    
    // Create a mock request with the user
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $response = $authController->profile($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
        $premiumInfo = $responseData['data']['premium'];
        echo "📱 API Response Premium Info:\n";
        echo "   Is Premium: " . ($premiumInfo['is_premium'] ? '✅ TRUE' : '❌ FALSE') . "\n";
        echo "   Status: {$premiumInfo['subscription_status']}\n";
        echo "   Type: {$premiumInfo['subscription_type']}\n";
        echo "   End Date: {$premiumInfo['subscription_end_date']}\n";
        echo "   Days Remaining: {$premiumInfo['days_remaining']}\n";
    } else {
        echo "❌ API response failed\n";
    }
    
    echo "\nSTEP 8: Manual Database Query Check\n";
    echo "==================================\n";
    $manualQuery = \DB::table('subscriptions')
        ->where('user_id', 3)
        ->where('status', 'active')
        ->where('end_date', '>', now())
        ->get();
    
    echo "📊 Manual query result: " . $manualQuery->count() . " active subscriptions\n";
    foreach ($manualQuery as $sub) {
        echo "   ID: {$sub->id}, Status: {$sub->status}, End: {$sub->end_date}\n";
    }
    
    echo "\nSTEP 9: Summary and Diagnosis\n";
    echo "============================\n";
    echo "🔍 Analysis:\n";
    echo "   - Total subscriptions: " . $subscriptions->count() . "\n";
    echo "   - Active by scope: " . $activeSubscriptions->count() . "\n";
    echo "   - Active by relationship: " . ($activeSubscription ? '1' : '0') . "\n";
    echo "   - hasActiveSubscription(): " . ($hasActive ? 'TRUE' : 'FALSE') . "\n";
    echo "   - AccessControlService: " . ($hasPremiumAccess ? 'TRUE' : 'FALSE') . "\n";
    echo "   - API Response: " . ($responseData['success'] && $responseData['data']['premium']['is_premium'] ? 'TRUE' : 'FALSE') . "\n";
    
    if ($subscriptions->count() > 0 && !$hasActive) {
        echo "\n🚨 ISSUE IDENTIFIED: User has subscriptions but none are detected as active!\n";
        echo "   Possible causes:\n";
        echo "   1. Subscription status is not 'active'\n";
        echo "   2. End date is in the past\n";
        echo "   3. End date is null\n";
        echo "   4. Database timezone issues\n";
        echo "   5. Model relationship issues\n";
    } elseif ($subscriptions->count() == 0) {
        echo "\n🚨 ISSUE IDENTIFIED: User has no subscriptions at all!\n";
    } else {
        echo "\n✅ All checks passed - user should be premium!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
