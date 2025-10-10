# PowerShell script to update PHP configuration for timeline uploads
Write-Host "Setting up PHP configuration for SarvCast Timeline Uploads..." -ForegroundColor Green
Write-Host ""

# Get PHP configuration file path
$phpIniPath = "C:\xampp\php\php.ini"
if (-not (Test-Path $phpIniPath)) {
    Write-Host "ERROR: PHP configuration file not found at: $phpIniPath" -ForegroundColor Red
    Write-Host "Please check your PHP installation." -ForegroundColor Yellow
    exit 1
}

Write-Host "Found PHP configuration file: $phpIniPath" -ForegroundColor Green

# Backup original file
$backupPath = "$phpIniPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
Copy-Item $phpIniPath $backupPath
Write-Host "Backup created: $backupPath" -ForegroundColor Green

# Read current configuration
$config = Get-Content $phpIniPath

# Update configuration values
$updates = @{
    'max_file_uploads' = '300'
    'post_max_size' = '200M'
    'upload_max_filesize' = '10M'
    'memory_limit' = '1024M'
    'max_execution_time' = '300'
    'max_input_time' = '300'
    'max_input_vars' = '5000'
}

Write-Host ""
Write-Host "Updating PHP configuration..." -ForegroundColor Yellow

foreach ($setting in $updates.Keys) {
    $value = $updates[$setting]
    $pattern = "^$setting\s*="
    
    if ($config -match $pattern) {
        $config = $config -replace $pattern, "$setting = $value"
        Write-Host "Updated $setting = $value" -ForegroundColor Green
    } else {
        # Add new setting if it doesn't exist
        $config += "$setting = $value"
        Write-Host "Added $setting = $value" -ForegroundColor Cyan
    }
}

# Write updated configuration
$config | Set-Content $phpIniPath -Encoding UTF8

Write-Host ""
Write-Host "PHP configuration updated successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT: You need to restart your web server (Apache/Nginx) for changes to take effect." -ForegroundColor Yellow
Write-Host ""
Write-Host "Updated settings:" -ForegroundColor Cyan
Write-Host "- max_file_uploads = 300" -ForegroundColor White
Write-Host "- post_max_size = 200M" -ForegroundColor White
Write-Host "- upload_max_filesize = 10M" -ForegroundColor White
Write-Host "- memory_limit = 1024M" -ForegroundColor White
Write-Host "- max_execution_time = 300" -ForegroundColor White
Write-Host "- max_input_time = 300" -ForegroundColor White
Write-Host "- max_input_vars = 5000" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
