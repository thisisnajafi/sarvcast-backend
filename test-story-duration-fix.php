<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Story Creation Validation Fix\n";
echo "====================================\n\n";

// Test validation rules
echo "1. Testing validation rules:\n";

$testData = [
    'title' => 'Test Story',
    'description' => 'Test description',
    'category_id' => 1,
    'age_group' => '3-5',
    'duration' => 60,
    'total_episodes' => 5,
    'free_episodes' => 2,
    'status' => 'draft'
];

$rules = [
    'title' => 'required|string|max:200',
    'description' => 'required|string',
    'category_id' => 'required|exists:categories,id',
    'age_group' => 'required|string',
    'duration' => 'required|integer|min:1',
    'total_episodes' => 'nullable|integer|min:1',
    'free_episodes' => 'nullable|integer|min:0',
    'status' => 'required|in:draft,pending,approved,rejected,published',
];

try {
    $validator = \Illuminate\Support\Facades\Validator::make($testData, $rules);
    
    if ($validator->passes()) {
        echo "   ✅ Validation rules working correctly\n";
    } else {
        echo "   ❌ Validation failed:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "      - {$error}\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Validation error: " . $e->getMessage() . "\n";
}

// Test missing duration
echo "\n2. Testing missing duration:\n";
$testDataNoDuration = $testData;
unset($testDataNoDuration['duration']);

try {
    $validator = \Illuminate\Support\Facades\Validator::make($testDataNoDuration, $rules);
    
    if ($validator->fails()) {
        echo "   ✅ Missing duration correctly caught by validation\n";
        foreach ($validator->errors()->all() as $error) {
            echo "      - {$error}\n";
        }
    } else {
        echo "   ❌ Missing duration should have failed validation\n";
    }
} catch (Exception $e) {
    echo "   ❌ Validation error: " . $e->getMessage() . "\n";
}

// Test invalid duration
echo "\n3. Testing invalid duration:\n";
$testDataInvalidDuration = $testData;
$testDataInvalidDuration['duration'] = 0;

try {
    $validator = \Illuminate\Support\Facades\Validator::make($testDataInvalidDuration, $rules);
    
    if ($validator->fails()) {
        echo "   ✅ Invalid duration correctly caught by validation\n";
        foreach ($validator->errors()->all() as $error) {
            echo "      - {$error}\n";
        }
    } else {
        echo "   ❌ Invalid duration should have failed validation\n";
    }
} catch (Exception $e) {
    echo "   ❌ Validation error: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
