<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\SmsService;
use Melipayamak\MelipayamakApi;

echo "=== Final Test: Corrected Melipayamak Pattern Implementation ===\n\n";

try {
    // Configuration
    $username = config('melipayamak.username');
    $password = config('melipayamak.password');
    $patternId = 372382;
    $testPhoneNumber = '09339487801';
    $testOtpCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    echo "🔧 Configuration:\n";
    echo "Username: {$username}\n";
    echo "Password: " . ($password ? '***SET***' : 'NOT SET') . "\n";
    echo "Pattern ID: {$patternId}\n";
    echo "Phone: {$testPhoneNumber}\n";
    echo "OTP Code: {$testOtpCode}\n\n";

    // Test 1: Direct GitHub Package Method (String Parameter)
    echo "=== Test 1: Direct GitHub Package Method ===\n";
    echo "🔌 Using GitHub package with string parameter...\n";
    $api = new MelipayamakApi($username, $password);
    $smsRest = $api->sms('rest');

    // Send SMS using string parameter (not array)
    echo "📤 Sending pattern SMS with string parameter...\n";
    echo "Parameters: text='{$testOtpCode}', to={$testPhoneNumber}, bodyId={$patternId}\n\n";

    $response = $smsRest->sendByBaseNumber($testOtpCode, $testPhoneNumber, $patternId);

    echo "📊 GitHub Package Response:\n";
    echo "Raw Response: {$response}\n\n";

    $result = json_decode($response);

    if ($result) {
        echo "Parsed Result:\n";
        echo "Value: " . ($result->Value ?? 'N/A') . "\n";
        echo "RetStatus: " . ($result->RetStatus ?? 'N/A') . "\n";
        echo "StrRetStatus: " . ($result->StrRetStatus ?? 'N/A') . "\n";

        if (isset($result->RetStatus) && $result->RetStatus == 1) {
            echo "\n✅ GitHub Package method SUCCESS!\n";
        } else {
            echo "\n❌ GitHub Package method FAILED!\n";
        }
    }

    // Test 2: Procedural PHP Method (cURL)
    echo "\n=== Test 2: Procedural PHP Method (cURL) ===\n";
    echo "🔌 Using procedural PHP cURL method...\n";

    $data = array(
        'username' => $username,
        'password' => $password,
        'text' => $testOtpCode,
        'to' => $testPhoneNumber,
        'bodyId' => $patternId
    );

    $post_data = http_build_query($data);
    $handle = curl_init('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber');

    curl_setopt($handle, CURLOPT_HTTPHEADER, array(
        'content-type' => 'application/x-www-form-urlencoded'
    ));
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);

    $curlResponse = curl_exec($handle);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $curlError = curl_error($handle);
    curl_close($handle);

    echo "📊 Procedural PHP Response:\n";
    echo "Raw Response: {$curlResponse}\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "cURL Error: " . ($curlError ?: 'None') . "\n\n";

    $curlResult = json_decode($curlResponse);

    if ($curlResult) {
        echo "Parsed Result:\n";
        echo "Value: " . ($curlResult->Value ?? 'N/A') . "\n";
        echo "RetStatus: " . ($curlResult->RetStatus ?? 'N/A') . "\n";
        echo "StrRetStatus: " . ($curlResult->StrRetStatus ?? 'N/A') . "\n";

        if (isset($curlResult->RetStatus) && $curlResult->RetStatus == 1) {
            echo "\n✅ Procedural PHP method SUCCESS!\n";
        } else {
            echo "\n❌ Procedural PHP method FAILED!\n";
        }
    }

    // Test 3: Using SmsService (Our Implementation)
    echo "\n=== Test 3: Using SmsService (Our Implementation) ===\n";
    $smsService = new SmsService();

    // Test the sendOtpWithTemplate method
    $reflection = new ReflectionClass($smsService);
    $sendOtpWithTemplateMethod = $reflection->getMethod('sendOtpWithTemplate');
    $sendOtpWithTemplateMethod->setAccessible(true);

    $serviceResult = $sendOtpWithTemplateMethod->invoke($smsService, $testPhoneNumber, $testOtpCode, 'verification');

    echo "Success: " . ($serviceResult['success'] ? '✅ YES' : '❌ NO') . "\n";
    echo "Method: " . ($serviceResult['method'] ?? 'unknown') . "\n";

    if ($serviceResult['success']) {
        echo "Message ID: " . ($serviceResult['message_id'] ?? 'N/A') . "\n";
        echo "\n✅ SmsService SUCCESS!\n";
    } else {
        echo "Error: " . ($serviceResult['error'] ?? 'Unknown error') . "\n";
        if (isset($serviceResult['response'])) {
            echo "Response Code: " . ($serviceResult['response']->Value ?? 'N/A') . "\n";
            echo "Response Status: " . ($serviceResult['response']->RetStatus ?? 'N/A') . "\n";
            echo "Response Message: " . ($serviceResult['response']->StrRetStatus ?? 'N/A') . "\n";
        }
        echo "\n❌ SmsService FAILED!\n";
    }

    // Summary
    echo "\n=== Final Test Summary ===\n";
    echo "📱 Phone Number: {$testPhoneNumber}\n";
    echo "🔢 Test OTP Code: {$testOtpCode}\n";
    echo "📊 GitHub Package: " . (($result && isset($result->RetStatus) && $result->RetStatus == 1) ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    echo "📊 Procedural PHP: " . (($curlResult && isset($curlResult->RetStatus) && $curlResult->RetStatus == 1) ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    echo "📊 SmsService: " . ($serviceResult['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";

    $anySuccess = ($result && isset($result->RetStatus) && $result->RetStatus == 1) ||
                  ($curlResult && isset($curlResult->RetStatus) && $curlResult->RetStatus == 1) ||
                  $serviceResult['success'];

    if ($anySuccess) {
        echo "\n✅ At least one method is working!\n";
        echo "📱 Check the phone {$testPhoneNumber} for the OTP message.\n";
        echo "🔢 Expected OTP in message: {$testOtpCode}\n";
    } else {
        echo "\n❌ All methods failed. Pattern code 372382 may not be activated in your Melipayamak account.\n";
        echo "💡 Contact Melipayamak support to activate pattern code 372382.\n";
    }

} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Final Test Completed ===\n";
