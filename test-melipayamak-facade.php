<?php

/**
 * Melipayamak Facade Test Script
 * 
 * This script demonstrates the new Melipayamak facade usage
 * Usage: php test-melipayamak-facade.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Facades\Melipayamak;
use App\Services\SmsService;

echo "=== Melipayamak Facade Test ===\n";
echo "Target Phone: 09136708883\n";
echo "Sender Number: 50002710008883\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$testPhone = '09136708883';
$testMessage = "تست سرویس پیامک سروکست با Facade - " . date('Y-m-d H:i:s');

// Test 1: Using the new Melipayamak facade
echo "=== Test 1: Melipayamak Facade ===\n";
try {
    $sms = Melipayamak::sms();
    $to = $testPhone;
    $from = '50002710008883';
    $text = $testMessage;
    
    echo "Sending SMS using Melipayamak facade...\n";
    echo "To: {$to}\n";
    echo "From: {$from}\n";
    echo "Message: {$text}\n\n";
    
    $response = $sms->send($to, $from, $text);
    $json = json_decode($response);
    
    echo "Raw Response: " . $response . "\n";
    echo "Decoded Response: " . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($json && isset($json->RetStatus) && $json->RetStatus == 1) {
        echo "✅ SMS sent successfully via facade!\n";
        echo "Message ID: " . ($json->Value ?? 'N/A') . "\n";
    } else {
        echo "❌ SMS sending failed via facade!\n";
        echo "Status: " . ($json->RetStatus ?? 'Unknown') . "\n";
        echo "Message: " . ($json->StrRetStatus ?? 'No message') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception in facade test: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 60) . "\n\n";

// Test 2: Using the updated SmsService
echo "=== Test 2: Updated SmsService ===\n";
try {
    $smsService = new SmsService();
    
    echo "Sending SMS using updated SmsService...\n";
    $result = $smsService->sendSms($testPhone, $testMessage);
    
    if ($result['success']) {
        echo "✅ SMS sent successfully via SmsService!\n";
        echo "Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "❌ SMS sending failed via SmsService!\n";
        if (isset($result['error'])) {
            echo "Error: " . $result['error'] . "\n";
        }
        if (isset($result['response'])) {
            echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception in SmsService test: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 60) . "\n\n";

// Test 3: Direct MelipayamakApi usage
echo "=== Test 3: Direct MelipayamakApi ===\n";
try {
    $username = config('services.melipayamk.token');
    $password = config('services.melipayamk.token');
    
    echo "Using direct MelipayamakApi...\n";
    echo "Username: {$username}\n";
    echo "Password: {$password}\n\n";
    
    $api = new \Melipayamak\MelipayamakApi($username, $password);
    $sms = $api->sms();
    
    $response = $sms->send($testPhone, '50002710008883', $testMessage);
    $json = json_decode($response);
    
    echo "Raw Response: " . $response . "\n";
    
    if ($json && isset($json->RetStatus) && $json->RetStatus == 1) {
        echo "✅ SMS sent successfully via direct API!\n";
        echo "Message ID: " . ($json->Value ?? 'N/A') . "\n";
    } else {
        echo "❌ SMS sending failed via direct API!\n";
        echo "Status: " . ($json->RetStatus ?? 'Unknown') . "\n";
        echo "Message: " . ($json->StrRetStatus ?? 'No message') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception in direct API test: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "All three methods tested:\n";
echo "1. Melipayamak Facade (Melipayamak::sms())\n";
echo "2. Updated SmsService (using Melipayamak library)\n";
echo "3. Direct MelipayamakApi usage\n\n";

echo "If all tests failed with 'UserNameAndPasswordFailed', you need valid credentials.\n";
echo "Update your .env file with correct Melipayamak username and password.\n\n";

echo "=== Test completed at " . date('Y-m-d H:i:s') . " ===\n";
