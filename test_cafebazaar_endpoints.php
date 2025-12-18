<?php

/**
 * CafeBazaar Endpoints Test Script
 *
 * This script verifies that CafeBazaar endpoints are properly configured
 * and can be called (without authentication for basic checks).
 */

echo "=== CafeBazaar Endpoints Test ===\n\n";

// Test 1: Check if routes are registered
echo "1. Checking route registration...\n";

$routesFile = __DIR__ . '/routes/api.php';
if (file_exists($routesFile)) {
    $content = file_get_contents($routesFile);
    if (strpos($content, 'cafebazaar') !== false) {
        echo "✅ CafeBazaar routes found in api.php\n";
    } else {
        echo "❌ CafeBazaar routes NOT found in api.php\n";
    }
} else {
    echo "❌ routes/api.php file not found\n";
}

// Test 2: Check if controller exists
echo "\n2. Checking controller existence...\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/Api/CafeBazaarSubscriptionController.php';
if (file_exists($controllerFile)) {
    echo "✅ CafeBazaarSubscriptionController exists\n";

    $content = file_get_contents($controllerFile);
    $methods = ['verifySubscription', 'getSubscriptionStatus', 'restorePurchases'];
    foreach ($methods as $method) {
        if (strpos($content, "public function {$method}") !== false) {
            echo "✅ Method {$method} found\n";
        } else {
            echo "❌ Method {$method} NOT found\n";
        }
    }
} else {
    echo "❌ CafeBazaarSubscriptionController NOT found\n";
}

// Test 3: Check service existence
echo "\n3. Checking service existence...\n";

$serviceFile = __DIR__ . '/app/Services/CafeBazaarService.php';
if (file_exists($serviceFile)) {
    echo "✅ CafeBazaarService exists\n";

    $content = file_get_contents($serviceFile);
    $methods = ['verifyPurchase', 'verifySubscription', 'acknowledgePurchase', 'mapProductIdToSubscriptionType'];
    foreach ($methods as $method) {
        if (strpos($content, "public function {$method}") !== false) {
            echo "✅ Service method {$method} found\n";
        } else {
            echo "❌ Service method {$method} NOT found\n";
        }
    }
} else {
    echo "❌ CafeBazaarService NOT found\n";
}

// Test 4: Check configuration
echo "\n4. Checking configuration...\n";

$configFile = __DIR__ . '/config/services.php';
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    if (strpos($content, "'cafebazaar' => [") !== false) {
        echo "✅ CafeBazaar configuration found in services.php\n";
    } else {
        echo "❌ CafeBazaar configuration NOT found in services.php\n";
    }
} else {
    echo "❌ config/services.php file not found\n";
}

// Test 5: Check FlavorHelper
echo "\n5. Checking FlavorHelper...\n";

$helperFile = __DIR__ . '/app/Helpers/FlavorHelper.php';
if (file_exists($helperFile)) {
    echo "✅ FlavorHelper exists\n";

    $content = file_get_contents($helperFile);
    if (strpos($content, 'isCafeBazaar') !== false) {
        echo "✅ isCafeBazaar method found\n";
    } else {
        echo "❌ isCafeBazaar method NOT found\n";
    }
} else {
    echo "❌ FlavorHelper NOT found\n";
}

echo "\n=== Test Summary ===\n";
echo "If all checks above are ✅, then CafeBazaar endpoints are properly configured.\n";
echo "Next steps:\n";
echo "1. Set up .env file with CafeBazaar API credentials\n";
echo "2. Test endpoints with authentication using Postman\n";
echo "3. Verify database tables support required fields\n";
echo "\nSee CAFEBAZAAR_ENV_SETUP.md for configuration instructions.\n";

?>
