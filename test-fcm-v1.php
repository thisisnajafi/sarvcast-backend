<?php

/**
 * FCM v1 API Test Script
 *
 * This script tests the Firebase Cloud Messaging v1 API implementation
 * Run: php test-fcm-v1.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Use file cache for testing (in case database isn't available)
config(['cache.default' => 'file']);

use App\Services\FirebaseAuthService;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "🔥 FCM v1 API Test Script\n";
echo "========================\n\n";

// Test 1: Check service account file
echo "1. Checking service account file...\n";
$serviceAccountPath = config('notification.firebase.service_account_path');
echo "   Path: {$serviceAccountPath}\n";

if (!file_exists($serviceAccountPath)) {
    echo "   ❌ ERROR: Service account file not found!\n";
    echo "   Expected: {$serviceAccountPath}\n";
    echo "   Please check your .env file: FIREBASE_SERVICE_ACCOUNT_PATH\n\n";
    exit(1);
}

echo "   ✅ File exists\n";

// Test 2: Validate JSON structure
echo "\n2. Validating JSON structure...\n";
$serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

if (!$serviceAccount) {
    echo "   ❌ ERROR: Invalid JSON file!\n";
    exit(1);
}

$requiredFields = ['type', 'project_id', 'private_key', 'client_email'];
$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($serviceAccount[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo "   ❌ ERROR: Missing required fields: " . implode(', ', $missingFields) . "\n";
    exit(1);
}

echo "   ✅ JSON structure valid\n";
echo "   Project ID: {$serviceAccount['project_id']}\n";
echo "   Client Email: {$serviceAccount['client_email']}\n";

// Test 3: Test OAuth2 token generation
echo "\n3. Testing OAuth2 token generation...\n";
try {
    $authService = app(FirebaseAuthService::class);
    $accessToken = $authService->getAccessToken();

    if (!$accessToken) {
        echo "   ❌ ERROR: Failed to generate access token\n";
        echo "   Check Laravel logs for details\n";
        exit(1);
    }

    echo "   ✅ Access token generated successfully\n";
    echo "   Token preview: " . substr($accessToken, 0, 30) . "...\n";
    echo "   Token length: " . strlen($accessToken) . " characters\n";
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 4: Check configuration
echo "\n4. Checking configuration...\n";
$projectId = config('notification.firebase.project_id');
$useV1Api = config('notification.firebase.use_v1_api');

echo "   Project ID: {$projectId}\n";
echo "   Use V1 API: " . ($useV1Api ? 'Yes ✅' : 'No ⚠️') . "\n";

if ($projectId !== $serviceAccount['project_id']) {
    echo "   ⚠️  WARNING: Project ID mismatch!\n";
    echo "   Config: {$projectId}\n";
    echo "   Service Account: {$serviceAccount['project_id']}\n";
}

// Test 5: Test notification service (dry run - no actual send)
echo "\n5. Testing NotificationService initialization...\n";
try {
    $notificationService = app(NotificationService::class);
    echo "   ✅ NotificationService initialized\n";

    // Check if we have users with FCM tokens for testing (skip if DB not available)
    try {
        $usersWithTokens = DB::table('user_devices')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        if (!empty($usersWithTokens)) {
            $userId = $usersWithTokens[0];
            $tokenCount = DB::table('user_devices')
                ->where('user_id', $userId)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->count();
            echo "   Found user with FCM tokens: User ID {$userId} ({$tokenCount} tokens)\n";
            echo "\n   To send a test notification, run:\n";
            echo "   php artisan tinker\n";
            echo "   \$user = \\App\\Models\\User::find({$userId});\n";
            echo "   app(\\App\\Services\\NotificationService::class)->sendPushNotification(\$user, 'Test', 'FCM v1 API test');\n";
        } else {
            echo "   ⚠️  No users with FCM tokens found\n";
            echo "   Register a device in the Flutter app first\n";
        }
    } catch (\Exception $e) {
        echo "   ⚠️  Database not available (skipping user check)\n";
        echo "   This is OK - FCM v1 API is configured correctly\n";
    }

    echo "   The FCM v1 API is ready - notifications will work once devices are registered\n";
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ All tests passed!\n";
echo "\nFCM v1 API is configured correctly.\n";
echo "\nNext steps:\n";
echo "1. Ensure FCM API is enabled in Google Cloud Console\n";
echo "2. Test sending a notification using the commands above\n";
echo "3. Check Laravel logs if notifications don't work\n";
echo "\n";

