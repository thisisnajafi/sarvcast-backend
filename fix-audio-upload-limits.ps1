# PowerShell script to fix audio upload limit
Write-Host "Fixing audio upload limit for episodes..." -ForegroundColor Green

$phpIniPath = "C:\xampp\php\php.ini"

# Read current configuration
$config = Get-Content $phpIniPath

# Define correct values for audio uploads
$correctValues = @{
    'upload_max_filesize' = '100M'  # Increased from 10M to 100M for audio files
    'post_max_size' = '200M'        # Keep existing value
    'memory_limit' = '1024M'        # Keep existing value
    'max_execution_time' = '300'    # Keep existing value
    'max_input_time' = '300'        # Keep existing value
}

Write-Host "Updating upload limits for audio files..." -ForegroundColor Yellow

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
Write-Host "Audio upload limits updated successfully!" -ForegroundColor Green
Write-Host "Episode audio files can now be up to 100MB." -ForegroundColor Cyan
Write-Host "Please restart your web server for changes to take effect." -ForegroundColor Yellow
