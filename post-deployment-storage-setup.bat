@echo off
REM SarvCast Post-Deployment Storage Setup Script (Windows)
REM Run this script on your production server after deployment

echo 🚀 Starting SarvCast post-deployment storage setup...

REM Navigate to the project directory
cd /d "E:\1 - laravel\7 - SarvCast\sarvcast"

REM Create public images directories
echo 📁 Creating public images directories...
if not exist "public\images\categories" mkdir "public\images\categories"
if not exist "public\images\stories" mkdir "public\images\stories"
if not exist "public\images\episodes" mkdir "public\images\episodes"
if not exist "public\images\people" mkdir "public\images\people"
if not exist "public\images\users" mkdir "public\images\users"
if not exist "public\images\playlists" mkdir "public\images\playlists"
if not exist "public\images\timeline" mkdir "public\images\timeline"

REM Set proper permissions (Windows)
echo 🔐 Setting public images permissions...
icacls public\images /grant Everyone:F /T

REM Clear caches
echo 🧹 Clearing caches...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

REM Test public images access
echo 🧪 Testing public images access...
if exist "public\images" (
    echo ✅ Public images directory exists
) else (
    echo ❌ Public images directory missing
    pause
    exit /b 1
)

REM Create test file
echo 📝 Creating test file...
echo test > public\images\test.txt

REM Check if test file is accessible
if exist "public\images\test.txt" (
    echo ✅ Public images access working
    del public\images\test.txt
) else (
    echo ❌ Public images access not working
    pause
    exit /b 1
)

echo 🎉 SarvCast storage setup completed successfully!
echo.
echo 📋 What was done:
echo   ✅ Public images directories created
echo   ✅ Permissions set correctly
echo   ✅ Caches cleared
echo   ✅ Public images access verified
echo.
echo 🔧 Next steps:
echo   1. Test image uploads through the admin panel
echo   2. Verify images are accessible via direct URLs
echo   3. Check that images display correctly in the application
echo.
pause
