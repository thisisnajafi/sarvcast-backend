@echo off
REM SarvCast Backup Script for Windows
REM This script creates backups of the SarvCast application

setlocal enabledelayedexpansion

echo 🗄️ Starting SarvCast Backup Process...

REM Configuration
set PROJECT_NAME=sarvcast
set BACKUP_DIR=C:\backups\sarvcast
set BACKUP_NAME=sarvcast-backup-%date:~-4,4%%date:~-10,2%%date:~-7,2%-%time:~0,2%%time:~3,2%%time:~6,2%
set LOG_FILE=C:\logs\sarvcast-backup.log

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

REM Check if Docker is running
docker ps >nul 2>&1
if %errorLevel% neq 0 (
    call :error "Docker is not running"
)

REM Check if application directory exists
if not exist "C:\var\www\sarvcast" (
    call :error "Application directory not found"
)

REM Create backup directory
set BACKUP_PATH=%BACKUP_DIR%\%BACKUP_NAME%
if not exist "%BACKUP_PATH%" mkdir "%BACKUP_PATH%"

call :log "Creating database backup..."
REM Database backup (includes new tables: image_timelines, story_comments)
docker-compose -f C:\var\www\sarvcast\docker-compose.production.yml exec -T mysql mysqldump -u sarvcast_user -p%DB_PASSWORD% --single-transaction --routines --triggers sarvcast_production > "%BACKUP_PATH%\database.sql"

if %errorLevel% equ 0 (
    call :success "Database backup completed"
) else (
    call :error "Database backup failed"
)

call :log "Creating application files backup..."
REM Application files backup
powershell -command "Compress-Archive -Path 'C:\var\www\sarvcast\*' -DestinationPath '%BACKUP_PATH%\application.zip' -Exclude @('node_modules', 'vendor', 'storage\logs', 'storage\framework\cache', 'storage\framework\sessions', 'storage\framework\views', '.git', '.env') -Force"

if %errorLevel% equ 0 (
    call :success "Application files backup completed"
) else (
    call :error "Application files backup failed"
)

call :log "Creating storage backup..."
REM Storage backup (includes uploaded files, images, audio)
if exist "C:\var\www\sarvcast\storage" (
    powershell -command "Compress-Archive -Path 'C:\var\www\sarvcast\storage\*' -DestinationPath '%BACKUP_PATH%\storage.zip' -Force"
    if %errorLevel% equ 0 (
        call :success "Storage backup completed"
    ) else (
        call :warning "Storage backup failed"
    )
) else (
    call :warning "Storage directory not found"
)

call :log "Creating Docker volumes backup..."
REM Docker volumes backup
docker run --rm -v sarvcast_mysql_data:/data -v sarvcast_redis_data:/redis -v "%BACKUP_PATH%":/backup alpine tar czf /backup/volumes.tar.gz /data /redis

if %errorLevel% equ 0 (
    call :success "Docker volumes backup completed"
) else (
    call :warning "Docker volumes backup failed"
)

call :log "Creating configuration backup..."
REM Configuration backup
copy "C:\var\www\sarvcast\.env" "%BACKUP_PATH%\.env.backup" 2>nul
copy "C:\var\www\sarvcast\docker-compose.production.yml" "%BACKUP_PATH%\docker-compose.production.yml.backup" 2>nul

if %errorLevel% equ 0 (
    call :success "Configuration backup completed"
) else (
    call :warning "Configuration backup failed"
)

call :log "Creating backup metadata..."
REM Create backup metadata
echo SarvCast Backup Information > "%BACKUP_PATH%\backup-info.txt"
echo ========================== >> "%BACKUP_PATH%\backup-info.txt"
echo. >> "%BACKUP_PATH%\backup-info.txt"
echo Backup Date: %date% %time% >> "%BACKUP_PATH%\backup-info.txt"
echo Backup Name: %BACKUP_NAME% >> "%BACKUP_PATH%\backup-info.txt"
echo Project: %PROJECT_NAME% >> "%BACKUP_PATH%\backup-info.txt"
echo. >> "%BACKUP_PATH%\backup-info.txt"
echo Contents: >> "%BACKUP_PATH%\backup-info.txt"
echo - database.sql (Database dump) >> "%BACKUP_PATH%\backup-info.txt"
echo - application.zip (Application files) >> "%BACKUP_PATH%\backup-info.txt"
echo - storage.zip (Uploaded files) >> "%BACKUP_PATH%\backup-info.txt"
echo - volumes.tar.gz (Docker volumes) >> "%BACKUP_PATH%\backup-info.txt"
echo - .env.backup (Environment configuration) >> "%BACKUP_PATH%\backup-info.txt"
echo - docker-compose.production.yml.backup (Docker configuration) >> "%BACKUP_PATH%\backup-info.txt"
echo. >> "%BACKUP_PATH%\backup-info.txt"
echo New Features Included: >> "%BACKUP_PATH%\backup-info.txt"
echo - Image Timeline system >> "%BACKUP_PATH%\backup-info.txt"
echo - Story Comments system >> "%BACKUP_PATH%\backup-info.txt"
echo - Persian phone authentication >> "%BACKUP_PATH%\backup-info.txt"
echo - Premium content access >> "%BACKUP_PATH%\backup-info.txt"

call :success "Backup metadata created"

call :log "Calculating backup size..."
REM Calculate backup size
for /f %%i in ('powershell -command "(Get-ChildItem '%BACKUP_PATH%' -Recurse | Measure-Object -Property Length -Sum).Sum"') do set BACKUP_SIZE=%%i
set /a BACKUP_SIZE_MB=%BACKUP_SIZE%/1024/1024

call :log "Backup size: %BACKUP_SIZE_MB% MB"

call :log "Cleaning up old backups..."
REM Keep only last 7 backups
for /f "skip=7" %%i in ('dir /b /o-d "%BACKUP_DIR%\sarvcast-backup-*" 2^>nul') do (
    rmdir /s /q "%BACKUP_DIR%\%%i" 2>nul
)

call :log "Verifying backup integrity..."
REM Verify backup files exist
if exist "%BACKUP_PATH%\database.sql" (
    if exist "%BACKUP_PATH%\application.zip" (
        if exist "%BACKUP_PATH%\storage.zip" (
            if exist "%BACKUP_PATH%\volumes.tar.gz" (
                call :success "Backup verification successful"
            ) else (
                call :warning "Volumes backup verification failed"
            )
        ) else (
            call :warning "Storage backup verification failed"
        )
    ) else (
        call :warning "Application backup verification failed"
    )
) else (
    call :warning "Database backup verification failed"
)

REM Display backup summary
echo.
echo 🎉 Backup completed successfully!
echo.
echo 📋 Backup Summary:
echo    • Backup Name: %BACKUP_NAME%
echo    • Backup Path: %BACKUP_PATH%
echo    • Backup Size: %BACKUP_SIZE_MB% MB
echo    • Backup Date: %date% %time%
echo    • New Features: Image Timeline, Story Comments
echo.
echo 📁 Backup Contents:
echo    • Database: database.sql
echo    • Application: application.zip
echo    • Storage: storage.zip
echo    • Volumes: volumes.tar.gz
echo    • Configuration: .env.backup, docker-compose.production.yml.backup
echo    • Metadata: backup-info.txt
echo.
echo 📝 Next Steps:
echo    1. Verify backup integrity
echo    2. Test restore procedure
echo    3. Store backup in secure location
echo    4. Update backup schedule if needed
echo.

call :success "Backup process completed successfully!"

pause
