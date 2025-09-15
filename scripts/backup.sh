#!/bin/bash

# SarvCast Backup Script
# This script creates automated backups of the SarvCast application

set -e

# Configuration
PROJECT_NAME="sarvcast"
BACKUP_DIR="/backups/sarvcast"
RETENTION_DAYS=30
LOG_FILE="/var/log/sarvcast-backup.log"

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

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

log "Starting backup process..."

# Generate timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="${PROJECT_NAME}_backup_${TIMESTAMP}"

# Create backup directory
BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"
mkdir -p $BACKUP_PATH

log "Creating database backup..."
# Database backup
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T mysql mysqldump \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    --single-transaction \
    --routines \
    --triggers \
    sarvcast_production > $BACKUP_PATH/database.sql

if [ $? -eq 0 ]; then
    success "Database backup completed"
else
    error "Database backup failed"
fi

log "Creating application files backup..."
# Application files backup
tar -czf $BACKUP_PATH/application.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='.git' \
    --exclude='.env' \
    /var/www/sarvcast

if [ $? -eq 0 ]; then
    success "Application files backup completed"
else
    error "Application files backup failed"
fi

log "Creating storage backup..."
# Storage backup
tar -czf $BACKUP_PATH/storage.tar.gz \
    --exclude='logs' \
    --exclude='framework/cache' \
    --exclude='framework/sessions' \
    --exclude='framework/views' \
    /var/www/sarvcast/storage

if [ $? -eq 0 ]; then
    success "Storage backup completed"
else
    error "Storage backup failed"
fi

log "Creating configuration backup..."
# Configuration backup
tar -czf $BACKUP_PATH/config.tar.gz \
    /var/www/sarvcast/.env \
    /var/www/sarvcast/docker-compose.production.yml \
    /var/www/sarvcast/nginx \
    /var/www/sarvcast/mysql \
    /var/www/sarvcast/supervisor \
    /var/www/sarvcast/monitoring

if [ $? -eq 0 ]; then
    success "Configuration backup completed"
else
    error "Configuration backup failed"
fi

log "Creating Docker volumes backup..."
# Docker volumes backup
docker run --rm \
    -v sarvcast_mysql_data:/data \
    -v $BACKUP_PATH:/backup \
    alpine:latest \
    tar czf /backup/mysql_volume.tar.gz -C /data .

docker run --rm \
    -v sarvcast_redis_data:/data \
    -v $BACKUP_PATH:/backup \
    alpine:latest \
    tar czf /backup/redis_volume.tar.gz -C /data .

if [ $? -eq 0 ]; then
    success "Docker volumes backup completed"
else
    error "Docker volumes backup failed"
fi

log "Creating backup metadata..."
# Create backup metadata
cat > $BACKUP_PATH/metadata.json << EOF
{
    "backup_name": "$BACKUP_NAME",
    "timestamp": "$TIMESTAMP",
    "date": "$(date -Iseconds)",
    "project": "$PROJECT_NAME",
    "version": "$(cd /var/www/sarvcast && git rev-parse HEAD 2>/dev/null || echo 'unknown')",
    "files": {
        "database": "database.sql",
        "application": "application.tar.gz",
        "storage": "storage.tar.gz",
        "config": "config.tar.gz",
        "mysql_volume": "mysql_volume.tar.gz",
        "redis_volume": "redis_volume.tar.gz"
    },
    "size": "$(du -sh $BACKUP_PATH | cut -f1)"
}
EOF

# Calculate backup size
BACKUP_SIZE=$(du -sh $BACKUP_PATH | cut -f1)
log "Backup size: $BACKUP_SIZE"

# Create backup archive
log "Creating backup archive..."
cd $BACKUP_DIR
tar -czf "${BACKUP_NAME}.tar.gz" $BACKUP_NAME
rm -rf $BACKUP_NAME

# Verify backup
if [ -f "${BACKUP_NAME}.tar.gz" ]; then
    success "Backup archive created successfully"
else
    error "Backup archive creation failed"
fi

# Cleanup old backups
log "Cleaning up old backups..."
find $BACKUP_DIR -name "*.tar.gz" -type f -mtime +$RETENTION_DAYS -delete

# Count remaining backups
BACKUP_COUNT=$(find $BACKUP_DIR -name "*.tar.gz" -type f | wc -l)
log "Remaining backups: $BACKUP_COUNT"

# Send notification (if configured)
if [ -n "$BACKUP_NOTIFICATION_EMAIL" ]; then
    log "Sending backup notification..."
    echo "SarvCast backup completed successfully at $(date)" | \
    mail -s "SarvCast Backup Completed" $BACKUP_NOTIFICATION_EMAIL
fi

# Log backup completion
log "Backup completed successfully"
log "Backup file: $BACKUP_DIR/${BACKUP_NAME}.tar.gz"
log "Backup size: $BACKUP_SIZE"

success "Backup process completed successfully!"

# Display backup summary
echo ""
echo "📋 Backup Summary:"
echo "   • Backup Name: $BACKUP_NAME"
echo "   • Backup Size: $BACKUP_SIZE"
echo "   • Backup Location: $BACKUP_DIR/${BACKUP_NAME}.tar.gz"
echo "   • Retention: $RETENTION_DAYS days"
echo "   • Remaining Backups: $BACKUP_COUNT"
echo ""

# Test backup integrity
log "Testing backup integrity..."
if tar -tzf "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" > /dev/null 2>&1; then
    success "Backup integrity verified"
else
    error "Backup integrity check failed"
fi

log "Backup process completed successfully!"
