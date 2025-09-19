#!/bin/bash

# SarvCast Restore Script
# This script restores the SarvCast application from a backup

set -e

# Configuration
PROJECT_NAME="sarvcast"
BACKUP_DIR="/backups/sarvcast"
LOG_FILE="/var/log/sarvcast-restore.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a $LOG_FILE
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a $LOG_FILE
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a $LOG_FILE
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a $LOG_FILE
    exit 1
}

# Check if backup file is provided
if [ -z "$1" ]; then
    error "Please provide backup file path"
fi

BACKUP_FILE="$1"

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    error "Backup file not found: $BACKUP_FILE"
fi

log "Starting restore process..."
log "Backup file: $BACKUP_FILE"

# Verify backup file
log "Verifying backup file..."
if tar -tzf "$BACKUP_FILE" > /dev/null 2>&1; then
    success "Backup file is valid"
else
    error "Backup file is corrupted or invalid"
fi

# Extract backup
log "Extracting backup..."
EXTRACT_DIR="/tmp/sarvcast_restore_$(date +%Y%m%d_%H%M%S)"
mkdir -p $EXTRACT_DIR
tar -xzf "$BACKUP_FILE" -C $EXTRACT_DIR

# Find the backup directory inside the extracted files
BACKUP_NAME=$(ls $EXTRACT_DIR | head -n 1)
RESTORE_PATH="$EXTRACT_DIR/$BACKUP_NAME"

if [ ! -d "$RESTORE_PATH" ]; then
    error "Invalid backup structure"
fi

log "Backup extracted to: $RESTORE_PATH"

# Check if metadata exists
if [ -f "$RESTORE_PATH/metadata.json" ]; then
    log "Backup metadata found:"
    cat "$RESTORE_PATH/metadata.json" | jq '.' 2>/dev/null || cat "$RESTORE_PATH/metadata.json"
else
    warning "No metadata found in backup"
fi

# Confirm restore
echo ""
echo "⚠️  WARNING: This will overwrite the current installation!"
echo "   • Current data will be lost"
echo "   • Make sure you have a current backup"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    log "Restore cancelled by user"
    rm -rf $EXTRACT_DIR
    exit 0
fi

# Stop services
log "Stopping services..."
docker-compose -f /var/www/sarvcast/docker-compose.production.yml down || true
systemctl stop nginx || true
systemctl stop mysql || true
systemctl stop redis || true

# Create current backup before restore
log "Creating current backup before restore..."
CURRENT_BACKUP="/tmp/sarvcast_current_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
tar -czf "$CURRENT_BACKUP" /var/www/sarvcast
success "Current backup created: $CURRENT_BACKUP"

# Restore application files
log "Restoring application files..."
if [ -f "$RESTORE_PATH/application.tar.gz" ]; then
    rm -rf /var/www/sarvcast
    mkdir -p /var/www/sarvcast
    tar -xzf "$RESTORE_PATH/application.tar.gz" -C /
    success "Application files restored"
else
    error "Application files not found in backup"
fi

# Restore storage
log "Restoring storage..."
if [ -f "$RESTORE_PATH/storage.tar.gz" ]; then
    tar -xzf "$RESTORE_PATH/storage.tar.gz" -C /
    success "Storage restored"
else
    warning "Storage backup not found"
fi

# Restore configuration
log "Restoring configuration..."
if [ -f "$RESTORE_PATH/config.tar.gz" ]; then
    tar -xzf "$RESTORE_PATH/config.tar.gz" -C /
    success "Configuration restored"
else
    warning "Configuration backup not found"
fi

# Set permissions
log "Setting permissions..."
chown -R www-data:www-data /var/www/sarvcast
chmod -R 755 /var/www/sarvcast
chmod -R 777 /var/www/sarvcast/storage
chmod -R 777 /var/www/sarvcast/bootstrap/cache

# Start services
log "Starting services..."
docker-compose -f /var/www/sarvcast/docker-compose.production.yml up -d

# Wait for services to be ready
log "Waiting for services to be ready..."
sleep 60

# Restore database
log "Restoring database..."
if [ -f "$RESTORE_PATH/database.sql" ]; then
    docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T mysql mysql \
        -u sarvcast_user \
        -p$DB_PASSWORD \
        sarvcast_production < "$RESTORE_PATH/database.sql"
    success "Database restored"
else
    error "Database backup not found"
fi

# Restore Docker volumes
log "Restoring Docker volumes..."
if [ -f "$RESTORE_PATH/mysql_volume.tar.gz" ]; then
    docker run --rm \
        -v sarvcast_mysql_data:/data \
        -v $RESTORE_PATH:/backup \
        alpine:latest \
        tar xzf /backup/mysql_volume.tar.gz -C /data
    success "MySQL volume restored"
fi

if [ -f "$RESTORE_PATH/redis_volume.tar.gz" ]; then
    docker run --rm \
        -v sarvcast_redis_data:/data \
        -v $RESTORE_PATH:/backup \
        alpine:latest \
        tar xzf /backup/redis_volume.tar.gz -C /data
    success "Redis volume restored"
fi

# Clear caches
log "Clearing caches..."
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T php-fpm php artisan cache:clear
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T php-fpm php artisan config:clear
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T php-fpm php artisan route:clear
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T php-fpm php artisan view:clear

# Rebuild caches
log "Rebuilding caches..."
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T php-fpm php artisan config:cache
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T php-fpm php artisan route:cache
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T php-fpm php artisan view:cache

# Health check
log "Performing health check..."
if curl -f http://localhost/health > /dev/null 2>&1; then
    success "Application is healthy"
else
    error "Health check failed"
fi

# Cleanup
log "Cleaning up temporary files..."
rm -rf $EXTRACT_DIR

# Display restore summary
echo ""
echo "🎉 Restore completed successfully!"
echo ""
echo "📋 Restore Summary:"
echo "   • Backup File: $BACKUP_FILE"
echo "   • Restore Time: $(date)"
echo "   • Current Backup: $CURRENT_BACKUP"
echo ""
echo "🔗 Access URLs:"
echo "   • Application: https://sarvcast.com"
echo "   • Admin Panel: https://sarvcast.com/admin"
echo "   • API: https://sarvcast.com/api/v1"
echo ""
echo "📝 Next Steps:"
echo "   1. Test all functionality"
echo "   2. Verify data integrity"
echo "   3. Check monitoring"
echo "   4. Update DNS if needed"
echo ""

success "Restore process completed successfully!"

# Log restore completion
log "Restore completed successfully"
log "Backup file: $BACKUP_FILE"
log "Current backup: $CURRENT_BACKUP"

log "Restore process completed successfully!"
