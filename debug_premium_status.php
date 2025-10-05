<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Investigating Premium Status Issue for User ID 3...\n\n";

try {
    echo "1. Checking user data...\n";
    $user = \App\Models\User::find(3);
    if ($user) {
        echo "   ✅ User found: {$user->first_name} {$user->last_name}\n";
        echo "   📱 Phone: {$user->phone_number}\n";
        echo "   👤 Role: {$user->role}\n";
        echo "   📊 Status: {$user->status}\n";
    } else {
        echo "   ❌ User not found\n";
        exit;
    }
    
    echo "\n2. Checking subscriptions for user...\n";
    $subscriptions = \App\Models\Subscription::where('user_id', 3)->get();
    echo "   📋 Total subscriptions: " . $subscriptions->count() . "\n";
    
    foreach ($subscriptions as $subscription) {
        echo "   📄 Subscription ID: {$subscription->id}\n";
        echo "      Type: {$subscription->type}\n";
        echo "      Status: {$subscription->status}\n";
        echo "      Price: {$subscription->price}\n";
        echo "      Start Date: {$subscription->start_date}\n";
        echo "      End Date: {$subscription->end_date}\n";
        echo "      Created: {$subscription->created_at}\n";
        echo "      Updated: {$subscription->updated_at}\n";
        echo "      Auto Renew: " . ($subscription->auto_renew ? 'Yes' : 'No') . "\n";
        echo "      ---\n";
    }
    
    echo "\n3. Checking payments for user...\n";
    $payments = \App\Models\Payment::where('user_id', 3)->get();
    echo "   💳 Total payments: " . $payments->count() . "\n";
    
    foreach ($payments as $payment) {
        echo "   💰 Payment ID: {$payment->id}\n";
        echo "      Amount: {$payment->amount}\n";
        echo "      Status: {$payment->status}\n";
        echo "      Method: {$payment->payment_method}\n";
        echo "      Transaction ID: {$payment->transaction_id}\n";
        echo "      Subscription ID: {$payment->subscription_id}\n";
        echo "      Created: {$payment->created_at}\n";
        echo "      Updated: {$payment->updated_at}\n";
        echo "      ---\n";
    }
    
    echo "\n4. Testing activeSubscription relationship...\n";
    $activeSubscription = $user->activeSubscription;
    if ($activeSubscription) {
        echo "   ✅ Active subscription found:\n";
        echo "      ID: {$activeSubscription->id}\n";
        echo "      Type: {$activeSubscription->type}\n";
        echo "      Status: {$activeSubscription->status}\n";
        echo "      End Date: {$activeSubscription->end_date}\n";
        echo "      Days Remaining: " . max(0, now()->diffInDays($activeSubscription->end_date, false)) . "\n";
    } else {
        echo "   ❌ No active subscription found\n";
    }
    
    echo "\n5. Testing Subscription::active() scope...\n";
    $activeSubscriptions = \App\Models\Subscription::where('user_id', 3)->active()->get();
    echo "   📋 Active subscriptions (using scope): " . $activeSubscriptions->count() . "\n";
    
    foreach ($activeSubscriptions as $sub) {
        echo "   ✅ Active: ID {$sub->id}, Status: {$sub->status}, End: {$sub->end_date}\n";
    }
    
    echo "\n6. Checking subscription status logic...\n";
    $allSubscriptions = \App\Models\Subscription::where('user_id', 3)->get();
    foreach ($allSubscriptions as $sub) {
        $isActive = $sub->status === 'active' && $sub->end_date > now();
        echo "   📊 Subscription {$sub->id}: Status='{$sub->status}', End='{$sub->end_date}', IsActive=" . ($isActive ? 'Yes' : 'No') . "\n";
    }
    
    echo "\n7. Testing AuthController profile method...\n";
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
        echo "   📱 API Response Premium Info:\n";
        echo "      Is Premium: " . ($premiumInfo['is_premium'] ? 'Yes' : 'No') . "\n";
        echo "      Status: {$premiumInfo['subscription_status']}\n";
        echo "      Type: {$premiumInfo['subscription_type']}\n";
        echo "      End Date: {$premiumInfo['subscription_end_date']}\n";
        echo "      Days Remaining: {$premiumInfo['days_remaining']}\n";
    } else {
        echo "   ❌ API response failed\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
