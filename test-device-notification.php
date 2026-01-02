<?php

/**
 * Test notification to specific device
 * Usage: php test-device-notification.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

$phoneNumber = '09136708883';
$deviceId = 'AE3A.240806.043';

echo "🔔 Test Notification to Specific Device\n";
echo "========================================\n\n";

// Find user
echo "1. Finding user with phone: {$phoneNumber}\n";
$user = User::findByPhoneNumber($phoneNumber);

if (!$user) {
    echo "   ❌ User not found!\n";
    exit(1);
}

echo "   ✅ User found: {$user->first_name} {$user->last_name} (ID: {$user->id})\n\n";

// Check devices
echo "2. Checking registered devices...\n";
$devices = DB::table('user_devices')
    ->where('user_id', $user->id)
    ->get();

if ($devices->isEmpty()) {
    echo "   ⚠️  No devices registered for this user\n";
    echo "   The user needs to open the app and log in to register their device\n";
    exit(1);
}

echo "   Found {$devices->count()} device(s):\n";
foreach ($devices as $device) {
    $hasFcm = !empty($device->fcm_token);
    $fcmPreview = $hasFcm ? substr($device->fcm_token, 0, 30) . '...' : 'None';
    echo "   - Device ID: {$device->device_id}\n";
    echo "     Type: {$device->device_type}\n";
    echo "     Model: {$device->device_model}\n";
    echo "     FCM Token: {$fcmPreview}\n";
    echo "     Last Active: {$device->last_active}\n";
    echo "\n";
}

// Check if specific device exists
$targetDevice = $devices->firstWhere('device_id', $deviceId);

if (!$targetDevice) {
    echo "   ⚠️  Device ID '{$deviceId}' not found!\n";
    echo "   Available device IDs:\n";
    foreach ($devices as $device) {
        echo "     - {$device->device_id}\n";
    }
    echo "\n";
    echo "   Proceeding to send notification to all devices for this user...\n\n";
} else {
    echo "   ✅ Target device found!\n";
    if (empty($targetDevice->fcm_token)) {
        echo "   ⚠️  Device has no FCM token registered\n";
        echo "   The user needs to open the app to register their FCM token\n";
        exit(1);
    }
    echo "   FCM Token: " . substr($targetDevice->fcm_token, 0, 50) . "...\n\n";
}

// Send notification
echo "3. Sending test notification...\n";
$title = 'تست اعلان';
$body = 'این یک اعلان تستی برای دستگاه شما است';

try {
    $notificationService = app(NotificationService::class);
    
    // Note: NotificationService sends to all devices for a user
    // If you want to send to a specific device only, we'd need to modify the service
    $result = $notificationService->sendPushNotification(
        $user,
        $title,
        $body,
        [
            'type' => 'test',
            'device_id' => $deviceId,
            'timestamp' => now()->toIso8601String(),
        ]
    );

    if ($result) {
        echo "   ✅ Notification sent successfully!\n";
        echo "   Title: {$title}\n";
        echo "   Body: {$body}\n";
        echo "\n";
        echo "   Note: Notification was sent to ALL registered devices for this user.\n";
        echo "   If you need to send to a specific device only, the service needs modification.\n";
    } else {
        echo "   ❌ Failed to send notification\n";
        echo "   Check Laravel logs for details\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Test completed!\n";

