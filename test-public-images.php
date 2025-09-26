<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing public images URL generation...\n\n";

// Test the public images URL generation
$testPath = 'categories/test-image.webp';
$baseUrl = config('app.url');
$fullUrl = $baseUrl . '/images/' . $testPath;

echo "Base URL: {$baseUrl}\n";
echo "Test path: {$testPath}\n";
echo "Generated URL: {$fullUrl}\n\n";

// Test if file exists in public
$publicPath = public_path('images/' . $testPath);
echo "Public path: {$publicPath}\n";
echo "Public file exists: " . (file_exists($publicPath) ? 'Yes' : 'No') . "\n\n";

// Test the HasImageUrl trait
use App\Traits\HasImageUrl;

class TestModel {
    use HasImageUrl;
    
    public function testImageUrl($path) {
        return $this->getImageUrlFromPath($path);
    }
}

$testModel = new TestModel();
$generatedUrl = $testModel->testImageUrl($testPath);

echo "Trait generated URL: {$generatedUrl}\n\n";

// Create a test file
echo "Creating test file...\n";
file_put_contents($publicPath, "test image content");

// Test if file is accessible
if (file_exists($publicPath)) {
    echo "✅ Test file created successfully\n";
    echo "File content: " . file_get_contents($publicPath) . "\n";
    
    // Clean up
    unlink($publicPath);
    echo "✅ Test file cleaned up\n";
} else {
    echo "❌ Failed to create test file\n";
}

echo "\nTest completed!\n";
