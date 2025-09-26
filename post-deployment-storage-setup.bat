@echo off
REM SarvCast Post-Deployment Storage Setup Script (Windows)
REM Run this script on your production server after deployment

echo 🚀 Starting SarvCast post-deployment storage setup...

REM Navigate to the project directory
cd /d "E:\1 - laravel\7 - SarvCast\sarvcast"

REM Create storage symlink
echo 🔗 Creating storage symlink...
php artisan storage:link --force

REM Set proper permissions (Windows)
echo 🔐 Setting storage permissions...
icacls storage\app\public /grant Everyone:F /T
icacls public\storage /grant Everyone:F /T

REM Clear caches
echo 🧹 Clearing caches...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

REM Test storage access
echo 🧪 Testing storage access...
if exist "public\storage" (
    echo ✅ Storage symlink exists
) else (
    echo ❌ Storage symlink missing
    pause
    exit /b 1
)

if exist "storage\app\public" (
    echo ✅ Storage directory exists
) else (
    echo ❌ Storage directory missing
    pause
    exit /b 1
)

REM Create test file
echo 📝 Creating test file...
echo test > storage\app\public\test.txt

REM Check if test file is accessible
if exist "public\storage\test.txt" (
    echo ✅ Storage access working
    del storage\app\public\test.txt
    del public\storage\test.txt
) else (
    echo ❌ Storage access not working
    pause
    exit /b 1
)

echo 🎉 SarvCast storage setup completed successfully!
echo.
echo 📋 What was done:
echo   ✅ Storage symlink created
echo   ✅ Permissions set correctly
echo   ✅ Caches cleared
echo   ✅ Storage access verified
echo.
echo 🔧 Next steps:
echo   1. Configure your web server (Apache/Nginx) to serve /storage/ directly
echo   2. Test image uploads through the admin panel
echo   3. Verify images are accessible via direct URLs
echo.
pause
