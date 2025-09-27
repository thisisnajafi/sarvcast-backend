<?php
/**
 * Simple Daily Sales Summary Cron Runner
 * 
 * Usage: php /home/sarvcs/public_html/my/daily-sales-summary.php
 * Cron: 0 0 * * * php /home/sarvcs/public_html/my/daily-sales-summary.php
 */

// Laravel project path
$projectPath = '/home/sarvcs/public_html/my/';

// Log file path
$logFile = $projectPath . 'storage/logs/cron-runner.log';

// Function to write logs
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Start execution
logMessage("Starting daily sales summary cron job");

// Change to Laravel directory
if (!chdir($projectPath)) {
    logMessage("ERROR: Cannot change to project directory: {$projectPath}");
    exit(1);
}

// Check if artisan exists
if (!file_exists('artisan')) {
    logMessage("ERROR: Laravel artisan file not found");
    exit(1);
}

// Execute the command
$command = 'php artisan telegram:daily-sales-summary 2>&1';
logMessage("Executing: {$command}");

$output = [];
$returnVar = 0;
exec($command, $output, $returnVar);

// Log results
logMessage("Return code: {$returnVar}");
logMessage("Output: " . implode(' | ', $output));

if ($returnVar === 0) {
    logMessage("SUCCESS: Daily sales summary sent successfully");
} else {
    logMessage("ERROR: Command failed with return code {$returnVar}");
}

logMessage("Cron job completed");
logMessage("---"); // Separator line
?>
