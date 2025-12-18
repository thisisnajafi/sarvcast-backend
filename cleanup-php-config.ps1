# PowerShell script to clean up duplicate PHP configuration entries
Write-Host "Cleaning up duplicate PHP configuration entries..." -ForegroundColor Green

$phpIniPath = "C:\xampp\php\php.ini"

# Read current configuration
$config = Get-Content $phpIniPath

# Define settings to clean up
$settingsToClean = @(
    'max_file_uploads',
    'post_max_size', 
    'upload_max_filesize',
    'memory_limit',
    'max_execution_time',
    'max_input_time',
    'max_input_vars'
)

Write-Host "Removing duplicate entries..." -ForegroundColor Yellow

foreach ($setting in $settingsToClean) {
    # Find all lines with this setting
    $lines = $config | Where-Object { $_ -match "^$setting\s*=" }
    
    if ($lines.Count -gt 1) {
        Write-Host "Found $($lines.Count) entries for $setting" -ForegroundColor Yellow
        
        # Keep only the last occurrence
        $lastLine = $lines[-1]
        $config = $config | Where-Object { $_ -notmatch "^$setting\s*=" }
        $config += $lastLine
        
        Write-Host "Cleaned up $setting" -ForegroundColor Green
    }
}

# Write cleaned configuration
$config | Set-Content $phpIniPath -Encoding UTF8

Write-Host ""
Write-Host "PHP configuration cleaned successfully!" -ForegroundColor Green
Write-Host "Please restart your web server for changes to take effect." -ForegroundColor Yellow
