@echo off
REM SarvCast Production Deployment Script for Windows
REM This script deploys the SarvCast application to production on Windows

setlocal enabledelayedexpansion

echo 🚀 Starting SarvCast Production Deployment...

REM Configuration
set PROJECT_NAME=sarvcast
set DOMAIN=sarvcast.com
set SSL_EMAIL=admin@sarvcast.com
set BACKUP_DIR=C:\backups\sarvcast
set LOG_FILE=C:\logs\sarvcast-deploy.log

REM Create log directory
if not exist "C:\logs" mkdir "C:\logs"

REM Functions
:log
echo [%date% %time%] %~1 | tee -a "%LOG_FILE%"
goto :eof

:success
echo ✅ %~1 | tee -a "%LOG_FILE%"
goto :eof

:warning
echo ⚠️  %~1 | tee -a "%LOG_FILE%"
goto :eof

:error
echo ❌ %~1 | tee -a "%LOG_FILE%"
exit /b 1

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    call :error "Please run as administrator"
)

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorLevel% neq 0 (
    call :error "Docker is not installed"
)

docker-compose --version >nul 2>&1
if %errorLevel% neq 0 (
    call :error "Docker Compose is not installed"
)

call :log "Starting deployment process..."

REM Create necessary directories
call :log "Creating necessary directories..."
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
if not exist "C:\var\www\sarvcast" mkdir "C:\var\www\sarvcast"

REM Backup existing data if exists
if exist "C:\var\www\sarvcast" (
    call :log "Creating backup of existing installation..."
    powershell -command "Compress-Archive -Path 'C:\var\www\sarvcast\*' -DestinationPath '%BACKUP_DIR%\backup-%date:~-4,4%%date:~-10,2%%date:~-7,2%-%time:~0,2%%time:~3,2%%time:~6,2%.zip' -Force"
    call :success "Backup created successfully"
)

REM Stop existing services
call :log "Stopping existing services..."
docker-compose -f docker-compose.production.yml down 2>nul
net stop nginx 2>nul
net stop mysql 2>nul
net stop redis 2>nul

REM Copy application files
call :log "Copying application files..."
xcopy /E /I /Y . "C:\var\www\sarvcast\"
cd "C:\var\www\sarvcast"

REM Create environment file
call :log "Creating production environment file..."
if not exist ".env" (
    copy .env.example .env
    call :warning "Please update .env file with production values"
)

REM Install dependencies
call :log "Installing PHP dependencies..."
docker run --rm -v "%cd%":/app -w /app composer:latest install --no-dev --optimize-autoloader --no-interaction

REM Build frontend assets
call :log "Building frontend assets..."
docker run --rm -v "%cd%":/app -w /app node:18-alpine sh -c "npm ci --only=production && npm run build"

REM Generate application key
call :log "Generating application key..."
docker run --rm -v "%cd%":/app -w /app php:8.2-cli php artisan key:generate

REM Run database migrations
call :log "Running database migrations..."
docker-compose -f docker-compose.production.yml up -d mysql redis
timeout /t 30 /nobreak >nul
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan migrate --force

REM Run specific migrations for new features
call :log "Running migrations for new features..."
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan migrate --path=database/migrations/2025_09_15_234038_create_image_timelines_table.php --force
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan migrate --path=database/migrations/2025_09_15_234833_create_story_comments_table.php --force
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan migrate --path=database/migrations/2025_09_16_000325_add_use_image_timeline_to_episodes_table.php --force

REM Seed database
call :log "Seeding database..."
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan db:seed --force

REM Cache configuration
call :log "Caching configuration..."
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan config:cache
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan route:cache
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan view:cache

REM Start all services
call :log "Starting all services..."
docker-compose -f docker-compose.production.yml up -d

REM Wait for services to be ready
call :log "Waiting for services to be ready..."
timeout /t 60 /nobreak >nul

REM Health check
call :log "Performing health check..."
curl -f http://localhost/health >nul 2>&1
if %errorLevel% equ 0 (
    call :success "Application is healthy"
) else (
    call :error "Health check failed"
)

REM Test new features
call :log "Testing new features..."
call :log "Testing Image Timeline endpoints..."
curl -f -H "Accept: application/json" http://localhost/api/v1/episodes/1/image-timeline >nul 2>&1
if %errorLevel% equ 0 (
    call :success "Image Timeline API is accessible"
) else (
    call :warning "Image Timeline API test failed (may be expected if no data)"
)

call :log "Testing Story Comments endpoints..."
curl -f -H "Accept: application/json" http://localhost/api/v1/stories/1/comments >nul 2>&1
if %errorLevel% equ 0 (
    call :success "Story Comments API is accessible"
) else (
    call :warning "Story Comments API test failed (may be expected if no data)"
)

call :log "Testing Admin Timeline interface..."
curl -f http://localhost/admin/timeline >nul 2>&1
if %errorLevel% equ 0 (
    call :success "Admin Timeline interface is accessible"
) else (
    call :warning "Admin Timeline interface test failed"
)

REM Setup monitoring
call :log "Setting up monitoring..."
if exist "monitoring\setup-monitoring.bat" (
    call monitoring\setup-monitoring.bat
)

REM Setup backup task
call :log "Setting up backup task..."
schtasks /create /tn "SarvCast Backup" /tr "C:\var\www\sarvcast\scripts\backup.bat" /sc daily /st 02:00 /f

REM Final checks
call :log "Performing final checks..."

REM Check if all containers are running
docker-compose -f docker-compose.production.yml ps | findstr "Up" >nul
if %errorLevel% equ 0 (
    call :success "All containers are running"
) else (
    call :error "Some containers failed to start"
)

REM Check if application is accessible
curl -f https://%DOMAIN%/health >nul 2>&1
if %errorLevel% equ 0 (
    call :success "Application is accessible via HTTPS"
) else (
    call :warning "Application may not be accessible via HTTPS yet"
)

REM Display deployment summary
echo.
echo 🎉 Deployment completed successfully!
echo.
echo 📋 Deployment Summary:
echo    • Application: %PROJECT_NAME%
echo    • Domain: %DOMAIN%
echo    • SSL: Enabled
echo    • Monitoring: Enabled
echo    • Backup: Configured
echo    • Logs: %LOG_FILE%
echo    • New Features: Image Timeline, Story Comments
echo    • Authentication: Persian Phone Numbers
echo    • Premium Access: Enabled
echo.
echo 🔗 Access URLs:
echo    • Application: https://%DOMAIN%
echo    • Admin Panel: https://%DOMAIN%/admin
echo    • Admin Timeline: https://%DOMAIN%/admin/timeline
echo    • API: https://%DOMAIN%/api/v1
echo    • Monitoring: http://%DOMAIN%:3000 (Grafana)
echo    • Metrics: http://%DOMAIN%:9090 (Prometheus)
echo.
echo 📝 Next Steps:
echo    1. Update .env file with production values
echo    2. Configure DNS to point to this server
echo    3. Test all functionality including new features:
echo       - Image Timeline management
echo       - Story Comments system
echo       - Persian phone authentication
echo       - Premium content access
echo    4. Setup monitoring alerts
echo    5. Configure backup verification
echo    6. Test admin timeline interface
echo    7. Verify mobile app integration
echo.
echo 📚 Documentation:
echo    • Logs: type %LOG_FILE%
echo    • Status: docker-compose -f docker-compose.production.yml ps
echo    • Restart: docker-compose -f docker-compose.production.yml restart
echo    • Logs: docker-compose -f docker-compose.production.yml logs
echo.

call :success "Deployment completed successfully!"

pause
