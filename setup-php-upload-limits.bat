@echo off
echo Setting up PHP configuration for SarvCast Timeline Uploads...
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as administrator - proceeding with configuration...
) else (
    echo WARNING: Not running as administrator. You may need to run this script as admin.
    echo.
)

REM Find PHP configuration file
for /f "tokens=*" %%i in ('php --ini ^| findstr "Loaded Configuration File"') do set PHPINI=%%i
set PHPINI=%PHPINI:Loaded Configuration File: =%

if exist "%PHPINI%" (
    echo Found PHP configuration file: %PHPINI%
    echo.
    
    REM Backup original file
    copy "%PHPINI%" "%PHPINI%.backup.%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%" >nul
    echo Backup created: %PHPINI%.backup
    
    REM Update max_file_uploads
    echo Updating max_file_uploads to 300...
    powershell -Command "(Get-Content '%PHPINI%') -replace 'max_file_uploads = .*', 'max_file_uploads = 300' | Set-Content '%PHPINI%'"
    
    REM Update post_max_size
    echo Updating post_max_size to 200M...
    powershell -Command "(Get-Content '%PHPINI%') -replace 'post_max_size = .*', 'post_max_size = 200M' | Set-Content '%PHPINI%'"
    
    REM Update upload_max_filesize
    echo Updating upload_max_filesize to 10M...
    powershell -Command "(Get-Content '%PHPINI%') -replace 'upload_max_filesize = .*', 'upload_max_filesize = 10M' | Set-Content '%PHPINI%'"
    
    REM Update memory_limit
    echo Updating memory_limit to 1024M...
    powershell -Command "(Get-Content '%PHPINI%') -replace 'memory_limit = .*', 'memory_limit = 1024M' | Set-Content '%PHPINI%'"
    
    REM Update max_execution_time
    echo Updating max_execution_time to 300...
    powershell -Command "(Get-Content '%PHPINI%') -replace 'max_execution_time = .*', 'max_execution_time = 300' | Set-Content '%PHPINI%'"
    
    REM Update max_input_time
    echo Updating max_input_time to 300...
    powershell -Command "(Get-Content '%PHPINI%') -replace 'max_input_time = .*', 'max_input_time = 300' | Set-Content '%PHPINI%'"
    
    REM Update max_input_vars
    echo Updating max_input_vars to 5000...
    powershell -Command "(Get-Content '%PHPINI%') -replace 'max_input_vars = .*', 'max_input_vars = 5000' | Set-Content '%PHPINI%'"
    
    echo.
    echo PHP configuration updated successfully!
    echo.
    echo IMPORTANT: You need to restart your web server (Apache/Nginx) for changes to take effect.
    echo.
    echo Updated settings:
    echo - max_file_uploads = 300
    echo - post_max_size = 200M
    echo - upload_max_filesize = 10M
    echo - memory_limit = 1024M
    echo - max_execution_time = 300
    echo - max_input_time = 300
    echo - max_input_vars = 5000
    echo.
    
) else (
    echo ERROR: PHP configuration file not found at: %PHPINI%
    echo Please check your PHP installation.
)

echo.
echo Press any key to continue...
pause >nul
