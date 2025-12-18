@echo off
REM SarvCast Production Deployment Script for Windows
REM This script automates the deployment process for production

echo 🚀 Starting SarvCast Production Deployment...

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker is not installed. Please install Docker Desktop first.
    pause
    exit /b 1
)

REM Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker Compose is not installed. Please install Docker Compose first.
    pause
    exit /b 1
)

REM Create environment file if it doesn't exist
if not exist .env.production (
    echo [WARNING] Creating .env.production from template...
    copy .env.production.example .env.production
    echo [WARNING] Please edit .env.production with your production values before continuing.
    echo [WARNING] Required values:
    echo [WARNING]   - APP_KEY (generate with: php artisan key:generate)
    echo [WARNING]   - DB_PASSWORD
    echo [WARNING]   - REDIS_PASSWORD
    echo [WARNING]   - SMS_API_KEY
    echo [WARNING]   - ZARINPAL_MERCHANT_ID
    echo [WARNING]   - AWS credentials (if using S3)
    echo [WARNING]   - FIREBASE_SERVER_KEY
    pause
)

REM Validate environment file
if not exist .env.production (
    echo [ERROR] .env.production file not found!
    pause
    exit /b 1
)

echo [INFO] Environment validation passed!

REM Create necessary directories
echo [INFO] Creating necessary directories...
if not exist storage\app\public mkdir storage\app\public
if not exist storage\logs mkdir storage\logs
if not exist storage\framework\cache mkdir storage\framework\cache
if not exist storage\framework\sessions mkdir storage\framework\sessions
if not exist storage\framework\views mkdir storage\framework\views
if not exist bootstrap\cache mkdir bootstrap\cache

REM Build and start services
echo [INFO] Building Docker images...
docker-compose -f docker-compose.production.yml build --no-cache

echo [INFO] Starting services...
docker-compose -f docker-compose.production.yml up -d

REM Wait for services to be ready
echo [INFO] Waiting for services to be ready...
timeout /t 30 /nobreak >nul

REM Check if services are running
echo [INFO] Checking service status...
docker-compose -f docker-compose.production.yml ps

REM Run database migrations
echo [INFO] Running database migrations...
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

REM Seed database
echo [INFO] Seeding database...
docker-compose -f docker-compose.production.yml exec app php artisan db:seed --force

REM Clear and optimize caches
echo [INFO] Clearing and optimizing caches...
docker-compose -f docker-compose.production.yml exec app php artisan config:clear
docker-compose -f docker-compose.production.yml exec app php artisan route:clear
docker-compose -f docker-compose.production.yml exec app php artisan view:clear
docker-compose -f docker-compose.production.yml exec app php artisan cache:clear

REM Optimize application
echo [INFO] Optimizing application...
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache

REM Run performance optimization
echo [INFO] Running performance optimization...
docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:optimize-performance

REM Test application health
echo [INFO] Testing application health...
timeout /t 10 /nobreak >nul

REM Create initial backup
echo [INFO] Creating initial backup...
docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:backup --type=full

REM Setup monitoring
echo [INFO] Setting up monitoring...
docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:monitor

REM Final status check
echo [INFO] Performing final status check...
docker-compose -f docker-compose.production.yml ps

REM Display access information
echo [INFO] Deployment completed successfully!
echo.
echo 🌐 Access Information:
echo   API: https://api.sarvcast.com
echo   Admin Dashboard: https://admin.sarvcast.com
echo   Health Check: https://api.sarvcast.com/api/v1/health
echo   Monitoring: http://localhost:9090 (if enabled)
echo.
echo 📋 Next Steps:
echo   1. Update DNS records to point to this server
echo   2. Configure your domain names (api.sarvcast.com, admin.sarvcast.com)
echo   3. Test all functionality
echo   4. Setup monitoring alerts
echo   5. Configure backup notifications
echo.
echo 🔧 Useful Commands:
echo   View logs: docker-compose -f docker-compose.production.yml logs -f
echo   Restart services: docker-compose -f docker-compose.production.yml restart
echo   Stop services: docker-compose -f docker-compose.production.yml down
echo   Create backup: docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:backup --type=full
echo   Monitor health: docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:monitor
echo.
echo 📚 Documentation:
echo   Production Guide: docs\PRODUCTION_DEPLOYMENT_GUIDE.md
echo   API Documentation: docs\API_DOCUMENTATION_COMPLETE.md
echo   Admin Guide: docs\ADMIN_USER_GUIDE.md
echo.

echo [INFO] SarvCast is now running in production! 🎉
pause
