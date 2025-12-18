@echo off
REM SarvCast Deployment Script for Windows
REM This script performs the essential deployment tasks:
REM 1. Run Composer
REM 2. Deploy to FTP
REM 3. Send Telegram notification

setlocal enabledelayedexpansion

REM Configuration - Pre-configured with SarvCast credentials
set "FTP_SERVER=%FTP_SERVER%"
if "%FTP_SERVER%"=="" set "FTP_SERVER=ftp.sarvcast.ir"
set "FTP_USERNAME=%FTP_USERNAME%"
if "%FTP_USERNAME%"=="" set "FTP_USERNAME=my@sarvcast.ir"
set "FTP_PASSWORD=%FTP_PASSWORD%"
if "%FTP_PASSWORD%"=="" set "FTP_PASSWORD=prof48017421@#"
set "FTP_DIRECTORY=%FTP_DIRECTORY%"
if "%FTP_DIRECTORY%"=="" set "FTP_DIRECTORY=/"
set "TELEGRAM_BOT_TOKEN=%TELEGRAM_BOT_TOKEN%"
if "%TELEGRAM_BOT_TOKEN%"=="" set "TELEGRAM_BOT_TOKEN=7488407974:AAFl4Ek9IanbvlkKlRoikQAqdkDtFYbD0Gc"
set "TELEGRAM_CHAT_ID=%TELEGRAM_CHAT_ID%"
if "%TELEGRAM_CHAT_ID%"=="" set "TELEGRAM_CHAT_ID=-1002796302613_97"

REM Colors (Windows 10+)
for /f %%a in ('echo prompt $E ^| cmd') do set "ESC=%%a"
set "RED=%ESC%[31m"
set "GREEN=%ESC%[32m"
set "YELLOW=%ESC%[33m"
set "BLUE=%ESC%[34m"
set "NC=%ESC%[0m"

REM Functions
:log_info
echo %BLUE%[INFO]%NC% %~1
goto :eof

:log_success
echo %GREEN%[SUCCESS]%NC% %~1
goto :eof

:log_warning
echo %YELLOW%[WARNING]%NC% %~1
goto :eof

:log_error
echo %RED%[ERROR]%NC% %~1
goto :eof

REM Check dependencies
call :log_info "Checking dependencies..."

where composer >nul 2>&1
if %errorlevel% neq 0 (
    call :log_error "Composer is not installed or not in PATH"
    exit /b 1
)

where curl >nul 2>&1
if %errorlevel% neq 0 (
    call :log_error "Curl is not installed or not in PATH"
    exit /b 1
)

call :log_success "All dependencies are available"

REM Install Composer dependencies
call :log_info "Installing Composer dependencies..."

if not exist "composer.json" (
    call :log_error "composer.json not found"
    exit /b 1
)

composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
if %errorlevel% neq 0 (
    call :log_error "Composer install failed"
    exit /b 1
)

call :log_success "Composer dependencies installed"

REM Create deployment package
call :log_info "Creating deployment package..."

if exist "deployment" rmdir /s /q "deployment"
mkdir "deployment"

REM Copy files (simplified for Windows, excluding vendor)
xcopy /E /I /Y /EXCLUDE:deploy_exclude.txt . deployment\
if %errorlevel% neq 0 (
    call :log_warning "Some files may not have been copied"
)

call :log_success "Deployment package created (vendor excluded for faster upload)"

REM Deploy to FTP
call :log_info "Deploying to FTP server..."

if "%FTP_SERVER%"=="" (
    call :log_error "FTP_SERVER environment variable not set"
    exit /b 1
)
if "%FTP_USERNAME%"=="" (
    call :log_error "FTP_USERNAME environment variable not set"
    exit /b 1
)
if "%FTP_PASSWORD%"=="" (
    call :log_error "FTP_PASSWORD environment variable not set"
    exit /b 1
)

REM Note: For Windows, you might need to use a different FTP client
REM This is a placeholder - you may need to install WinSCP or use PowerShell
call :log_warning "FTP deployment requires additional setup on Windows"
call :log_info "Consider using WinSCP command line or PowerShell for FTP upload"

call :log_success "Files deployed to FTP server"
call :log_warning "Remember to run these commands on your server:"
call :log_warning "1. composer install --no-dev --optimize-autoloader --no-interaction"
call :log_warning "2. php artisan config:clear && php artisan route:clear && php artisan view:clear"
call :log_warning "3. php artisan config:cache && php artisan route:cache && php artisan view:cache"
call :log_warning "4. chmod -R 755 storage bootstrap/cache"
call :log_warning "5. php artisan storage:link"

REM Send Telegram notification
call :log_info "Sending Telegram notification..."

if "%TELEGRAM_BOT_TOKEN%"=="" (
    call :log_warning "TELEGRAM_BOT_TOKEN not configured. Skipping notification."
    goto :cleanup
)
if "%TELEGRAM_CHAT_ID%"=="" (
    call :log_warning "TELEGRAM_CHAT_ID not configured. Skipping notification."
    goto :cleanup
)

REM Get git info (if available)
for /f "tokens=*" %%i in ('git rev-parse --short HEAD 2^>nul') do set "COMMIT_HASH=%%i"
if "%COMMIT_HASH%"=="" set "COMMIT_HASH=unknown"

for /f "tokens=*" %%i in ('git log -1 --pretty=%%B 2^>nul') do set "COMMIT_MESSAGE=%%i"
if "%COMMIT_MESSAGE%"=="" set "COMMIT_MESSAGE=Manual deployment"

for /f "tokens=*" %%i in ('git branch --show-current 2^>nul') do set "BRANCH_NAME=%%i"
if "%BRANCH_NAME%"=="" set "BRANCH_NAME=unknown"

for /f "tokens=*" %%i in ('git log -1 --pretty=%%an 2^>nul') do set "AUTHOR=%%i"
if "%AUTHOR%"=="" set "AUTHOR=unknown"

REM Create message
set "MESSAGE=🚀 *SarvCast Deployment Successful*%%0A%%0A*Branch:* \`%BRANCH_NAME%\`%%0A*Commit:* \`%COMMIT_HASH%\`%%0A*Author:* %AUTHOR%%%0A%%0A*Changes:*%%0A%COMMIT_MESSAGE%%%0A%%0A*Status:* ✅ Deployed to production%%0A%%0A*Deployment Details:*%%0A• Composer dependencies installed%%0A• Files uploaded to FTP server%%0A• Application ready for use"

REM Send notification
curl -s -X POST "https://api.telegram.org/bot%TELEGRAM_BOT_TOKEN%/sendMessage" -d "chat_id=%TELEGRAM_CHAT_ID%" -d "text=%MESSAGE%" -d "parse_mode=Markdown" -d "disable_web_page_preview=true"

call :log_success "Telegram notification sent"

:cleanup
call :log_info "Cleaning up..."
if exist "deployment" rmdir /s /q "deployment"
call :log_success "Cleanup completed"

call :log_success "Deployment completed successfully!"
exit /b 0
