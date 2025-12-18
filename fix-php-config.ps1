# PowerShell script to fix concatenated PHP configuration values
Write-Host "Fixing concatenated PHP configuration values..." -ForegroundColor Green

$phpIniPath = "C:\xampp\php\php.ini"

# Read current configuration
$config = Get-Content $phpIniPath

# Define correct values
$correctValues = @{
    'max_file_uploads' = '300'
    'post_max_size' = '200M'
    'upload_max_filesize' = '10M'
    'memory_limit' = '1024M'
    'max_execution_time' = '300'
    'max_input_time' = '300'
    'max_input_vars' = '5000'
}

Write-Host "Fixing concatenated values..." -ForegroundColor Yellow

# Remove all existing entries for these settings
foreach ($setting in $correctValues.Keys) {
    $config = $config | Where-Object { $_ -notmatch "^$setting\s*=" }
}

# Add correct values
foreach ($setting in $correctValues.Keys) {
    $value = $correctValues[$setting]
    $config += "$setting = $value"
    Write-Host "Set $setting = $value" -ForegroundColor Green
}

# Write fixed configuration
$config | Set-Content $phpIniPath -Encoding UTF8

Write-Host ""
Write-Host "PHP configuration fixed successfully!" -ForegroundColor Green
Write-Host "Please restart your web server for changes to take effect." -ForegroundColor Yellow
