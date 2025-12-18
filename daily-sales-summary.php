<?php
/**
 * Daily Sales Summary Cron Job Runner
 * This file calls the Laravel telegram:daily-sales-summary command
 * 
 * Place this file in: /home/sarvcs/public_html/my/daily-sales-summary.php
 * Cron job command: php /home/sarvcs/public_html/my/daily-sales-summary.php
 */

// Set the Laravel project path
$laravelPath = '/home/sarvcs/public_html/my/';

// Change to Laravel directory
chdir($laravelPath);

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function
function writeLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($laravelPath . 'storage/logs/cron-runner.log', $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage;
}

// Start logging
writeLog("=== Daily Sales Summary Cron Job Started ===");

try {
    // Check if Laravel exists
    if (!file_exists($laravelPath . 'artisan')) {
        throw new Exception("Laravel artisan file not found at: {$laravelPath}artisan");
    }
    
    writeLog("Laravel project found at: {$laravelPath}");
    
    // Execute the Laravel command
    $command = "php artisan telegram:daily-sales-summary 2>&1";
    writeLog("Executing command: {$command}");
    
    $output = [];
    $returnCode = 0;
    
    exec($command, $output, $returnCode);
    
    // Log the output
    writeLog("Command output:");
    foreach ($output as $line) {
        writeLog("  " . $line);
    }
    
    writeLog("Command return code: {$returnCode}");
    
    if ($returnCode === 0) {
        writeLog("✅ Daily sales summary command executed successfully");
    } else {
        writeLog("❌ Daily sales summary command failed with return code: {$returnCode}");
    }
    
} catch (Exception $e) {
    writeLog("❌ Error: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
} catch (Error $e) {
    writeLog("❌ Fatal Error: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
}

writeLog("=== Daily Sales Summary Cron Job Completed ===");
writeLog(""); // Empty line for readability

// Optional: Send HTTP response (for web access testing)
if (isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $returnCode === 0,
        'message' => $returnCode === 0 ? 'Daily sales summary sent successfully' : 'Daily sales summary failed',
        'timestamp' => date('Y-m-d H:i:s'),
        'output' => $output
    ]);
}
?>
