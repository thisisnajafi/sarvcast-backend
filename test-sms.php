<?php

/**
 * Simple SMS Test Script for Melipayamak
 * 
 * This script can be run directly to test the SMS service
 * Usage: php test-sms.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\SmsService;

echo "=== Melipayamak SMS Test ===\n";
echo "Phone: 09136708883\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Configuration check
echo "Configuration:\n";
echo "- Token: " . config('services.melipayamk.token') . "\n";
echo "- Sender: " . config('services.melipayamk.sender') . "\n";
echo "- Base URL: https://rest.payamak-panel.com/api/SendSMS/SendSMS\n\n";

try {
    $smsService = new SmsService();
    
    // Test 1: Send regular SMS
    echo "Test 1: Sending regular SMS...\n";
    $message = "تست سرویس پیامک سروکست - این یک پیام تستی است. زمان: " . date('Y-m-d H:i:s');
    $result = $smsService->sendSms('09136708883', $message);
    
    if ($result['success']) {
        echo "✅ SMS sent successfully!\n";
        echo "Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "❌ SMS sending failed!\n";
        if (isset($result['error'])) {
            echo "Error: " . $result['error'] . "\n";
        }
        if (isset($result['response'])) {
            echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Test 2: Send OTP
    echo "Test 2: Sending OTP...\n";
    $otpResult = $smsService->sendOtp('09136708883', 'verification');
    
    if ($otpResult['success']) {
        echo "✅ OTP sent successfully!\n";
        echo "Message ID: " . ($otpResult['message_id'] ?? 'N/A') . "\n";
        echo "Response: " . json_encode($otpResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "❌ OTP sending failed!\n";
        if (isset($otpResult['error'])) {
            echo "Error: " . $otpResult['error'] . "\n";
        }
        if (isset($otpResult['response'])) {
            echo "Response: " . json_encode($otpResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Test 3: Send payment notification
    echo "Test 3: Sending payment notification...\n";
    $paymentResult = $smsService->sendPaymentNotification('09136708883', 100000, 'IRT');
    
    if ($paymentResult['success']) {
        echo "✅ Payment notification sent successfully!\n";
        echo "Message ID: " . ($paymentResult['message_id'] ?? 'N/A') . "\n";
        echo "Response: " . json_encode($paymentResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "❌ Payment notification sending failed!\n";
        if (isset($paymentResult['error'])) {
            echo "Error: " . $paymentResult['error'] . "\n";
        }
        if (isset($paymentResult['response'])) {
            echo "Response: " . json_encode($paymentResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";
echo "Check the Laravel logs for more details.\n";
